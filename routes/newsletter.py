from flask import Blueprint, request, jsonify
from flask_jwt_extended import jwt_required, get_jwt_identity
from extensions import db
from models.user import User

newsletter_bp = Blueprint('newsletter', __name__)

@newsletter_bp.route('/newsletter/preview', methods=['GET'])
@jwt_required()
def preview():
    """Get newsletter preview"""
    # Placeholder for newsletter preview
    return jsonify({
        'message': 'Newsletter preview',
        'preview': 'Newsletter content preview'
    }), 200

@newsletter_bp.route('/newsletter/consent', methods=['PUT'])
@jwt_required()
def update_consent():
    """Update newsletter consent"""
    user_id = get_jwt_identity()
    user = User.query.get(user_id)
    
    if not user:
        return jsonify({'message': 'User not found'}), 404
    
    data = request.get_json()
    if 'consent' not in data:
        return jsonify({'message': 'Consent value is required'}), 422
    
    user.newsletter_consent = bool(data['consent'])
    db.session.commit()
    
    return jsonify({
        'message': 'Newsletter consent updated successfully',
        'newsletter_consent': user.newsletter_consent
    }), 200

@newsletter_bp.route('/newsletter/consent', methods=['GET'])
@jwt_required()
def get_consent():
    """Get newsletter consent"""
    user_id = get_jwt_identity()
    user = User.query.get(user_id)
    
    if not user:
        return jsonify({'message': 'User not found'}), 404
    
    return jsonify({
        'newsletter_consent': user.newsletter_consent
    }), 200

