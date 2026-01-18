from models.alert import Alert
from models.product import Product
from datetime import datetime
import logging
from services.email_service import EmailService

logger = logging.getLogger(__name__)
email_service = EmailService()

class NotificationService:
    """Service for handling notifications and alerts"""
    
    def check_and_trigger_alerts(self, product: Product, send_notifications: bool = False) -> list:
        """Check and trigger alerts for a product"""
        triggered_alerts = []
        
        alerts = Alert.query.filter_by(
            product_id=product.id,
            is_active=True
        ).filter(Alert.triggered_at.is_(None)).all()
        
        for alert in alerts:
            should_trigger = False
            
            if alert.alert_type == 'PRICE_DROP':
                should_trigger = float(product.current_price) <= float(alert.target_price)
            elif alert.alert_type == 'PRICE_INCREASE':
                should_trigger = float(product.current_price) >= float(alert.target_price)
            elif alert.alert_type == 'STOCK_AVAILABLE':
                # For now, consider product always in stock
                should_trigger = True
            
            if should_trigger:
                alert.triggered_at = datetime.utcnow()
                alert.updated_at = datetime.utcnow()
                
                # Send notifications if requested
                if send_notifications:
                    self.send_alert_notifications(alert)
                
                triggered_alerts.append(alert)
        
        from extensions import db
        db.session.commit()
        
        return triggered_alerts
    
    def send_alert_notifications(self, alert: Alert):
        """Send notifications for a triggered alert"""
        user = alert.user
        product = alert.product
        
        if user.email_notifications and not alert.email_sent and user.email:
            try:
                success = email_service.send_price_alert_email(
                    user_email=user.email,
                    user_name=user.name or user.email.split('@')[0],
                    product_name=product.name,
                    product_url=product.url,
                    current_price=float(product.current_price),
                    target_price=float(alert.target_price),
                    alert_type=alert.alert_type
                )
                if success:
                    alert.email_sent = True
                    logger.info(f"Email alert sent to {user.email} for product {product.name}")
                else:
                    logger.error(f"Failed to send email alert to {user.email}")
            except Exception as e:
                logger.error(f"Error sending email alert: {str(e)}")
        
        if user.push_notifications and not alert.push_sent and user.fcm_token:
            # TODO: Send push notification
            alert.push_sent = True
        
        if user.whatsapp_notifications and not alert.whatsapp_sent and user.whatsapp_number:
            # TODO: Send WhatsApp notification
            alert.whatsapp_sent = True
        
        from extensions import db
        db.session.commit()

