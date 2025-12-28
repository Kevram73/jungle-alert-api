from extensions import db
from datetime import datetime

class PriceHistory(db.Model):
    __tablename__ = 'price_histories'
    
    id = db.Column(db.Integer, primary_key=True)
    product_id = db.Column(db.Integer, db.ForeignKey('products.id', ondelete='CASCADE'), nullable=False, index=True)
    price = db.Column(db.Numeric(10, 2), nullable=False)
    recorded_at = db.Column(db.DateTime, default=datetime.utcnow, nullable=False, index=True)
    
    def to_dict(self):
        """Convert price history to dictionary"""
        return {
            'id': self.id,
            'product_id': self.product_id,
            'price': float(self.price) if self.price else 0.0,
            'recorded_at': self.recorded_at.isoformat() if self.recorded_at else None,
        }

