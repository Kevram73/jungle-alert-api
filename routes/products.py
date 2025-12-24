from flask import Blueprint, request, jsonify, current_app
from flask_jwt_extended import jwt_required, get_jwt_identity
from extensions import db
from models.user import User
from models.product import Product
from models.alert import Alert
from models.price_history import PriceHistory
from services.amazon_scraping_service import AmazonScrapingService
from services.notification_service import NotificationService
from datetime import datetime
from sqlalchemy.exc import IntegrityError
import time
import random

products_bp = Blueprint('products', __name__)
scraping_service = AmazonScrapingService()
notification_service = NotificationService()

def get_current_user_id():
    """Get current user ID from JWT token and verify user exists
    Returns: (user_id, error_response, status_code)
    If successful: (user_id, None, None)
    If error: (None, jsonify_response, status_code)
    """
    user_id_str = get_jwt_identity()
    try:
        user_id = int(user_id_str)
    except (ValueError, TypeError):
        return None, jsonify({'message': 'Invalid user identity'}), 401
    
    user = User.query.get(user_id)
    if not user:
        return None, jsonify({'message': 'User not found'}), 404
    
    return user_id, None, None

def extract_marketplace_from_url(url):
    """Extract marketplace from URL"""
    from urllib.parse import urlparse
    parsed = urlparse(url)
    host = parsed.netloc.lower()
    
    if 'amazon.fr' in host or 'amzn.fr' in host:
        return 'FR'
    if 'amazon.de' in host or 'amzn.de' in host:
        return 'DE'
    if 'amazon.co.uk' in host or 'amzn.co.uk' in host:
        return 'UK'
    if 'amazon.it' in host or 'amzn.it' in host:
        return 'IT'
    if 'amazon.es' in host or 'amzn.es' in host:
        return 'ES'
    if 'amazon.com.br' in host or 'amzn.com.br' in host:
        return 'BR'
    if 'amazon.in' in host or 'amzn.in' in host:
        return 'IN'
    if 'amazon.ca' in host or 'amzn.ca' in host:
        return 'CA'
    
    return 'US'

def currency_for_marketplace(marketplace):
    """Get currency for marketplace"""
    mapping = {
        'FR': 'EUR', 'DE': 'EUR', 'ES': 'EUR', 'IT': 'EUR', 'EU': 'EUR',
        'UK': 'GBP',
        'CA': 'CAD',
        'BR': 'BRL',
        'IN': 'INR',
    }
    return mapping.get(marketplace, 'USD')

@products_bp.route('/products', methods=['GET'])
@jwt_required()
def index():
    """Get user's products"""
    user_id, error_response, status_code = get_current_user_id()
    if error_response:
        return error_response, status_code
    
    page = request.args.get('page', 1, type=int)
    per_page = request.args.get('per_page', 20, type=int)
    
    products = Product.query.filter_by(user_id=user_id)\
        .order_by(Product.created_at.desc())\
        .paginate(page=page, per_page=per_page, error_out=False)
    
    return jsonify({
        'message': 'Products retrieved successfully',
        'products': {
            'data': [p.to_dict(include_relations=True) for p in products.items],
            'current_page': products.page,
            'per_page': products.per_page,
            'total': products.total,
            'pages': products.pages,
        }
    }), 200

