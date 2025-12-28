from extensions import db
from datetime import datetime
from sqlalchemy import Enum as SQLEnum

class Alert(db.Model):
    __tablename__ = 'alerts'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id', ondelete='CASCADE'), nullable=False, index=True)
    product_id = db.Column(db.Integer, db.ForeignKey('products.id', ondelete='CASCADE'), nullable=False, index=True)
    target_price = db.Column(db.Numeric(10, 2), nullable=False)
    alert_type = db.Column(SQLEnum('PRICE_DROP', 'PRICE_INCREASE', 'STOCK_AVAILABLE', name='alert_type'), default='PRICE_DROP', nullable=False)
    is_active = db.Column(db.Boolean, default=True, nullable=False)
    is_read = db.Column(db.Boolean, default=False, nullable=False)
    email_sent = db.Column(db.Boolean, default=False, nullable=False)
    whatsapp_sent = db.Column(db.Boolean, default=False, nullable=False)
    push_sent = db.Column(db.Boolean, default=False, nullable=False)
    triggered_at = db.Column(db.DateTime, nullable=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow, nullable=False)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, nullable=False)
    
    def to_dict(self, include_product=False):
        """Convert alert to dictionary"""
        data = {
            'id': self.id,
            'user_id': self.user_id,
            'product_id': self.product_id,
            'target_price': float(self.target_price) if self.target_price else 0.0,
            'alert_type': self.alert_type,
            'is_active': self.is_active,
            'is_read': self.is_read,
            'email_sent': self.email_sent,
            'whatsapp_sent': self.whatsapp_sent,
            'push_sent': self.push_sent,
            'triggered_at': self.triggered_at.isoformat() if self.triggered_at else None,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None,
        }
        
        if include_product and self.product:
            data['product'] = {
                'id': self.product.id,
                'title': self.product.title,
                'current_price': float(self.product.current_price) if self.product.current_price else 0.0,
                'image_url': self.product.image_url,
                'amazon_url': self.product.amazon_url,
                'currency': self.product.currency,
                'marketplace': self.product.marketplace,
            }
        
        return data

