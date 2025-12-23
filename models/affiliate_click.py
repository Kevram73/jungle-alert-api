from extensions import db
from datetime import datetime

class AffiliateClick(db.Model):
    __tablename__ = 'affiliate_clicks'
    
    id = db.Column(db.Integer, primary_key=True)
    product_id = db.Column(db.Integer, db.ForeignKey('products.id', ondelete='CASCADE'), nullable=False, index=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id', ondelete='CASCADE'), nullable=False, index=True)
    clicked_at = db.Column(db.DateTime, default=datetime.utcnow, nullable=False, index=True)
    
    def to_dict(self):
        """Convert affiliate click to dictionary"""
        return {
            'id': self.id,
            'product_id': self.product_id,
            'user_id': self.user_id,
            'clicked_at': self.clicked_at.isoformat() if self.clicked_at else None,
        }