@products_bp.route('/products', methods=['POST'])
@jwt_required()
def store():
    """Create a new product"""
    user_id, error_response, status_code = get_current_user_id()
    if error_response:
        return error_response, status_code
    
    data = request.get_json()
    
    if not data.get('amazon_url'):
        return jsonify({'message': 'Amazon URL is required'}), 422
    
    # Validate Amazon URL
    if not scraping_service.validate_amazon_url(data['amazon_url']):
        return jsonify({
            'message': 'Invalid Amazon URL',
            'errors': {'amazon_url': ['Please provide a valid Amazon product URL']}
        }), 422
    
    # Resolve short URL if needed
    url = data['amazon_url']
    original_url = url
    if any(domain in url for domain in ['a.co', 'amzn.eu', 'amzn.to', 'amzn.com']):
        resolved = scraping_service.resolve_short_url(url)
        if resolved:
            url = resolved
            print(f'✅ [PRODUCTS] URL raccourcie résolue: {original_url} -> {url}')
        else:
            print(f'⚠️ [PRODUCTS] Impossible de résoudre l\'URL raccourcie: {original_url}')
    
    # Check if product already exists (utiliser l'URL résolue si disponible)
    existing = Product.query.filter_by(user_id=user_id, amazon_url=url).first()
    if not existing and url != original_url:
        # Aussi vérifier avec l'URL originale
        existing = Product.query.filter_by(user_id=user_id, amazon_url=original_url).first()
    
    if existing:
        return jsonify({
            'message': 'Product already exists',
            'product': existing.to_dict(include_relations=True)
        }), 409
    
    # Extract marketplace and currency (toujours depuis l'URL résolue si disponible)
    marketplace = extract_marketplace_from_url(url)
    # Si le marketplace est 'US' mais que l'URL est amzn.eu, essayer de détecter depuis les données scrapées
    if marketplace == 'US' and 'amzn.eu' in original_url.lower():
        print(f'⚠️ [PRODUCTS] Marketplace non détecté pour amzn.eu, sera détecté lors du scraping')
        # On laissera le scraping détecter le marketplace
    currency = currency_for_marketplace(marketplace)
    
    # Scrape product data (utiliser l'URL résolue si disponible)
    scrape_data = data.get('scrape_data', True)
    product_data = {
        'user_id': user_id,
        'amazon_url': url,  # Utiliser l'URL résolue
        'target_price': data.get('target_price'),
        'is_active': True,
        'marketplace': marketplace,
        'currency': currency,
    }
    
    if scrape_data:
        # Utiliser l'URL résolue pour le scraping
        scrape_url = url if url != original_url else data['amazon_url']
        scraped = scraping_service.scrape_product_with_retry(scrape_url)
        
        if scraped['success']:
            data_dict = scraped['data']
            
            # Si le marketplace était 'US' mais qu'on a des données scrapées, essayer de détecter depuis l'URL scrapée
            if marketplace == 'US' and 'amzn.eu' in original_url.lower():
                # Les données scrapées peuvent contenir l'URL finale
                scraped_url = data_dict.get('amazon_url') or scrape_url
                detected_marketplace = extract_marketplace_from_url(scraped_url)
                if detected_marketplace != 'US':
                    marketplace = detected_marketplace
                    currency = currency_for_marketplace(marketplace)
                    product_data['marketplace'] = marketplace
                    product_data['currency'] = currency
                    print(f'✅ [PRODUCTS] Marketplace détecté depuis les données scrapées: {marketplace}')
            
            product_data.update({
                'title': data_dict.get('title') or data_dict.get('name') or 'Product from Amazon',
                'description': data_dict.get('description'),
                'image_url': data_dict.get('image_url'),
                'current_price': data_dict.get('price') or data_dict.get('current_price') or 0,
                'original_price': data_dict.get('original_price'),
                'asin': data_dict.get('asin'),
                'availability': data_dict.get('availability'),
                'rating': data_dict.get('rating'),
                'review_count': data_dict.get('review_count'),
                'category': data_dict.get('category'),
                'stock_quantity': data_dict.get('stock_quantity'),
                'stock_status': data_dict.get('stock_status'),
                'brand': data_dict.get('brand'),
                'seller': data_dict.get('seller'),
                'is_prime': data_dict.get('prime_eligible', False),
                'discount_percentage': data_dict.get('discount_percentage'),
                'features': data_dict.get('features'),
                'images': data_dict.get('images'),
                # Ne pas écraser la devise et le marketplace - ils sont déjà correctement définis
                # 'currency': currency,  # Déjà défini plus haut
                # 'marketplace': marketplace,  # Déjà défini plus haut
            })
        else:
            # Fallback data
            product_data.update({
                'title': 'Product from Amazon',
                'current_price': 0,
            })
    
    try:
        product = Product(**product_data)
        db.session.add(product)
        db.session.commit()
        
        # Create price history
        if product.current_price and float(product.current_price) > 0:
            ph = PriceHistory(product_id=product.id, price=product.current_price, recorded_at=datetime.utcnow())
            db.session.add(ph)
            db.session.commit()
        
        # Create alerts if target_price provided
        if data.get('target_price') and float(data['target_price']) > 0:
            for alert_type in ['PRICE_DROP', 'PRICE_INCREASE', 'STOCK_AVAILABLE']:
                alert = Alert(
                    user_id=user_id,
                    product_id=product.id,
                    target_price=data['target_price'],
                    alert_type=alert_type,
                    is_active=True
                )
                db.session.add(alert)
            db.session.commit()
        
        return jsonify({
            'message': 'Product created successfully',
            'product': product.to_dict(include_relations=True),
            'scraped': scrape_data
        }), 201
    except IntegrityError as e:
        db.session.rollback()
        error_msg = str(e.orig) if hasattr(e, 'orig') else str(e)
        print(f'❌ [PRODUCTS] IntegrityError: {error_msg}')
        print(f'❌ [PRODUCTS] User ID: {user_id}')
        print(f'❌ [PRODUCTS] User exists: {User.query.get(user_id) is not None}')
        
        if 'foreign key constraint' in error_msg.lower() and 'user_id' in error_msg.lower():
            # Vérifier à nouveau que l'utilisateur existe
            user_check = User.query.get(user_id)
            if not user_check:
                return jsonify({
                    'success': False,
                    'message': 'User not found or invalid user',
                    'error': 'The user associated with this request does not exist in the database. Please log in again.'
                }), 404
            else:
                # L'utilisateur existe, c'est peut-être un autre problème
                return jsonify({
                    'success': False,
                    'message': 'Database constraint error',
                    'error': 'A database constraint was violated. Please try again.'
                }), 400
        
        return jsonify({
            'success': False,
            'message': 'Database error',
            'error': error_msg if current_app.config.get('DEBUG') else 'A database error occurred'
        }), 400
    except Exception as e:
        db.session.rollback()
        import traceback
        print(f'❌ [PRODUCTS] Exception: {str(e)}')
        print(f'❌ [PRODUCTS] Traceback: {traceback.format_exc()}')
        return jsonify({
            'success': False,
            'message': 'Failed to create product',
            'error': str(e) if current_app.config.get('DEBUG') else 'An error occurred'
        }), 500

