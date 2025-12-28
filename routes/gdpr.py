from flask import Blueprint, request, jsonify
from flask_jwt_extended import jwt_required, get_jwt_identity
from extensions import db
from models.user import User

gdpr_bp = Blueprint('gdpr', __name__)

@gdpr_bp.route('/gdpr/export-data', methods=['GET'])
@jwt_required()
def export_data():
    """Export user data (GDPR)"""
    user_id = get_jwt_identity()
    user = User.query.get(user_id)
    
    if not user:
        return jsonify({'message': 'User not found'}), 404
    
    # Export all user data
    from models.product import Product
    from models.alert import Alert
    from models.subscription import Subscription
    
    data = {
        'user': user.to_dict(include_sensitive=True),
        'products': [p.to_dict() for p in Product.query.filter_by(user_id=user_id).all()],
        'alerts': [a.to_dict() for a in Alert.query.filter_by(user_id=user_id).all()],
        'subscriptions': [s.to_dict() for s in Subscription.query.filter_by(user_id=user_id).all()],
    }
    
    return jsonify({
        'message': 'Data exported successfully',
        'data': data
    }), 200

@gdpr_bp.route('/gdpr/delete-account', methods=['DELETE'])
@jwt_required()
def delete_account():
    """Delete user account (GDPR)"""
    user_id = get_jwt_identity()
    user = User.query.get(user_id)
    
    if not user:
        return jsonify({'message': 'User not found'}), 404
    
    data = request.get_json()
    if not data.get('password'):
        return jsonify({'message': 'Password is required for account deletion'}), 422
    
    if not user.check_password(data['password']):
        return jsonify({'message': 'Password is incorrect'}), 400
    
    # Delete user (cascade will handle related data)
    db.session.delete(user)
    db.session.commit()
    
    return jsonify({'message': 'Account deleted successfully'}), 200

