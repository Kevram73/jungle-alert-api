from models.user import User
from models.subscription import Subscription
from datetime import datetime, timedelta
from extensions import db

class SubscriptionService:
    """Service for managing subscriptions"""
    
    PLAN_PRICES = {
        'premium_simple': 10.0,
        'premium_deluxe': 30.0,
    }
    
    PLAN_DURATION_DAYS = 365  # 1 year
    
    @staticmethod
    def get_max_products_for_plan(plan: str) -> int:
        """Get max products for a subscription plan"""
        mapping = {
            'FREE': 1,
            'PREMIUM_SIMPLE': 1,
            'PREMIUM_DELUXE': 999999,  # Unlimited
        }
        return mapping.get(plan, 1)
    
    @staticmethod
    def can_add_product(user: User) -> bool:
        """Check if user can add a product"""
        # Check if subscription is active
        if user.subscription_tier != 'FREE':
            if not user.subscription_end_date or user.subscription_end_date < datetime.utcnow():
                # Subscription expired, revert to free
                user.subscription_tier = 'FREE'
                user.subscription_end_date = None
                db.session.commit()
        
        max_products = SubscriptionService.get_max_products_for_plan(user.subscription_tier)
        from models.product import Product
        current_count = Product.query.filter_by(user_id=user.id, is_active=True).count()
        
        return current_count < max_products
    
    @staticmethod
    def can_add_alert(user: User) -> bool:
        """Check if user can add an alert"""
        # For now, alerts are unlimited for all plans
        return True
    
    @staticmethod
    def create_subscription(user: User, plan_name: str, payment_reference: str = None) -> Subscription:
        """Create a new subscription"""
        if plan_name not in SubscriptionService.PLAN_PRICES:
            raise ValueError(f"Invalid plan: {plan_name}")
        
        # Create subscription
        subscription = Subscription(
            user_id=user.id,
            plan=plan_name,
            status='active',
            amount=SubscriptionService.PLAN_PRICES[plan_name],
            currency='EUR',
            starts_at=datetime.utcnow(),
            expires_at=datetime.utcnow() + timedelta(days=SubscriptionService.PLAN_DURATION_DAYS),
            payment_reference=payment_reference
        )
        db.session.add(subscription)
        
        # Update user
        if plan_name == 'premium_simple':
            user.subscription_tier = 'PREMIUM_SIMPLE'
        elif plan_name == 'premium_deluxe':
            user.subscription_tier = 'PREMIUM_DELUXE'
        
        user.subscription_start_date = subscription.starts_at
        user.subscription_end_date = subscription.expires_at
        db.session.commit()
        
        return subscription
    
    @staticmethod
    def get_allowed_alert_channels(user: User) -> list:
        """Get allowed alert channels for user"""
        if user.subscription_tier == 'FREE':
            return ['email']
        else:
            return ['email', 'whatsapp', 'push']
    
    @staticmethod
    def get_max_alerts_for_plan(plan: str) -> int:
        """Get max alerts for plan"""
        # For now, unlimited for all plans
        return 999999