@products_bp.route('/products/<int:product_id>', methods=['GET'])
@jwt_required()
def show(product_id):
    """Get a specific product"""
    user_id = get_jwt_identity()
    product = Product.query.filter_by(id=product_id, user_id=user_id).first_or_404()
    
    return jsonify({
        'message': 'Product retrieved successfully',
        'product': product.to_dict(include_relations=True)
    }), 200

@products_bp.route('/products/<int:product_id>', methods=['PUT'])
@jwt_required()
def update(product_id):
    """Update a product"""
    user_id = get_jwt_identity()
    product = Product.query.filter_by(id=product_id, user_id=user_id).first_or_404()
    
    data = request.get_json()
    old_price = product.current_price
    
    # Update fields (mais pas marketplace et currency - ils sont gérés séparément)
    for key in ['title', 'description', 'image_url', 'current_price', 'target_price', 
                'is_active', 'availability', 'rating', 'review_count', 'category']:
        if key in data:
            setattr(product, key, data[key])
    
    # Toujours mettre à jour marketplace et currency à partir de l'URL (pas des données envoyées)
    if 'amazon_url' in data:
        product.amazon_url = data['amazon_url']
    
    # Toujours recalculer marketplace et currency à partir de l'URL actuelle
    marketplace = extract_marketplace_from_url(product.amazon_url)
    product.marketplace = marketplace
    product.currency = currency_for_marketplace(marketplace)
    
    product.updated_at = datetime.utcnow()
    db.session.commit()
    
    # Create price history if price changed
    if 'current_price' in data and data['current_price'] != old_price:
        ph = PriceHistory(product_id=product.id, price=data['current_price'], recorded_at=datetime.utcnow())
        db.session.add(ph)
        db.session.commit()
    
    return jsonify({
        'message': 'Product updated successfully',
        'product': product.to_dict(include_relations=True)
    }), 200

