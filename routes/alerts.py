from flask import Blueprint, request, jsonify
from flask_jwt_extended import jwt_required, get_jwt_identity
from extensions import db
from models.alert import Alert
from models.product import Product
from services.notification_service import NotificationService
from datetime import datetime

alerts_bp = Blueprint('alerts', __name__)
notification_service = NotificationService()

def map_alert_type(input_type: str) -> str:
    """Map alert type from mobile app to API type"""
    mapping = {
        'email_notification': 'PRICE_DROP',
        'price_drop': 'PRICE_DROP',
        'price_increase': 'PRICE_INCREASE',
        'stock_available': 'STOCK_AVAILABLE',
        'immediate': 'PRICE_DROP',
        'daily': 'PRICE_DROP',
        'weekly': 'PRICE_DROP',
    }
    
    input_type = input_type.lower()
    if input_type in mapping:
        return mapping[input_type]
    
    # If already valid uppercase type
    upper_type = input_type.upper()
    if upper_type in ['PRICE_DROP', 'PRICE_INCREASE', 'STOCK_AVAILABLE']:
        return upper_type
    
    return 'PRICE_DROP'  # Default

@alerts_bp.route('/alerts', methods=['GET'])
@jwt_required()
def index():
    """Get user's alerts"""
    user_id = get_jwt_identity()
    
    page = request.args.get('page', 1, type=int)
    per_page = request.args.get('per_page', 20, type=int)
    
    alerts = Alert.query.filter_by(user_id=user_id)\
        .order_by(Alert.created_at.desc())\
        .paginate(page=page, per_page=per_page, error_out=False)
    
    return jsonify({
        'message': 'Alerts retrieved successfully',
        'alerts': {
            'data': [a.to_dict(include_product=True) for a in alerts.items],
            'current_page': alerts.page,
            'per_page': alerts.per_page,
            'total': alerts.total,
            'pages': alerts.pages,
        }
    }), 200

@alerts_bp.route('/alerts', methods=['POST'])
@jwt_required()
def store():
    """Create a new alert"""
    user_id = get_jwt_identity()
    data = request.get_json()
    
    if not data.get('product_id'):
        return jsonify({'message': 'Product ID is required'}), 422
    if not data.get('target_price'):
        return jsonify({'message': 'Target price is required'}), 422
    if not data.get('alert_type'):
        return jsonify({'message': 'Alert type is required'}), 422
    
    # Check product belongs to user
    product = Product.query.filter_by(id=data['product_id'], user_id=user_id).first()
    if not product:
        return jsonify({'message': 'Product not found'}), 404
    
    # Map alert type
    alert_type = map_alert_type(data['alert_type'])
    
    # Check for existing active alert
    existing = Alert.query.filter_by(
        user_id=user_id,
        product_id=data['product_id'],
        alert_type=alert_type,
        is_active=True
    ).first()
    
    if existing:
        return jsonify({
            'message': 'An active alert already exists for this product with the same type',
            'alert': existing.to_dict()
        }), 409
    
    alert = Alert(
        user_id=user_id,
        product_id=data['product_id'],
        target_price=data['target_price'],
        alert_type=alert_type,
        is_active=data.get('is_active', True)
    )
    
    db.session.add(alert)
    db.session.commit()
    
    return jsonify({
        'message': 'Alert created successfully',
        'alert': alert.to_dict(include_product=True)
    }), 201

@alerts_bp.route('/alerts/<int:alert_id>', methods=['GET'])
@jwt_required()
def show(alert_id):
    """Get a specific alert"""
    user_id = get_jwt_identity()
    alert = Alert.query.filter_by(id=alert_id, user_id=user_id).first_or_404()
    
    return jsonify({
        'message': 'Alert retrieved successfully',
        'alert': alert.to_dict(include_product=True)
    }), 200

@alerts_bp.route('/alerts/<int:alert_id>', methods=['PUT'])
@jwt_required()
def update(alert_id):
    """Update an alert"""
    user_id = get_jwt_identity()
    alert = Alert.query.filter_by(id=alert_id, user_id=user_id).first_or_404()
    
    data = request.get_json()
    
    if 'target_price' in data:
        alert.target_price = data['target_price']
    if 'is_active' in data:
        alert.is_active = data['is_active']
    if 'alert_type' in data:
        alert.alert_type = map_alert_type(data['alert_type'])
    
    alert.updated_at = datetime.utcnow()
    db.session.commit()
    
    return jsonify({
        'message': 'Alert updated successfully',
        'alert': alert.to_dict(include_product=True)
    }), 200

