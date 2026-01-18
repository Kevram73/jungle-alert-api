import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from email.utils import formataddr
import logging
from flask import current_app

logger = logging.getLogger(__name__)

class EmailService:
    """Service for sending emails"""
    
    def __init__(self):
        self.smtp_server = None
        self.smtp_port = None
        self.username = None
        self.password = None
        self.from_address = None
        self.from_name = None
        self.use_tls = None
        self.use_ssl = None
    
    def _init_config(self):
        """Initialize email configuration from Flask app config"""
        if current_app:
            self.smtp_server = current_app.config.get('MAIL_SERVER')
            self.smtp_port = current_app.config.get('MAIL_PORT')
            self.username = current_app.config.get('MAIL_USERNAME')
            self.password = current_app.config.get('MAIL_PASSWORD')
            self.from_address = current_app.config.get('MAIL_DEFAULT_SENDER')
            self.from_name = current_app.config.get('MAIL_FROM_NAME')
            self.use_tls = current_app.config.get('MAIL_USE_TLS', True)
            self.use_ssl = current_app.config.get('MAIL_USE_SSL', False)
    
    def send_email(self, to_email: str, subject: str, html_body: str, text_body: str = None) -> bool:
        """
        Send an email
        
        Args:
            to_email: Recipient email address
            subject: Email subject
            html_body: HTML content of the email
            text_body: Plain text content (optional)
            
        Returns:
            bool: True if email sent successfully, False otherwise
        """
        try:
            self._init_config()
            
            if not all([self.smtp_server, self.smtp_port, self.username, self.password]):
                logger.error("Email configuration is incomplete")
                return False
            
            # Create message
            msg = MIMEMultipart('alternative')
            msg['Subject'] = subject
            msg['From'] = formataddr((self.from_name, self.from_address))
            msg['To'] = to_email
            
            # Add text and HTML parts
            if text_body:
                part1 = MIMEText(text_body, 'plain', 'utf-8')
                msg.attach(part1)
            
            part2 = MIMEText(html_body, 'html', 'utf-8')
            msg.attach(part2)
            
            # Send email
            if self.use_ssl:
                server = smtplib.SMTP_SSL(self.smtp_server, self.smtp_port)
            else:
                server = smtplib.SMTP(self.smtp_server, self.smtp_port)
                if self.use_tls:
                    server.starttls()
            
            server.login(self.username, self.password)
            server.sendmail(self.from_address, to_email, msg.as_string())
            server.quit()
            
            logger.info(f"Email sent successfully to {to_email}")
            return True
            
        except Exception as e:
            logger.error(f"Failed to send email to {to_email}: {str(e)}")
            return False
    
    def send_price_alert_email(self, user_email: str, user_name: str, product_name: str, 
                               product_url: str, current_price: float, target_price: float,
                               alert_type: str) -> bool:
        """
        Send a price alert email
        
        Args:
            user_email: User's email address
            user_name: User's name
            product_name: Name of the product
            product_url: URL of the product
            current_price: Current price of the product
            target_price: Target price set by the user
            alert_type: Type of alert (PRICE_DROP, PRICE_INCREASE, STOCK_AVAILABLE)
            
        Returns:
            bool: True if email sent successfully
        """
        subject = f"🔔 Alerte Prix: {product_name}"
        
        # Determine alert message based on type
        if alert_type == 'PRICE_DROP':
            alert_message = f"Le prix a baissé à {current_price}€ (votre cible: {target_price}€)"
            emoji = "📉"
        elif alert_type == 'PRICE_INCREASE':
            alert_message = f"Le prix a augmenté à {current_price}€ (votre cible: {target_price}€)"
            emoji = "📈"
        else:
            alert_message = "Le produit est maintenant disponible!"
            emoji = "✅"
        
        html_body = f"""
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {{
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }}
                .header {{
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                    border-radius: 10px 10px 0 0;
                }}
                .content {{
                    background: #f9f9f9;
                    padding: 30px;
                    border-radius: 0 0 10px 10px;
                }}
                .alert-box {{
                    background: white;
                    border-left: 4px solid #667eea;
                    padding: 20px;
                    margin: 20px 0;
                    border-radius: 5px;
                }}
                .product-name {{
                    font-size: 18px;
                    font-weight: bold;
                    color: #667eea;
                    margin-bottom: 10px;
                }}
                .price {{
                    font-size: 24px;
                    font-weight: bold;
                    color: #28a745;
                    margin: 10px 0;
                }}
                .button {{
                    display: inline-block;
                    background: #667eea;
                    color: white;
                    padding: 12px 30px;
                    text-decoration: none;
                    border-radius: 5px;
                    margin-top: 20px;
                }}
                .footer {{
                    text-align: center;
                    margin-top: 30px;
                    color: #666;
                    font-size: 12px;
                }}
            </style>
        </head>
        <body>
            <div class="header">
                <h1>{emoji} Jungle Alert</h1>
                <p>Votre alerte de prix a été déclenchée!</p>
            </div>
            <div class="content">
                <p>Bonjour {user_name},</p>
                
                <div class="alert-box">
                    <div class="product-name">{product_name}</div>
                    <p>{alert_message}</p>
                    <div class="price">{current_price}€</div>
                </div>
                
                <p>Ne manquez pas cette opportunité!</p>
                
                <a href="{product_url}" class="button">Voir le produit</a>
                
                <div class="footer">
                    <p>Vous recevez cet email car vous avez configuré une alerte pour ce produit.</p>
                    <p>&copy; 2026 Jungle Alert. Tous droits réservés.</p>
                </div>
            </div>
        </body>
        </html>
        """
        
        text_body = f"""
        Jungle Alert - Alerte Prix
        
        Bonjour {user_name},
        
        Votre alerte de prix a été déclenchée!
        
        Produit: {product_name}
        {alert_message}
        Prix actuel: {current_price}€
        
        Voir le produit: {product_url}
        
        Vous recevez cet email car vous avez configuré une alerte pour ce produit.
        © 2026 Jungle Alert. Tous droits réservés.
        """
        
        return self.send_email(user_email, subject, html_body, text_body)
    
    def send_welcome_email(self, user_email: str, user_name: str) -> bool:
        """
        Send a welcome email to new users
        
        Args:
            user_email: User's email address
            user_name: User's name
            
        Returns:
            bool: True if email sent successfully
        """
        subject = "Bienvenue sur Jungle Alert! 🎉"
        
        html_body = f"""
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {{
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }}
                .header {{
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                    border-radius: 10px 10px 0 0;
                }}
                .content {{
                    background: #f9f9f9;
                    padding: 30px;
                    border-radius: 0 0 10px 10px;
                }}
                .feature {{
                    background: white;
                    padding: 15px;
                    margin: 15px 0;
                    border-radius: 5px;
                    border-left: 4px solid #667eea;
                }}
                .footer {{
                    text-align: center;
                    margin-top: 30px;
                    color: #666;
                    font-size: 12px;
                }}
            </style>
        </head>
        <body>
            <div class="header">
                <h1>🎉 Bienvenue sur Jungle Alert!</h1>
            </div>
            <div class="content">
                <p>Bonjour {user_name},</p>
                
                <p>Merci de vous être inscrit sur Jungle Alert! Nous sommes ravis de vous compter parmi nous.</p>
                
                <h3>Que pouvez-vous faire avec Jungle Alert?</h3>
                
                <div class="feature">
                    <strong>📊 Suivre les prix</strong><br>
                    Surveillez l'évolution des prix de vos produits préférés sur Amazon.
                </div>
                
                <div class="feature">
                    <strong>🔔 Recevoir des alertes</strong><br>
                    Soyez notifié instantanément lorsque le prix atteint votre cible.
                </div>
                
                <div class="feature">
                    <strong>💰 Économiser</strong><br>
                    Achetez au meilleur moment et économisez sur vos achats.
                </div>
                
                <p>Commencez dès maintenant à ajouter vos premiers produits!</p>
                
                <div class="footer">
                    <p>&copy; 2026 Jungle Alert. Tous droits réservés.</p>
                </div>
            </div>
        </body>
        </html>
        """
        
        text_body = f"""
        Bienvenue sur Jungle Alert!
        
        Bonjour {user_name},
        
        Merci de vous être inscrit sur Jungle Alert! Nous sommes ravis de vous compter parmi nous.
        
        Que pouvez-vous faire avec Jungle Alert?
        
        - Suivre les prix: Surveillez l'évolution des prix de vos produits préférés sur Amazon.
        - Recevoir des alertes: Soyez notifié instantanément lorsque le prix atteint votre cible.
        - Économiser: Achetez au meilleur moment et économisez sur vos achats.
        
        Commencez dès maintenant à ajouter vos premiers produits!
        
        © 2026 Jungle Alert. Tous droits réservés.
        """
        
        return self.send_email(user_email, subject, html_body, text_body)

