from models.alert import Alert
from models.product import Product
from datetime import datetime
import logging

logger = logging.getLogger(__name__)

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
        # This would integrate with email, push, and WhatsApp services
        # For now, just mark as sent
        user = alert.user
        
        if user.email_notifications and not alert.email_sent:
            # TODO: Send email notification
            alert.email_sent = True
        
        if user.push_notifications and not alert.push_sent and user.fcm_token:
            # TODO: Send push notification
            alert.push_sent = True
        
        if user.whatsapp_notifications and not alert.whatsapp_sent and user.whatsapp_number:
            # TODO: Send WhatsApp notification
            alert.whatsapp_sent = True
        
        from extensions import db
        db.session.commit()

