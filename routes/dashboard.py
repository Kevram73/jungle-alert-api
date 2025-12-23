from flask import Blueprint, jsonify
from flask_jwt_extended import jwt_required, get_jwt_identity
from models.product import Product
from models.alert import Alert
from datetime import datetime, timedelta

dashboard_bp = Blueprint('dashboard', __name__)

@dashboard_bp.route('/dashboard', methods=['GET'])
@jwt_required()
def index():
    """Get dashboard data"""
    user_id = get_jwt_identity()
    
    total_products = Product.query.filter_by(user_id=user_id).count()
    active_alerts = Alert.query.filter_by(user_id=user_id, is_active=True).count()
    
    today = datetime.utcnow().date()
    price_drops_today = Alert.query.filter_by(user_id=user_id, alert_type='PRICE_DROP')\
        .filter(Alert.triggered_at >= datetime.combine(today, datetime.min.time()))\
        .count()
    
    total_savings = calculate_total_savings(user_id)
    recent_activity = get_recent_activity(user_id)
    
    # Products with active alerts
    products_with_alerts = Product.query.filter_by(user_id=user_id)\
        .join(Alert, Product.id == Alert.product_id)\
        .filter(Alert.is_active == True)\
        .limit(5)\
        .all()
    
    return jsonify({
        'message': 'Dashboard data retrieved successfully',
        'stats': {
            'total_products': total_products,
            'active_alerts': active_alerts,
            'price_drops_today': price_drops_today,
            'total_savings': total_savings,
        },
        'recent_activity': recent_activity,
        'products_with_alerts': [p.to_dict(include_relations=True) for p in products_with_alerts],
    }), 200

def calculate_total_savings(user_id):
    """Calculate total savings from price drops"""
    triggered_alerts = Alert.query.filter_by(user_id=user_id, alert_type='PRICE_DROP')\
        .filter(Alert.triggered_at.isnot(None))\
        .all()
    
    total_savings = 0.0
    for alert in triggered_alerts:
        if alert.product:
            savings = float(alert.product.current_price) - float(alert.target_price)
            if savings > 0:
                total_savings += savings
    
    return round(total_savings, 2)

def get_recent_activity(user_id):
    """Get recent activity"""
    activities = []
    
    # Triggered alerts
    triggered_alerts = Alert.query.filter_by(user_id=user_id)\
        .filter(Alert.triggered_at.isnot(None))\
        .order_by(Alert.triggered_at.desc())\
        .limit(5)\
        .all()
    
    for alert in triggered_alerts:
        if alert.product:
            activities.append({
                'type': 'alert_triggered',
                'message': f"Price alert triggered for {alert.product.title}",
                'timestamp': alert.triggered_at.isoformat() if alert.triggered_at else None,
                'data': {
                    'product_id': alert.product_id,
                    'target_price': float(alert.target_price),
                    'current_price': float(alert.product.current_price),
                }
            })
    
    # New products
    new_products = Product.query.filter_by(user_id=user_id)\
        .order_by(Product.created_at.desc())\
        .limit(3)\
        .all()
    
    for product in new_products:
        activities.append({
            'type': 'product_added',
            'message': f"New product added: {product.title}",
            'timestamp': product.created_at.isoformat() if product.created_at else None,
            'data': {
                'product_id': product.id,
                'price': float(product.current_price),
            }
        })
    
    # Sort by timestamp
    activities.sort(key=lambda x: x['timestamp'] or '', reverse=True)
    
    return activities[:10]

