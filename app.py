from flask import Flask
from flask_cors import CORS
from config import config
from extensions import db, migrate, jwt
import os

def create_app(config_name=None):
    """Application factory"""
    app = Flask(__name__)
    
    # Load configuration
    config_name = config_name or os.getenv('FLASK_ENV', 'development')
    app.config.from_object(config[config_name])
    
    # Initialize extensions
    db.init_app(app)
    migrate.init_app(app, db)
    jwt.init_app(app)
    CORS(app, origins=app.config['CORS_ORIGINS'], supports_credentials=True)
    
    # Import models to register them with SQLAlchemy
    from models import User, Product, Alert, Subscription, PriceHistory, AffiliateClick
    
    # Register blueprints
    from routes.auth import auth_bp
    from routes.products import products_bp
    from routes.alerts import alerts_bp
    from routes.subscriptions import subscriptions_bp
    from routes.users import users_bp
    from routes.dashboard import dashboard_bp
    from routes.affiliate import affiliate_bp
    from routes.newsletter import newsletter_bp
    from routes.gdpr import gdpr_bp
    
    app.register_blueprint(auth_bp, url_prefix='/api/v1')
    app.register_blueprint(products_bp, url_prefix='/api/v1')
    app.register_blueprint(alerts_bp, url_prefix='/api/v1')
    app.register_blueprint(subscriptions_bp, url_prefix='/api/v1')
    app.register_blueprint(users_bp, url_prefix='/api/v1')
    app.register_blueprint(dashboard_bp, url_prefix='/api/v1')
    app.register_blueprint(affiliate_bp, url_prefix='/api/v1')
    app.register_blueprint(newsletter_bp, url_prefix='/api/v1')
    app.register_blueprint(gdpr_bp, url_prefix='/api/v1')
    
    # Health check
    @app.route('/api/health')
    def health():
        return {'status': 'healthy', 'message': 'API is running'}, 200
    
    return app

if __name__ == '__main__':
    app = create_app()
    app.run(debug=True, host='0.0.0.0', port=5000)

