from flask import Flask
from flask_cors import CORS
from flasgger import Swagger
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
    
    # Initialize Swagger for API documentation
    swagger_config = {
        "headers": [],
        "specs": [
            {
                "endpoint": "apispec",
                "route": "/api/apispec.json",
                "rule_filter": lambda rule: True,
                "model_filter": lambda tag: True,
            }
        ],
        "static_url_path": "/flasgger_static",
        "swagger_ui": True,
        "specs_route": "/api/docs"
    }
    
    swagger_template = {
        "swagger": "2.0",
        "info": {
            "title": "Jungle Alert API",
            "description": "API REST pour l'application Jungle Alert - Suivi de prix Amazon",
            "version": "1.0.0",
            "contact": {
                "name": "Jungle Alert Support"
            }
        },
        "basePath": "/api/v1",
        "schemes": ["http", "https"],
        "securityDefinitions": {
            "Bearer": {
                "type": "apiKey",
                "name": "Authorization",
                "in": "header",
                "description": "JWT Authorization header using the Bearer scheme. Example: \"Authorization: Bearer {token}\""
            }
        },
        "security": [
            {
                "Bearer": []
            }
        ],
        "tags": [
            {
                "name": "Authentication",
                "description": "Endpoints d'authentification"
            },
            {
                "name": "Products",
                "description": "Gestion des produits Amazon"
            },
            {
                "name": "Alerts",
                "description": "Gestion des alertes de prix"
            },
            {
                "name": "Subscriptions",
                "description": "Gestion des abonnements"
            },
            {
                "name": "Users",
                "description": "Gestion du profil utilisateur"
            },
            {
                "name": "Dashboard",
                "description": "Tableau de bord et statistiques"
            },
            {
                "name": "Affiliate",
                "description": "Liens d'affiliation"
            },
            {
                "name": "Newsletter",
                "description": "Gestion de la newsletter"
            },
            {
                "name": "GDPR",
                "description": "Conformité RGPD"
            }
        ]
    }
    
    Swagger(app, config=swagger_config, template=swagger_template)
    
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

