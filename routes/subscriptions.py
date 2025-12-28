from flask import Blueprint, request, jsonify
from flask_jwt_extended import jwt_required, get_jwt_identity
from extensions import db
from models.subscription import Subscription
from services.subscription_service import SubscriptionService

subscriptions_bp = Blueprint('subscriptions', __name__)

@subscriptions_bp.route('/subscriptions', methods=['GET'])
@jwt_required()
def index():
    """Get user's subscriptions"""
    user_id = get_jwt_identity()
    
    subscriptions = Subscription.query.filter_by(user_id=user_id)\
        .order_by(Subscription.created_at.desc())\
        .all()
    
    return jsonify([s.to_dict() for s in subscriptions]), 200

@subscriptions_bp.route('/subscriptions', methods=['POST'])
@jwt_required()
def store():
    """Create a new subscription"""
    user_id = get_jwt_identity()
    data = request.get_json()
    
    if not data.get('plan') or data['plan'] not in ['premium_simple', 'premium_deluxe']:
        return jsonify({'message': 'Valid plan is required'}), 422
    
    from models.user import User
    user = User.query.get(user_id)
    
    try:
        subscription = SubscriptionService.create_subscription(
            user,
            data['plan'],
            data.get('payment_reference')
        )
        return jsonify(subscription.to_dict()), 201
    except ValueError as e:
        return jsonify({'message': str(e)}), 400

@subscriptions_bp.route('/subscriptions/plans', methods=['GET'])
def plans():
    """Get available subscription plans"""
    plans = [
        {
            'plan_id': 'free',
            'name': 'Free',
            'price': 0.0,
            'duration_days': None,
            'max_products': 1,
            'features': ['Email alerts', '1 product tracking'],
        },
        {
            'plan_id': 'premium_simple',
            'name': 'Premium Simple',
            'price': SubscriptionService.PLAN_PRICES['premium_simple'],
            'duration_days': SubscriptionService.PLAN_DURATION_DAYS,
            'max_products': 1,
            'features': [
                'Email alerts',
                'WhatsApp alerts',
                'Push notifications',
                '1 product tracking'
            ],
        },
        {
            'plan_id': 'premium_deluxe',
            'name': 'Premium Deluxe',
            'price': SubscriptionService.PLAN_PRICES['premium_deluxe'],
            'duration_days': SubscriptionService.PLAN_DURATION_DAYS,
            'max_products': 999999,
            'features': [
                'Email alerts',
                'WhatsApp alerts',
                'Push notifications',
                'Unlimited products'
            ],
        },
    ]
    
    return jsonify({'plans': plans}), 200

@subscriptions_bp.route('/subscriptions/limits', methods=['GET'])
@jwt_required()
def limits():
    """Get subscription limits"""
    user_id = get_jwt_identity()
    from models.user import User
    from models.product import Product
    
    user = User.query.get(user_id)
    max_products = SubscriptionService.get_max_products_for_plan(user.subscription_tier)
    current_count = Product.query.filter_by(user_id=user_id, is_active=True).count()
    allowed_channels = SubscriptionService.get_allowed_alert_channels(user)
    
    return jsonify({
        'plan': user.subscription_tier,
        'max_products': max_products,
        'current_products': current_count,
        'remaining_products': max(0, max_products - current_count),
        'allowed_alert_channels': allowed_channels,
        'subscription_expires_at': user.subscription_end_date.isoformat() if user.subscription_end_date else None,
    }), 200

@subscriptions_bp.route('/subscription/upgrade', methods=['PUT'])
@jwt_required()
def upgrade():
    """Upgrade user subscription"""
    user_id = get_jwt_identity()
    data = request.get_json()
    
    if not data.get('subscription_tier'):
        return jsonify({'message': 'subscription_tier is required'}), 422
    
    from models.user import User
    user = User.query.get(user_id)
    
    tier = data['subscription_tier'].upper()
    if tier not in ['PREMIUM_SIMPLE', 'PREMIUM_DELUXE']:
        return jsonify({'message': 'Invalid subscription tier'}), 422
    
    # Map to service format
    plan_map = {
        'PREMIUM_SIMPLE': 'premium_simple',
        'PREMIUM_DELUXE': 'premium_deluxe'
    }
    plan = plan_map[tier]
    
    try:
        # Use the subscription service to upgrade
        subscription = SubscriptionService.create_subscription(
            user,
            plan,
            data.get('payment_reference')
        )
        
        return jsonify({
            'message': 'Subscription upgraded successfully',
            'subscription': subscription.to_dict(),
            'user': user.to_dict()
        }), 200
    except ValueError as e:
        return jsonify({'message': str(e)}), 400