@products_bp.route('/products/<int:product_id>', methods=['DELETE'])
@jwt_required()
def destroy(product_id):
    """Delete a product"""
    user_id = get_jwt_identity()
    product = Product.query.filter_by(id=product_id, user_id=user_id).first_or_404()
    
    db.session.delete(product)
    db.session.commit()
    
    return jsonify({'message': 'Product deleted successfully'}), 200

@products_bp.route('/products/<int:product_id>/scrape-update', methods=['POST'])
@jwt_required()
def scrape_and_update(product_id):
    """Scrape and update product data"""
    user_id = get_jwt_identity()
    product = Product.query.filter_by(id=product_id, user_id=user_id).first_or_404()
    
    scraped = scraping_service.scrape_product_with_retry(product.amazon_url)
    
    if not scraped['success']:
        return jsonify({
            'message': 'Failed to scrape product data',
            'error': scraped.get('error', 'Unknown error')
        }), 400
    
    data = scraped['data']
    old_price = product.current_price
    
    # Update product
    product.title = data.get('title') or data.get('name') or product.title
    product.description = data.get('description') or product.description
    product.image_url = data.get('image_url') or product.image_url
    product.current_price = data.get('price') or data.get('current_price') or product.current_price
    product.original_price = data.get('original_price') or product.original_price
    product.availability = data.get('availability') or product.availability
    product.rating = data.get('rating') or product.rating
    product.review_count = data.get('review_count') or product.review_count
    product.brand = data.get('brand') or product.brand
    product.seller = data.get('seller') or product.seller
    product.is_prime = data.get('prime_eligible', False)
    product.discount_percentage = data.get('discount_percentage') or product.discount_percentage
    product.features = data.get('features') or product.features
    product.images = data.get('images') or product.images
    product.last_price_check = datetime.utcnow()
    product.updated_at = datetime.utcnow()
    
    # Toujours mettre à jour marketplace et currency à partir de l'URL (pas des données scrapées)
    marketplace = extract_marketplace_from_url(product.amazon_url)
    product.marketplace = marketplace
    product.currency = currency_for_marketplace(marketplace)
    
    db.session.commit()
    
    # Create price history if price changed
    new_price = data.get('price') or data.get('current_price')
    if new_price and new_price != old_price:
        ph = PriceHistory(product_id=product.id, price=new_price, recorded_at=datetime.utcnow())
        db.session.add(ph)
        db.session.commit()
        
        # Check alerts
        notification_service.check_and_trigger_alerts(product, send_notifications=False)
    
    return jsonify({
        'message': 'Product data updated successfully',
        'product': product.to_dict(include_relations=True),
        'price_change': float(new_price) - float(old_price) if new_price and old_price else 0
    }), 200

@products_bp.route('/products/<int:product_id>/refresh', methods=['POST'])
@jwt_required()
def refresh_product(product_id):
    """Refresh product data (alias for scrape-update)"""
    # Delegate to scrape_and_update function
    return scrape_and_update(product_id)

