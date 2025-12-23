from flask import Blueprint, request, jsonify
from flask_jwt_extended import jwt_required, get_jwt_identity
from extensions import db
from models.user import User

users_bp = Blueprint('users', __name__)

@users_bp.route('/users/me', methods=['GET'])
@jwt_required()
def me():
    """Get current user profile"""
    user_id = get_jwt_identity()
    user = User.query.get(user_id)
    
    if not user:
        return jsonify({'message': 'User not found'}), 404
    
    return jsonify(user.to_dict()), 200

@users_bp.route('/users/me', methods=['PUT'])
@jwt_required()
def update():
    """Update user profile"""
    user_id = get_jwt_identity()
    user = User.query.get(user_id)
    
    if not user:
        return jsonify({'message': 'User not found'}), 404
    
    data = request.get_json()
    
    # Check email uniqueness
    if 'email' in data and data['email'] != user.email:
        if User.query.filter_by(email=data['email']).first():
            return jsonify({'message': 'Email already exists'}), 422
    
    # Check username uniqueness
    if 'username' in data and data['username'] != user.username:
        if User.query.filter_by(username=data['username']).first():
            return jsonify({'message': 'Username already exists'}), 422
    
    # Update fields
    for key in ['first_name', 'last_name', 'email', 'username', 
                'email_notifications', 'whatsapp_notifications', 
                'push_notifications', 'whatsapp_number', 'fcm_token']:
        if key in data:
            setattr(user, key, data[key])
    
    db.session.commit()
    
    return jsonify({
        'message': 'Profile updated successfully',
        'user': user.to_dict()
    }), 200

@users_bp.route('/users/me/fcm-token', methods=['POST'])
@jwt_required()
def update_fcm_token():
    """Update FCM token"""
    user_id = get_jwt_identity()
    user = User.query.get(user_id)
    
    if not user:
        return jsonify({'message': 'User not found'}), 404
    
    data = request.get_json()
    if not data.get('fcm_token'):
        return jsonify({'message': 'FCM token is required'}), 422
    
    user.fcm_token = data['fcm_token']
    db.session.commit()
    
    return jsonify({
        'message': 'FCM token updated successfully',
        'fcm_token_set': bool(user.fcm_token)
    }), 200

@users_bp.route('/users/change-password', methods=['POST'])
@jwt_required()
def change_password():
    """Change user password"""
    user_id = get_jwt_identity()
    user = User.query.get(user_id)
    
    if not user:
        return jsonify({'message': 'User not found'}), 404
    
    data = request.get_json()
    if not data.get('current_password') or not data.get('new_password'):
        return jsonify({'message': 'Current password and new password are required'}), 422
    
    if not user.check_password(data['current_password']):
        return jsonify({'message': 'Current password is incorrect'}), 400
    
    user.set_password(data['new_password'])
    db.session.commit()
    
    return jsonify({'message': 'Password changed successfully'}), 200

@users_bp.route('/users/me', methods=['DELETE'])
@jwt_required()
def destroy():
    """Delete user account"""
    user_id = get_jwt_identity()
    user = User.query.get(user_id)
    
    if not user:
        return jsonify({'message': 'User not found'}), 404
    
    data = request.get_json()
    if not data.get('password') or not data.get('confirm_deletion'):
        return jsonify({'message': 'Password and confirmation are required'}), 422
    
    if not user.check_password(data['password']):
        return jsonify({'message': 'Password is incorrect'}), 400
    
    db.session.delete(user)
    db.session.commit()
    
    return jsonify({'message': 'Account deleted successfully'}), 200

