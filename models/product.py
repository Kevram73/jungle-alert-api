from extensions import db
from datetime import datetime
from sqlalchemy.orm import relationship
from sqlalchemy import JSON
from models.price_history import PriceHistory

class Product(db.Model):
    __tablename__ = 'products'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id', ondelete='CASCADE'), nullable=False, index=True)
    amazon_url = db.Column(db.String(500), nullable=False)
    title = db.Column(db.String(500), nullable=False)
    description = db.Column(db.Text, nullable=True)
    image_url = db.Column(db.String(500), nullable=True)
    current_price = db.Column(db.Numeric(10, 2), nullable=False)
    original_price = db.Column(db.Numeric(10, 2), nullable=True)
    target_price = db.Column(db.Numeric(10, 2), nullable=True)
    asin = db.Column(db.String(20), nullable=True, index=True)
    is_active = db.Column(db.Boolean, default=True, nullable=False)
    currency = db.Column(db.String(3), nullable=True)
    marketplace = db.Column(db.String(10), nullable=True)
    availability = db.Column(db.String(255), nullable=True)
    rating = db.Column(db.Numeric(3, 2), nullable=True)
    review_count = db.Column(db.Integer, nullable=True)
    category = db.Column(db.String(255), nullable=True)
    category_path = db.Column(db.String(500), nullable=True)
    stock_quantity = db.Column(db.Integer, nullable=True)
    stock_status = db.Column(db.String(100), nullable=True)
    brand = db.Column(db.String(255), nullable=True)
    seller = db.Column(db.String(255), nullable=True)
    is_prime = db.Column(db.Boolean, default=False, nullable=False)
    discount_percentage = db.Column(db.Numeric(5, 2), nullable=True)
    features = db.Column(JSON, nullable=True)
    images = db.Column(JSON, nullable=True)
    last_price_check = db.Column(db.DateTime, nullable=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow, nullable=False)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, nullable=False)
    
    # Relationships
    alerts = relationship('Alert', backref='product', lazy='dynamic', cascade='all, delete-orphan')
    price_histories = relationship('PriceHistory', backref='product', lazy='dynamic', cascade='all, delete-orphan')
    affiliate_clicks = relationship('AffiliateClick', backref='product', lazy='dynamic', cascade='all, delete-orphan')
    
    def to_dict(self, include_relations=False):
        """Convert product to dictionary"""
        data = {
            'id': self.id,
            'user_id': self.user_id,
            'amazon_url': self.amazon_url,
            'title': self.title,
            'description': self.description,
            'image_url': self.image_url,
            'current_price': float(self.current_price) if self.current_price else 0.0,
            'original_price': float(self.original_price) if self.original_price else None,
            'target_price': float(self.target_price) if self.target_price else None,
            'asin': self.asin,
            'is_active': self.is_active,
            'currency': self.currency,
            'marketplace': self.marketplace,
            'availability': self.availability,
            'rating': float(self.rating) if self.rating else None,
            'review_count': self.review_count,
            'category': self.category,
            'category_path': self.category_path,
            'stock_quantity': self.stock_quantity,
            'stock_status': self.stock_status,
            'brand': self.brand,
            'seller': self.seller,
            'is_prime': self.is_prime,
            'discount_percentage': float(self.discount_percentage) if self.discount_percentage else None,
            'features': self.features,
            'images': self.images,
            'last_price_check': self.last_price_check.isoformat() if self.last_price_check else None,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None,
        }
        
        if include_relations:
            data['alerts'] = [alert.to_dict() for alert in self.alerts.filter_by(is_active=True).all()]
            data['price_histories'] = [ph.to_dict() for ph in self.price_histories.order_by(PriceHistory.recorded_at.desc()).limit(30).all()]
        
        return data