@products_bp.route('/products/scrape-preview', methods=['POST'])
def scrape_preview():
    """
    Scrape product preview (public endpoint)
    ---
    tags:
      - Products
    parameters:
      - in: body
        name: body
        required: true
        schema:
          type: object
          required:
            - amazon_url
          properties:
            amazon_url:
              type: string
              example: https://amzn.eu/d/bvp7pE1
              description: URL du produit Amazon (supporte les URLs raccourcies)
    responses:
      200:
        description: Product data scraped successfully
        schema:
          type: object
          properties:
            success:
              type: boolean
            message:
              type: string
            data:
              type: object
              properties:
                asin:
                  type: string
                title:
                  type: string
                price:
                  type: number
                currency:
                  type: string
                marketplace:
                  type: string
      400:
        description: Scraping failed
      422:
        description: Invalid Amazon URL
    """
    data = request.get_json()
    
    if not data.get('amazon_url'):
        return jsonify({'message': 'Amazon URL is required'}), 422
    
    if not scraping_service.validate_amazon_url(data['amazon_url']):
        return jsonify({
            'message': 'Invalid Amazon URL',
            'errors': {'amazon_url': ['Please provide a valid Amazon product URL']}
        }), 422
    
    # Résoudre l'URL raccourcie si nécessaire
    url = data['amazon_url']
    if any(domain in url for domain in ['a.co', 'amzn.eu', 'amzn.to', 'amzn.com']):
        resolved = scraping_service.resolve_short_url(url)
        if resolved:
            url = resolved
            print(f'✅ [SCRAPE-PREVIEW] URL raccourcie résolue: {data["amazon_url"]} -> {url}')
    
    scraped = scraping_service.scrape_product_with_retry(url)
    
    if not scraped['success']:
        return jsonify({
            'success': False,
            'message': 'Failed to scrape product data',
            'error': scraped.get('error', 'Unknown error'),
            'data': {
                'message': 'Failed to scrape product data',
                'error': scraped.get('error', 'Unknown error')
            }
        }), 400
    
    raw = scraped['data']
    # Utiliser l'URL résolue ou l'URL depuis les données scrapées pour détecter le marketplace
    final_url = raw.get('amazon_url') or url
    marketplace = extract_marketplace_from_url(final_url)
    # Si toujours 'US' et que c'était une URL raccourcie, essayer depuis l'URL originale résolue
    if marketplace == 'US' and 'amzn.eu' in data['amazon_url'].lower() and url != data['amazon_url']:
        marketplace = extract_marketplace_from_url(url)
    currency = currency_for_marketplace(marketplace)
    price = raw.get('price') or raw.get('current_price')
    
    enriched = {
        'asin': raw.get('asin'),
        'title': raw.get('title') or 'Product from URL',
        'image_url': raw.get('image_url'),
        'availability': raw.get('availability') or 'In Stock',
        'price': price,
        'name': raw.get('title') or 'Product from URL',
        'current_price': price,
        'suggested_price': round(float(price) * 0.8, 2) if price and price > 0 else 0.0,
        'marketplace': marketplace,
        'currency': currency,
        'category': raw.get('category') or 'General',
        'rating': raw.get('rating') or 4.5,
        'review_count': raw.get('review_count') or 100,
    }
    
    return jsonify({
        'success': True,
        'message': 'Product data scraped successfully',
        'data': enriched
    }), 200

@products_bp.route('/products/<int:product_id>/price-history', methods=['GET'])
@jwt_required()
def price_history(product_id):
    """Get price history for a product"""
    user_id = get_jwt_identity()
    product = Product.query.filter_by(id=product_id, user_id=user_id).first_or_404()
    
    days = request.args.get('days', 30, type=int)
    from datetime import timedelta
    cutoff = datetime.utcnow() - timedelta(days=days)
    
    histories = PriceHistory.query.filter_by(product_id=product_id)\
        .filter(PriceHistory.recorded_at >= cutoff)\
        .order_by(PriceHistory.recorded_at.asc())\
        .all()
    
    return jsonify({
        'message': 'Price history retrieved successfully',
        'price_history': [h.to_dict() for h in histories]
    }), 200

@products_bp.route('/products/bulk-update-prices', methods=['POST'])
@jwt_required()
def bulk_update_prices():
    """Bulk update prices for all user products"""
    user_id = get_jwt_identity()
    products = Product.query.filter_by(user_id=user_id, is_active=True).all()
    
    updated = 0
    errors = []
    
    for product in products:
        try:
            # Random delay between products
            time.sleep(random.uniform(2, 4))
            
            scraped = scraping_service.scrape_product_with_retry(product.amazon_url)
            
            if scraped['success'] and scraped['data'].get('price'):
                new_price = scraped['data']['price']
                old_price = product.current_price
                
                if new_price != old_price:
                    product.current_price = new_price
                    product.last_price_check = datetime.utcnow()
                    db.session.commit()
                    
                    # Create price history
                    ph = PriceHistory(product_id=product.id, price=new_price, recorded_at=datetime.utcnow())
                    db.session.add(ph)
                    db.session.commit()
                    
                    # Check alerts
                    notification_service.check_and_trigger_alerts(product, send_notifications=False)
                    
                    updated += 1
        except Exception as e:
            errors.append(f"Product {product.id}: {str(e)}")
    
    return jsonify({
        'message': 'Bulk price update completed',
        'updated_products': updated,
        'total_products': len(products),
        'errors': errors
    }), 200

