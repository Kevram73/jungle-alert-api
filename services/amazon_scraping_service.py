import re
import time
import random
import logging
from urllib.parse import urlparse
from typing import Optional, Dict, List
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.common.exceptions import TimeoutException, NoSuchElementException, WebDriverException
from webdriver_manager.chrome import ChromeDriverManager
from bs4 import BeautifulSoup
# from flask import current_app  # Non utilisé pour l'instant

logger = logging.getLogger(__name__)

class AmazonScrapingService:
    """Service for scraping Amazon product data using Selenium"""
    
    USER_AGENTS = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:132.0) Gecko/20100101 Firefox/132.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.1 Safari/605.1.15',
    ]
    
    def __init__(self):
        # Utiliser les variables d'environnement ou des valeurs par défaut
        import os
        self.headless = os.getenv('SELENIUM_HEADLESS', 'True').lower() == 'true'
        self.timeout = int(os.getenv('SELENIUM_TIMEOUT', '30'))
        self.delay_min = int(os.getenv('SCRAPING_DELAY_MIN', '1'))
        self.delay_max = int(os.getenv('SCRAPING_DELAY_MAX', '3'))
    
    def _get_driver(self):
        """Create and configure Chrome driver"""
        chrome_options = Options()
        
        if self.headless:
            chrome_options.add_argument('--headless')
        
        chrome_options.add_argument('--no-sandbox')
        chrome_options.add_argument('--disable-dev-shm-usage')
        chrome_options.add_argument('--disable-blink-features=AutomationControlled')
        chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
        chrome_options.add_experimental_option('useAutomationExtension', False)
        
        user_agent = random.choice(self.USER_AGENTS)
        chrome_options.add_argument(f'user-agent={user_agent}')
        
        chrome_options.add_argument('--window-size=1920,1080')
        chrome_options.add_argument('--disable-gpu')
        chrome_options.add_argument('--lang=en-US,en;q=0.9')
        
        try:
            import os
            import glob
            import subprocess
            
            # Essayer d'abord d'utiliser le chromedriver qui vient avec Chrome
            chrome_driver_paths = [
                '/usr/bin/chromedriver',
                '/usr/local/bin/chromedriver',
                '/opt/google/chrome/chromedriver',
            ]
            
            driver_path = None
            for path in chrome_driver_paths:
                if os.path.exists(path) and os.access(path, os.X_OK):
                    driver_path = path
                    logger.info(f"Using system ChromeDriver at: {driver_path}")
                    break
            
            # Si pas trouvé, utiliser ChromeDriverManager mais chercher le bon fichier
            if not driver_path:
                driver_manager = ChromeDriverManager()
                manager_path = driver_manager.install()
                
                # Chercher récursivement le vrai binaire chromedriver
                base_dir = manager_path if os.path.isdir(manager_path) else os.path.dirname(manager_path)
                
                # Chercher tous les fichiers chromedriver
                for root, dirs, files in os.walk(base_dir):
                    for file in files:
                        if file == 'chromedriver':
                            candidate = os.path.join(root, file)
                            # Exclure les fichiers THIRD_PARTY et vérifier la taille (binaires > 1MB)
                            if 'THIRD_PARTY' not in candidate and 'NOTICES' not in candidate:
                                try:
                                    size = os.path.getsize(candidate)
                                    if size > 1000000:  # Binaire > 1MB
                                        driver_path = candidate
                                        # Rendre exécutable
                                        os.chmod(driver_path, 0o755)
                                        logger.info(f"Found ChromeDriver at: {driver_path} (size: {size} bytes)")
                                        break
                                except:
                                    pass
                    if driver_path:
                        break
                
                # Si toujours pas trouvé, essayer avec glob
                if not driver_path:
                    pattern = os.path.join(base_dir, '**', 'chromedriver')
                    for match in glob.glob(pattern, recursive=True):
                        if 'THIRD_PARTY' not in match and 'NOTICES' not in match:
                            try:
                                if os.path.getsize(match) > 1000000:
                                    driver_path = match
                                    os.chmod(driver_path, 0o755)
                                    break
                            except:
                                pass
            
            if not driver_path or not os.path.exists(driver_path):
                raise Exception(f"ChromeDriver binary not found. Please install ChromeDriver manually.")
            
            logger.info(f"Using ChromeDriver at: {driver_path}")
            service = Service(driver_path)
            driver = webdriver.Chrome(service=service, options=chrome_options)
            
            # Execute script to hide webdriver property
            driver.execute_cdp_cmd('Page.addScriptToEvaluateOnNewDocument', {
                'source': '''
                    Object.defineProperty(navigator, 'webdriver', {
                        get: () => undefined
                    })
                '''
            })
            
            return driver
        except Exception as e:
            logger.error(f"Failed to create Chrome driver: {e}")
            raise
    
    def scrape_product(self, url: str, use_cache: bool = True) -> Dict:
        """Scrape product data from Amazon URL"""
        try:
            url = self.normalize_amazon_url(url)
            
            if self.is_short_url(url):
                resolved_url = self.resolve_short_url(url)
                if resolved_url:
                    url = self.normalize_amazon_url(resolved_url)
            
            asin = self.extract_asin_from_url(url)
            if not asin:
                raise Exception('Could not extract ASIN from URL')
            
            marketplace = self.extract_marketplace_from_url(url)
            country = self.get_country_from_marketplace(marketplace)
            
            logger.info(f"🎯 Scraping ASIN: {asin}, Marketplace: {marketplace}")
            
            # Add random delay before scraping
            delay = random.uniform(self.delay_min, self.delay_max)
            time.sleep(delay)
            
            html = self.fetch_product_page(url)
            
            product_data = self.extract_all_product_data(html, url, asin, marketplace, country)
            
            if not product_data.get('image_url') and product_data.get('images'):
                product_data['image_url'] = product_data['images'][0] if product_data['images'] else None
            
            if not self.is_valid_product_data(product_data):
                raise Exception('Scraped data is incomplete or invalid')
            
            logger.info(f"✅ Success: {product_data.get('title', '')[:60]}")
            
            return {
                'success': True,
                'data': product_data,
                'cached': False,
            }
            
        except Exception as e:
            logger.error(f"❌ Scraping error: {e}", exc_info=True)
            return {
                'success': False,
                'error': str(e),
                'url': url,
            }
    
    def fetch_product_page(self, url: str) -> str:
        """Fetch product page HTML using Selenium"""
        driver = None
        try:
            driver = self._get_driver()
            
            # Set additional headers
            driver.execute_cdp_cmd('Network.setUserAgentOverride', {
                "userAgent": random.choice(self.USER_AGENTS)
            })
            
            # Navigate to page
            driver.get(url)
            
            # Wait for page to load
            try:
                WebDriverWait(driver, self.timeout).until(
                    EC.presence_of_element_located((By.TAG_NAME, "body"))
                )
            except TimeoutException:
                raise Exception("Page load timeout")
            
            # Check for captcha
            if self.is_captcha_page(driver.page_source):
                raise Exception('Amazon detected automated request (captcha). Please try again later.')
            
            # Scroll to load dynamic content
            driver.execute_script("window.scrollTo(0, document.body.scrollHeight/2);")
            time.sleep(1)
            
            html = driver.page_source
            return html
            
        finally:
            if driver:
                driver.quit()
    
    def scrape_product_with_retry(self, url: str, max_retries: int = 2) -> Dict:
        """Scrape product with retry logic"""
        last_error = None
        
        for attempt in range(1, max_retries + 1):
            logger.info(f"🔄 Attempt {attempt}/{max_retries}")
            
            result = self.scrape_product(url, use_cache=(attempt == 1))
            
            if result['success']:
                return result
            
            last_error = result.get('error', 'Unknown error')
            
            if attempt < max_retries:
                wait_seconds = 30 + random.randint(0, 30)
                logger.warning(f"⏳ Waiting {wait_seconds}s before retry")
                time.sleep(wait_seconds)
        
        return {
            'success': False,
            'error': f"Failed after {max_retries} attempts: {last_error}",
            'url': url,
        }
    
    def normalize_amazon_url(self, url: str) -> str:
        """Normalize Amazon URL to standard format"""
        asin = self.extract_asin_from_url(url)
        if not asin:
            return url
        
        base_url = self.get_amazon_base_url(url)
        return f"{base_url}/dp/{asin}"
    
    def get_amazon_base_url(self, url: Optional[str] = None) -> str:
        """Get Amazon base URL from URL or default"""
        if url:
            parsed = urlparse(url)
            host = parsed.netloc.lower().replace('m.amazon', 'www.amazon')
            
            domains = {
                'amazon.fr': 'https://www.amazon.fr',
                'amazon.de': 'https://www.amazon.de',
                'amazon.co.uk': 'https://www.amazon.co.uk',
                'amazon.it': 'https://www.amazon.it',
                'amazon.es': 'https://www.amazon.es',
                'amazon.com.br': 'https://www.amazon.com.br',
                'amazon.in': 'https://www.amazon.in',
                'amazon.ca': 'https://www.amazon.ca',
                'amazon.com': 'https://www.amazon.com',
            }
            
            for domain, base in domains.items():
                if domain in host:
                    return base
        
        return 'https://www.amazon.com'
    
    def extract_asin_from_url(self, url: str) -> Optional[str]:
        """Extract ASIN from Amazon URL"""
        patterns = [
            r'/dp/([A-Z0-9]{10})',
            r'/product/([A-Z0-9]{10})',
            r'/gp/product/([A-Z0-9]{10})',
            r'/gp/aw/d/([A-Z0-9]{10})',
            r'/aw/d/([A-Z0-9]{10})',
            r'/d/([A-Z0-9]{10})',
        ]
        
        for pattern in patterns:
            match = re.search(pattern, url)
            if match:
                return match.group(1)
        
        return None
    
    def extract_marketplace_from_url(self, url: str) -> str:
        """Extract marketplace code from URL"""
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
        if 'amzn.eu' in host:
            return 'EU'
        if 'amazon.com.br' in host or 'amzn.com.br' in host:
            return 'BR'
        if 'amazon.in' in host or 'amzn.in' in host:
            return 'IN'
        if 'amazon.ca' in host or 'amzn.ca' in host:
            return 'CA'
        
        return 'US'
    
    def get_country_from_marketplace(self, marketplace: str) -> str:
        """Get country name from marketplace code"""
        mapping = {
            'FR': 'France',
            'DE': 'Germany',
            'UK': 'United Kingdom',
            'IT': 'Italy',
            'ES': 'Spain',
            'EU': 'Europe',
            'BR': 'Brazil',
            'IN': 'India',
            'CA': 'Canada',
        }
        return mapping.get(marketplace, 'United States')
    
    def get_currency_from_marketplace(self, marketplace: str) -> str:
        """Get currency code from marketplace"""
        mapping = {
            'FR': 'EUR', 'DE': 'EUR', 'IT': 'EUR', 'ES': 'EUR', 'EU': 'EUR',
            'UK': 'GBP',
            'CA': 'CAD',
            'BR': 'BRL',
            'IN': 'INR',
        }
        return mapping.get(marketplace, 'USD')
    
    def is_short_url(self, url: str) -> bool:
        """Check if URL is a short Amazon URL"""
        return any(domain in url for domain in ['amzn.to', 'amzn.eu', 'a.co'])
    
    def resolve_short_url(self, short_url: str) -> Optional[str]:
        """Resolve short Amazon URL to full URL"""
        driver = None
        try:
            driver = self._get_driver()
            driver.get(short_url)
            time.sleep(2)
            return driver.current_url
        except Exception as e:
            logger.warning(f"Short URL resolution failed: {e}")
            return None
        finally:
            if driver:
                driver.quit()
    
    def is_captcha_page(self, html: str) -> bool:
        """Check if page is a captcha page"""
        indicators = ['captcha', 'robot check', 'automated access', 'unusual traffic']
        html_lower = html.lower()
        return any(indicator in html_lower for indicator in indicators)
    
    def is_valid_product_data(self, data: Dict) -> bool:
        """Validate scraped product data"""
        return bool(data.get('title') and data.get('asin'))
    
    def extract_all_product_data(self, html: str, url: str, asin: str, marketplace: str, country: str) -> Dict:
        """Extract all product data from HTML"""
        soup = BeautifulSoup(html, 'lxml')
        
        return {
            'asin': asin,
            'amazon_url': url,
            'marketplace': marketplace,
            'country': country,
            'title': self.extract_title(soup),
            'name': self.extract_title(soup),
            'price': self.extract_price(soup, marketplace),
            'current_price': self.extract_price(soup, marketplace),
            'original_price': self.extract_original_price(soup),
            'currency': self.get_currency_from_marketplace(marketplace),
            'discount_percentage': self.extract_discount_percentage(soup),
            'availability': self.extract_availability(soup),
            'in_stock': self.is_in_stock(soup),
            'stock_quantity': self.extract_stock_quantity(soup),
            'image_url': self.extract_image_url(soup),
            'images': self.extract_all_images(soup),
            'description': self.extract_description(soup),
            'features': self.extract_features(soup),
            'rating': self.extract_rating(soup),
            'rating_count': self.extract_rating_count(soup),
            'review_count': self.extract_review_count(soup),
            'categories': self.extract_categories(soup),
            'brand': self.extract_brand(soup),
            'specifications': self.extract_specifications(soup),
            'prime_eligible': self.is_prime_eligible(soup),
            'seller': self.extract_seller(soup),
        }
    
    def extract_title(self, soup: BeautifulSoup) -> Optional[str]:
        """Extract product title"""
        selectors = [
            {'id': 'productTitle'},
            {'id': 'title'},
            {'class': 'product-title'},
        ]
        
        for selector in selectors:
            element = soup.find(**selector)
            if element:
                title = element.get_text(strip=True)
                if title:
                    return title
        
        # Fallback to meta title
        title_tag = soup.find('title')
        if title_tag:
            title = title_tag.get_text(strip=True)
            if 'Amazon' in title:
                title = title.split(':')[0].strip()
            return title
        
        return None
    
    def extract_price(self, soup: BeautifulSoup, marketplace: str) -> Optional[float]:
        """Extract product price"""
        # Try multiple selectors
        price_selectors = [
            {'class': 'a-price-whole'},
            {'id': 'priceblock_ourprice'},
            {'class': 'a-offscreen'},
            {'class': 'a-price'},
        ]
        
        for selector in price_selectors:
            elements = soup.find_all(**selector)
            for element in elements:
                price_text = element.get_text(strip=True)
                price = self.parse_price_string(price_text, marketplace)
                if price and price > 0:
                    return price
        
        return None
    
    def parse_price_string(self, price_string: str, marketplace: str) -> Optional[float]:
        """Parse price string to float"""
        if not price_string:
            return None
        
        # Clean the string
        price_string = re.sub(r'\s+', '', price_string)
        price_string = price_string.strip()
        
        # Remove trailing commas/dots
        price_string = price_string.rstrip(',.')
        
        # Handle European format (1.234,56 or 39,00)
        if marketplace in ['FR', 'DE', 'IT', 'ES', 'EU']:
            # Check if it's European format (comma as decimal separator)
            if ',' in price_string:
                # Remove thousand separators (dots)
                price_string = price_string.replace('.', '')
                # Replace comma with dot
                price_string = price_string.replace(',', '.')
            else:
                # No comma, might be dot as decimal or no decimal
                # If there are multiple dots, it's likely thousand separators
                if price_string.count('.') > 1:
                    price_string = price_string.replace('.', '')
        
        # Handle US format (1,234.56 or 39.00)
        else:
            # Remove thousand separators (commas)
            price_string = price_string.replace(',', '')
        
        # Extract only digits and dots
        price_string = re.sub(r'[^\d.]', '', price_string)
        
        try:
            return float(price_string) if price_string else None
        except ValueError:
            return None
    
    def extract_original_price(self, soup: BeautifulSoup) -> Optional[float]:
        """Extract original price (before discount)"""
        selectors = [
            {'class': 'a-text-price'},
            {'class': 'a-text-strike'},
        ]
        
        for selector in selectors:
            element = soup.find(**selector)
            if element:
                price_text = element.get_text(strip=True)
                price = self.parse_price_string(price_text, 'US')
                if price and price > 0:
                    return price
        
        return None
    
    def extract_discount_percentage(self, soup: BeautifulSoup) -> Optional[int]:
        """Extract discount percentage"""
        text = soup.get_text()
        match = re.search(r'-(\d+)%', text)
        if match:
            return int(match.group(1))
        return None
    
    def extract_availability(self, soup: BeautifulSoup) -> str:
        """Extract availability status"""
        selectors = [
            {'id': 'availability'},
            {'class': 'a-color-success'},
        ]
        
        for selector in selectors:
            element = soup.find(**selector)
            if element:
                availability = element.get_text(strip=True)
                if availability:
                    return availability
        
        return 'Unknown'
    
    def is_in_stock(self, soup: BeautifulSoup) -> bool:
        """Check if product is in stock"""
        text = soup.get_text().lower()
        
        out_of_stock_indicators = [
            'currently unavailable',
            'out of stock',
            'temporairement en rupture',
            'derzeit nicht verfügbar',
        ]
        
        for indicator in out_of_stock_indicators:
            if indicator in text:
                return False
        
        in_stock_indicators = [
            'in stock',
            'en stock',
            'auf lager',
            'disponibile',
            'add to cart',
        ]
        
        for indicator in in_stock_indicators:
            if indicator in text:
                return True
        
        return False
    
    def extract_stock_quantity(self, soup: BeautifulSoup) -> Optional[int]:
        """Extract stock quantity"""
        text = soup.get_text()
        match = re.search(r'Only\s+(\d+)\s+left\s+in\s+stock', text, re.IGNORECASE)
        if match:
            return int(match.group(1))
        return None
    
    def extract_image_url(self, soup: BeautifulSoup) -> Optional[str]:
        """Extract main product image URL"""
        # Try landing image
        img = soup.find('img', {'id': 'landingImage'})
        if img and img.get('src'):
            src = img.get('src')
            if 'data:image' not in src:
                return src
        
        # Try data attributes
        img = soup.find('img', {'data-a-image-name': 'landingImage'})
        if img and img.get('src'):
            return img.get('src')
        
        return None
    
    def extract_all_images(self, soup: BeautifulSoup) -> List[str]:
        """Extract all product images"""
        images = []
        
        # Try to find image data in script tags
        scripts = soup.find_all('script', type='text/javascript')
        for script in scripts:
            if script.string and 'colorImages' in script.string:
                # Try to extract JSON data
                match = re.search(r'"colorImages":\s*\{[^}]*"initial":\s*(\[[^\]]+\])', script.string)
                if match:
                    try:
                        import json
                        json_data = json.loads(match.group(1))
                        for img in json_data:
                            if isinstance(img, dict):
                                if 'hiRes' in img:
                                    images.append(img['hiRes'])
                                elif 'large' in img:
                                    images.append(img['large'])
                    except:
                        pass
        
        # Fallback: find all product images
        imgs = soup.find_all('img', {'class': re.compile(r'.*product-image.*')})
        for img in imgs:
            src = img.get('src') or img.get('data-src')
            if src and 'data:image' not in src:
                images.append(src)
        
        return list(set(images))  # Remove duplicates
    
    def extract_description(self, soup: BeautifulSoup) -> Optional[str]:
        """Extract product description"""
        selectors = [
            {'id': 'feature-bullets'},
            {'id': 'productDescription'},
        ]
        
        for selector in selectors:
            element = soup.find(**selector)
            if element:
                description = element.get_text(strip=True)
                if description:
                    return description
        
        return None
    
    def extract_features(self, soup: BeautifulSoup) -> List[str]:
        """Extract product features"""
        features = []
        element = soup.find('div', {'id': 'feature-bullets'})
        
        if element:
            items = element.find_all('li')
            for item in items:
                feature = item.get_text(strip=True)
                if feature:
                    features.append(feature)
        
        return features
    
    def extract_rating(self, soup: BeautifulSoup) -> Optional[float]:
        """Extract product rating"""
        # Try multiple selectors
        selectors = [
            {'id': 'acrPopover'},
            {'class': 'a-icon-alt'},
        ]
        
        for selector in selectors:
            element = soup.find(**selector)
            if element:
                text = element.get_text(strip=True)
                match = re.search(r'([\d.,]+)\s+out\s+of\s+5', text, re.IGNORECASE)
                if match:
                    try:
                        return float(match.group(1).replace(',', '.'))
                    except:
                        pass
        
        return None
    
    def extract_rating_count(self, soup: BeautifulSoup) -> Optional[int]:
        """Extract rating count"""
        element = soup.find('span', {'id': 'acrCustomerReviewText'})
        if element:
            text = element.get_text(strip=True)
            match = re.search(r'([\d,.\s]+)', text)
            if match:
                count_str = re.sub(r'[^\d]', '', match.group(1))
                try:
                    return int(count_str)
                except:
                    pass
        return None
    
    def extract_review_count(self, soup: BeautifulSoup) -> Optional[int]:
        """Extract review count"""
        text = soup.get_text()
        match = re.search(r'(\d+)\s+customer\s+reviews?', text, re.IGNORECASE)
        if match:
            return int(match.group(1))
        return None
    
    def extract_categories(self, soup: BeautifulSoup) -> List[str]:
        """Extract product categories"""
        categories = []
        element = soup.find('div', {'id': 'wayfinding-breadcrumbs_feature_div'})
        
        if element:
            links = element.find_all('a')
            for link in links:
                category = link.get_text(strip=True)
                if category:
                    categories.append(category)
        
        return categories
    
    def extract_brand(self, soup: BeautifulSoup) -> Optional[str]:
        """Extract product brand"""
        element = soup.find('a', {'id': 'bylineInfo'})
        if element:
            brand = element.get_text(strip=True)
            brand = re.sub(r'^(Brand:\s*|Marque\s*:\s*)', '', brand, flags=re.IGNORECASE)
            if brand:
                return brand
        return None
    
    def extract_specifications(self, soup: BeautifulSoup) -> Dict[str, str]:
        """Extract product specifications"""
        specs = {}
        table = soup.find('table', {'id': 'productDetails_techSpec_section_1'})
        
        if table:
            rows = table.find_all('tr')
            for row in rows:
                th = row.find('th')
                td = row.find('td')
                if th and td:
                    key = th.get_text(strip=True)
                    value = td.get_text(strip=True)
                    if key and value:
                        specs[key] = value
        
        return specs
    
    def extract_seller(self, soup: BeautifulSoup) -> Optional[str]:
        """Extract seller information"""
        selectors = [
            {'id': 'merchant-info'},
            {'class': 'seller'},
        ]
        
        for selector in selectors:
            element = soup.find(**selector)
            if element:
                seller = element.get_text(strip=True)
                if seller:
                    return seller
        
        return None
    
    def is_prime_eligible(self, soup: BeautifulSoup) -> bool:
        """Check if product is Prime eligible"""
        return bool(soup.find('i', {'class': re.compile(r'.*a-icon-prime.*')}))
    
    def validate_amazon_url(self, url: str) -> bool:
        """Validate if URL is an Amazon URL"""
        amazon_domains = [
            'amazon.com', 'amazon.de', 'amazon.co.uk', 'amazon.fr',
            'amazon.it', 'amazon.es', 'amazon.com.br', 'amazon.in',
            'amazon.ca', 'a.co', 'amzn.to', 'amzn.eu', 'amzn.com',
            'amzn.co.uk', 'amzn.de', 'amzn.fr', 'amzn.it', 'amzn.es',
            'amzn.com.br', 'amzn.in', 'amzn.ca',
        ]
        
        parsed = urlparse(url)
        host = parsed.netloc.lower()
        
        return any(domain in host for domain in amazon_domains)