@alerts_bp.route('/alerts/<int:alert_id>', methods=['DELETE'])
@jwt_required()
def destroy(alert_id):
    """Delete an alert"""
    user_id = get_jwt_identity()
    alert = Alert.query.filter_by(id=alert_id, user_id=user_id).first_or_404()
    
    db.session.delete(alert)
    db.session.commit()
    
    return jsonify({
        'message': 'Alert deleted successfully',
        'success': True
    }), 200

@alerts_bp.route('/alerts/<int:alert_id>/toggle', methods=['POST'])
@jwt_required()
def toggle(alert_id):
    """Toggle alert active status"""
    user_id = get_jwt_identity()
    alert = Alert.query.filter_by(id=alert_id, user_id=user_id).first_or_404()
    
    alert.is_active = not alert.is_active
    alert.updated_at = datetime.utcnow()
    db.session.commit()
    
    return jsonify({
        'message': 'Alert status updated successfully',
        'alert': alert.to_dict(include_product=True)
    }), 200

@alerts_bp.route('/alerts/active', methods=['GET'])
@jwt_required()
def active():
    """Get active alerts"""
    user_id = get_jwt_identity()
    
    alerts = Alert.query.filter_by(user_id=user_id, is_active=True)\
        .order_by(Alert.created_at.desc())\
        .all()
    
    return jsonify({
        'message': 'Active alerts retrieved successfully',
        'alerts': [a.to_dict(include_product=True) for a in alerts]
    }), 200

@alerts_bp.route('/alerts/triggered', methods=['GET'])
@jwt_required()
def triggered():
    """Get triggered alerts"""
    user_id = get_jwt_identity()
    
    page = request.args.get('page', 1, type=int)
    per_page = request.args.get('per_page', 20, type=int)
    
    alerts = Alert.query.filter_by(user_id=user_id)\
        .filter(Alert.triggered_at.isnot(None))\
        .order_by(Alert.triggered_at.desc())\
        .paginate(page=page, per_page=per_page, error_out=False)
    
    return jsonify({
        'message': 'Triggered alerts retrieved successfully',
        'alerts': {
            'data': [a.to_dict(include_product=True) for a in alerts.items],
            'current_page': alerts.page,
            'per_page': alerts.per_page,
            'total': alerts.total,
            'pages': alerts.pages,
        }
    }), 200

@alerts_bp.route('/products/<int:product_id>/alerts', methods=['GET'])
@jwt_required()
def by_product(product_id):
    """Get alerts for a product"""
    user_id = get_jwt_identity()
    product = Product.query.filter_by(id=product_id, user_id=user_id).first_or_404()
    
    alerts = Alert.query.filter_by(user_id=user_id, product_id=product_id)\
        .order_by(Alert.created_at.desc())\
        .all()
    
    return jsonify({
        'message': 'Product alerts retrieved successfully',
        'alerts': [a.to_dict() for a in alerts]
    }), 200

@alerts_bp.route('/products/<int:product_id>/check-alerts', methods=['POST'])
@jwt_required()
def check_alerts(product_id):
    """Check and trigger alerts for a product"""
    user_id = get_jwt_identity()
    product = Product.query.filter_by(id=product_id, user_id=user_id).first_or_404()
    
    triggered = notification_service.check_and_trigger_alerts(product, send_notifications=False)
    
    return jsonify({
        'message': 'Alert check completed',
        'triggered_alerts': [a.to_dict() for a in triggered],
        'total_checked': Alert.query.filter_by(product_id=product_id, is_active=True).count()
    }), 200

@alerts_bp.route('/alerts/bulk-update', methods=['POST'])
@jwt_required()
def bulk_update():
    """Bulk update alert status"""
    user_id = get_jwt_identity()
    data = request.get_json()
    
    if not data.get('alert_ids') or not isinstance(data.get('is_active'), bool):
        return jsonify({'message': 'alert_ids and is_active are required'}), 422
    
    updated = Alert.query.filter_by(user_id=user_id)\
        .filter(Alert.id.in_(data['alert_ids']))\
        .update({'is_active': data['is_active']}, synchronize_session=False)
    
    db.session.commit()
    
    return jsonify({
        'message': 'Alerts updated successfully',
        'updated_count': updated
    }), 200

@alerts_bp.route('/alerts/bulk-delete', methods=['POST'])
@jwt_required()
def bulk_delete():
    """Delete multiple alerts"""
    user_id = get_jwt_identity()
    data = request.get_json()
    
    if not data.get('alert_ids'):
        return jsonify({'message': 'alert_ids is required'}), 422
    
    deleted = Alert.query.filter_by(user_id=user_id)\
        .filter(Alert.id.in_(data['alert_ids']))\
        .delete(synchronize_session=False)
    
    db.session.commit()
    
    return jsonify({
        'message': 'Alerts deleted successfully',
        'deleted_count': deleted
    }), 200

