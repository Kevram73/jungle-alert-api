from extensions import db
from datetime import datetime
import bcrypt
from sqlalchemy.orm import relationship

class User(db.Model):
    __tablename__ = 'users'
    
    id = db.Column(db.Integer, primary_key=True)
    email = db.Column(db.String(255), unique=True, nullable=False, index=True)
    username = db.Column(db.String(100), unique=True, nullable=False, index=True)
    hashed_password = db.Column(db.String(255), nullable=False)
    first_name = db.Column(db.String(100), nullable=True)
    last_name = db.Column(db.String(100), nullable=True)
    profile_picture_url = db.Column(db.String(500), nullable=True)
    subscription_tier = db.Column(db.Enum('FREE', 'PREMIUM_SIMPLE', 'PREMIUM_DELUXE'), default='FREE', nullable=False)
    subscription_start_date = db.Column(db.DateTime, nullable=True)
    subscription_end_date = db.Column(db.DateTime, nullable=True)
    stripe_customer_id = db.Column(db.String(255), nullable=True)
    email_notifications = db.Column(db.Boolean, default=True, nullable=False)
    whatsapp_notifications = db.Column(db.Boolean, default=False, nullable=False)
    push_notifications = db.Column(db.Boolean, default=True, nullable=False)
    whatsapp_number = db.Column(db.String(50), nullable=True)
    fcm_token = db.Column(db.Text, nullable=True)
    is_active = db.Column(db.Boolean, default=True, nullable=False)
    is_verified = db.Column(db.Boolean, default=False, nullable=False)
    verification_token = db.Column(db.String(255), nullable=True)
    gdpr_consent = db.Column(db.Boolean, default=False, nullable=False)
    data_retention_consent = db.Column(db.Boolean, default=False, nullable=False)
    newsletter_consent = db.Column(db.Boolean, default=False, nullable=False)
    last_login = db.Column(db.DateTime, nullable=True)
    remember_token = db.Column(db.String(100), nullable=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow, nullable=False)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, nullable=False)
    
    # Relationships
    products = relationship('Product', backref='user', lazy='dynamic', cascade='all, delete-orphan')
    alerts = relationship('Alert', backref='user', lazy='dynamic', cascade='all, delete-orphan')
    subscriptions = relationship('Subscription', backref='user', lazy='dynamic', cascade='all, delete-orphan')
    
    def set_password(self, password):
        """Hash and set password"""
        self.hashed_password = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
    
    def check_password(self, password):
        """Check if password matches"""
        return bcrypt.checkpw(password.encode('utf-8'), self.hashed_password.encode('utf-8'))
    
    def to_dict(self, include_sensitive=False):
        """Convert user to dictionary"""
        data = {
            'id': self.id,
            'email': self.email,
            'username': self.username,
            'first_name': self.first_name,
            'last_name': self.last_name,
            'profile_picture_url': self.profile_picture_url,
            'subscription_tier': self.subscription_tier,
            'subscription_start_date': self.subscription_start_date.isoformat() if self.subscription_start_date else None,
            'subscription_end_date': self.subscription_end_date.isoformat() if self.subscription_end_date else None,
            'email_notifications': self.email_notifications,
            'whatsapp_notifications': self.whatsapp_notifications,
            'push_notifications': self.push_notifications,
            'is_active': self.is_active,
            'is_verified': self.is_verified,
            'gdpr_consent': self.gdpr_consent,
            'data_retention_consent': self.data_retention_consent,
            'newsletter_consent': self.newsletter_consent,
            'last_login': self.last_login.isoformat() if self.last_login else None,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None,
        }
        if include_sensitive:
            data['stripe_customer_id'] = self.stripe_customer_id
            data['whatsapp_number'] = self.whatsapp_number
        return data

