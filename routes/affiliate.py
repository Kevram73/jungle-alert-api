from flask import Blueprint, request, jsonify
from flask_jwt_extended import jwt_required, get_jwt_identity
from extensions import db
from models.product import Product
from models.affiliate_click import AffiliateClick
from datetime import datetime

affiliate_bp = Blueprint('affiliate', __name__)

@affiliate_bp.route('/affiliate/products/<int:product_id>/buy-link', methods=['GET'])
@jwt_required()
def get_buy_link(product_id):
    """Get affiliate buy link for a product"""
    user_id = get_jwt_identity()
    product = Product.query.filter_by(id=product_id, user_id=user_id).first_or_404()
    
    # Return the Amazon URL as buy link
    return jsonify({
        'buy_link': product.amazon_url,
        'product_id': product.id
    }), 200

@affiliate_bp.route('/affiliate/products/<int:product_id>/track-click', methods=['POST'])
@jwt_required()
def track_click(product_id):
    """Track affiliate click"""
    user_id = get_jwt_identity()
    product = Product.query.filter_by(id=product_id, user_id=user_id).first_or_404()
    
    click = AffiliateClick(
        product_id=product_id,
        user_id=user_id,
        clicked_at=datetime.utcnow()
    )
    
    db.session.add(click)
    db.session.commit()
    
    return jsonify({
        'message': 'Click tracked successfully',
        'click': click.to_dict()
    }), 201

