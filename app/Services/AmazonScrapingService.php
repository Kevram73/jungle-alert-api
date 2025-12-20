<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Service de scraping Amazon ENRICHI avec support MOBILE
 * Convertit automatiquement les liens mobiles en liens desktop pour avoir plus de données
 * 
 * Extrait TOUTES les informations produit :
 * - Nom complet du produit
 * - Marketplace (pays)
 * - ASIN
 * - Nombre d'étoiles (rating) ⭐
 * - Nombre d'avis (reviews) 💬
 * - Nombre en stock 📦
 * - Statut de disponibilité
 * - Catégories complètes 🏷️
 * - Prix, images, description, etc.
 */
class AmazonScrapingService
{
    private array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:132.0) Gecko/20100101 Firefox/132.0',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.1 Safari/605.1.15',
    ];

    /**
     * Scrape product with ALL enriched data
     * Supporte les liens web ET mobile
     */
    public function scrapeProduct(string $url, bool $useCache = true): array
    {
        try {
            // 🔄 ÉTAPE 1: Normaliser l'URL (mobile → desktop)
            $url = $this->normalizeAmazonUrl($url);
            Log::info("Normalized URL: {$url}");

            // Resolve short URLs
            if ($this->isShortUrl($url)) {
                $resolvedUrl = $this->resolveShortUrl($url);
                if ($resolvedUrl) {
                    $url = $this->normalizeAmazonUrl($resolvedUrl);
                    Log::info("Short URL resolved and normalized: {$url}");
                }
            }

            // Check cache
            if ($useCache) {
                $cacheKey = 'amazon_enriched_' . md5($url);
                $cachedData = Cache::get($cacheKey);
                
                if ($cachedData !== null) {
                    Log::debug("Cache hit: {$url}");
                    return [
                        'success' => true,
                        'data' => $cachedData,
                        'cached' => true,
                    ];
                }
            }

            // Extract ASIN and marketplace
            $asin = $this->extractAsinFromUrl($url);
            if (!$asin) {
                throw new Exception('Could not extract ASIN from URL');
            }

            $marketplace = $this->extractMarketplaceFromUrl($url);
            $country = $this->getCountryFromMarketplace($marketplace);

            Log::info("Scraping: ASIN={$asin}, Marketplace={$marketplace}, Country={$country}");

            // Fetch HTML
            $html = $this->fetchProductPage($url);

            // Extract ALL data
            $productData = $this->extractAllProductData($html, $url, $asin, $marketplace, $country);
            
            // Fallback: Si l'image principale n'est pas trouvée, utiliser la première image de la liste
            if (empty($productData['image_url']) && !empty($productData['images'])) {
                $productData['image_url'] = $productData['images'][0];
                Log::info("Using first image from images array as main image");
            }
            
            // Logging pour déboguer les problèmes d'extraction
            if (empty($productData['price']) && empty($productData['current_price'])) {
                Log::warning("Price extraction failed for ASIN: {$asin}, Marketplace: {$marketplace}");
            }
            if (empty($productData['image_url'])) {
                Log::warning("Image extraction failed for ASIN: {$asin}, Marketplace: {$marketplace}");
            }

            // Validate
            if (!$this->isValidProductData($productData)) {
                throw new Exception('Scraped data is incomplete or invalid');
            }

            // Cache result
            if ($useCache) {
                $cacheKey = 'amazon_enriched_' . md5($url);
                Cache::put($cacheKey, $productData, now()->addMinutes(5));
            }

            Log::info("Successfully scraped: {$productData['title']}");

            return [
                'success' => true,
                'data' => $productData,
                'cached' => false,
            ];

        } catch (Exception $e) {
            Log::error('Scraping error: ' . $e->getMessage() . ' | URL: ' . $url);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'url' => $url,
            ];
        }
    }

    /**
     * Normaliser les URLs Amazon
     * Convertit les liens mobiles en liens desktop pour avoir plus de données
     */
    private function normalizeAmazonUrl(string $url): string
    {
        $asin = $this->extractAsinFromUrl($url);
        if (!$asin) {
            return $url;
        }

        $baseUrl = $this->getAmazonBaseUrl($url);
        $canonicalUrl = "{$baseUrl}/dp/{$asin}";

        Log::debug("URL normalized: {$url} → {$canonicalUrl}");
        return $canonicalUrl;
    }

    /**
     * Get Amazon base URL from marketplace or URL
     */
    private function getAmazonBaseUrl(?string $url = null): string
    {
        if ($url) {
            $host = parse_url($url, PHP_URL_HOST) ?? '';
            $host = strtolower($host);
            $host = str_replace('m.amazon', 'www.amazon', $host);
            
            $domainMap = [
                'amzn.com.br' => 'https://www.amazon.com.br',
                'amzn.co.uk' => 'https://www.amazon.co.uk',
                'amzn.de' => 'https://www.amazon.de',
                'amzn.fr' => 'https://www.amazon.fr',
                'amzn.it' => 'https://www.amazon.it',
                'amzn.es' => 'https://www.amazon.es',
                'amzn.in' => 'https://www.amazon.in',
                'amzn.ca' => 'https://www.amazon.ca',
                'amzn.eu' => 'https://www.amazon.eu',
                'amazon.com.br' => 'https://www.amazon.com.br',
                'amazon.co.uk' => 'https://www.amazon.co.uk',
                'amazon.de' => 'https://www.amazon.de',
                'amazon.fr' => 'https://www.amazon.fr',
                'amazon.it' => 'https://www.amazon.it',
                'amazon.es' => 'https://www.amazon.es',
                'amazon.in' => 'https://www.amazon.in',
                'amazon.ca' => 'https://www.amazon.ca',
                'amazon.eu' => 'https://www.amazon.eu',
                'amazon.com' => 'https://www.amazon.com',
            ];

            foreach ($domainMap as $domain => $baseUrl) {
                if (str_contains($host, $domain)) {
                    return $baseUrl;
                }
            }
        }
        
        return 'https://www.amazon.com';
    }

    /**
     * Extract ASIN from URL (supporte mobile et desktop)
     */
    private function extractAsinFromUrl(string $url): ?string
    {
        $patterns = [
            '/\/dp\/([A-Z0-9]{10})/',
            '/\/product\/([A-Z0-9]{10})/',
            '/\/gp\/product\/([A-Z0-9]{10})/',
            '/\/gp\/aw\/d\/([A-Z0-9]{10})/',
            '/\/aw\/d\/([A-Z0-9]{10})/',
            '/\/[^\/]*\/([A-Z0-9]{10})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Extract ALL product data from HTML
     */
    private function extractAllProductData(string $html, string $url, string $asin, string $marketplace, string $country): array
    {
        return [
            'asin' => $asin,
            'amazon_url' => $url,
            'marketplace' => $marketplace,
            'country' => $country,
            'title' => $this->extractTitle($html),
            'name' => $this->extractTitle($html),
            'price' => $this->extractPrice($html, $url),
            'current_price' => $this->extractPrice($html, $url),
            'original_price' => $this->extractOriginalPrice($html),
            'currency' => $this->getCurrencyFromMarketplace($marketplace),
            'discount_percentage' => $this->calculateDiscount(
                $this->extractOriginalPrice($html),
                $this->extractPrice($html, $url)
            ),
            'rating' => $this->extractRating($html),
            'stars' => $this->extractRating($html),
            'review_count' => $this->extractReviewCount($html),
            'number_of_reviews' => $this->extractReviewCount($html),
            'availability' => $this->extractAvailability($html),
            'in_stock' => $this->isInStock($html),
            'stock_quantity' => $this->extractStockQuantity($html),
            'stock_status' => $this->getStockStatus($html),
            'category' => $this->extractMainCategory($html),
            'categories' => $this->extractAllCategories($html),
            'category_path' => $this->extractCategoryPath($html),
            'image_url' => $this->extractMainImage($html),
            'images' => $this->extractAllImages($html),
            'description' => $this->extractDescription($html),
            'features' => $this->extractFeatures($html),
            'brand' => $this->extractBrand($html),
            'seller' => $this->extractSeller($html),
            'is_prime' => $this->isPrimeEligible($html),
            'scraped_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Extract product title/name
     */
    private function extractTitle(string $html): ?string
    {
        $patterns = [
            '/<span[^>]*id="productTitle"[^>]*>(.*?)<\/span>/is',
            '/<title>(.*?)<\/title>/is',
            '/<h1[^>]*class="[^"]*product-title[^"]*"[^>]*>(.*?)<\/h1>/is',
            '/<h1[^>]*>(.*?)<\/h1>/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $title = trim(strip_tags($matches[1]));
                $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
                $title = preg_replace('/\s+/', ' ', $title);
                $title = preg_replace('/\s*[:|]\s*Amazon\..*$/i', '', $title);
                $title = preg_replace('/\s*-\s*Amazon\..*$/i', '', $title);
                
                if (!empty($title) && strlen($title) > 10) {
                    return substr($title, 0, 500);
                }
            }
        }

        return null;
    }

    /**
     * Extract rating
     */
    private function extractRating(string $html): ?float
    {
        $patterns = [
            '/<span[^>]*class="[^"]*a-icon-alt[^"]*"[^>]*>([0-9.,]+)\s*(?:out of|sur|von|su|de)\s*(?:5|cinco)/i',
            '/"ratingValue"\s*:\s*"?([0-9.]+)"?/i',
            '/data-rating="([0-9.]+)"/i',
            '/<span[^>]*class="[^"]*rating[^"]*"[^>]*>([0-9.,]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $rating = str_replace(',', '.', $matches[1]);
                $rating = floatval($rating);
                
                if ($rating >= 0 && $rating <= 5) {
                    return round($rating, 1);
                }
            }
        }

        return null;
    }

    /**
     * Extract review count
     */
    private function extractReviewCount(string $html): ?int
    {
        $patterns = [
            '/<span[^>]*id="acrCustomerReviewText"[^>]*>([0-9,.\s]+)/i',
            '/"reviewCount"\s*:\s*"?([0-9,]+)"?/i',
            '/([0-9,.\s]+)\s*(?:ratings?|reviews?|évaluations?|avis|Bewertungen?|recensioni)/i',
            '/<a[^>]*href="[^"]*#customerReviews[^"]*"[^>]*>([0-9,.\s]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $count = preg_replace('/[^0-9]/', '', $matches[1]);
                $count = intval($count);
                
                if ($count > 0 && $count < 10000000) {
                    return $count;
                }
            }
        }

        return null;
    }

    /**
     * Extract stock quantity
     */
    private function extractStockQuantity(string $html): ?int
    {
        $patterns = [
            '/Only\s+(\d+)\s+left\s+in\s+stock/i',
            '/Plus\s+que\s+(\d+)\s+en\s+stock/i',
            '/Noch\s+(\d+)\s+auf\s+Lager/i',
            '/Solo\s+(\d+)\s+disponibles?/i',
            '/Rimangono\s+solo\s+(\d+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $quantity = intval($matches[1]);
                if ($quantity > 0 && $quantity < 1000) {
                    return $quantity;
                }
            }
        }

        return null;
    }

    /**
     * Check if product is in stock
     */
    private function isInStock(string $html): bool
    {
        $htmlLower = strtolower($html);
        
        $outOfStockIndicators = [
            'out of stock', 'rupture de stock', 'non disponible',
            'currently unavailable', 'actuellement indisponible',
            'nicht verfügbar', 'non disponibile',
        ];

        foreach ($outOfStockIndicators as $indicator) {
            if (str_contains($htmlLower, $indicator)) {
                return false;
            }
        }

        $inStockIndicators = [
            'in stock', 'en stock', 'auf lager', 'disponible',
            'available', 'add to cart', 'ajouter au panier',
        ];

        foreach ($inStockIndicators as $indicator) {
            if (str_contains($htmlLower, $indicator)) {
                return true;
            }
        }

        return true;
    }

    /**
     * Get stock status text
     */
    private function getStockStatus(string $html): string
    {
        if (!$this->isInStock($html)) {
            return 'out_of_stock';
        }

        $quantity = $this->extractStockQuantity($html);
        if ($quantity !== null) {
            return $quantity <= 5 ? 'low_stock' : 'in_stock_limited';
        }

        return 'in_stock';
    }

    /**
     * Extract availability text
     */
    private function extractAvailability(string $html): ?string
    {
        $patterns = [
            '/<span[^>]*id="availability"[^>]*>(.*?)<\/span>/s',
            '/<div[^>]*id="availability"[^>]*>(.*?)<\/div>/s',
            '/class="[^"]*availability[^"]*"[^>]*>(.*?)<\/(?:span|div)>/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $availability = trim(strip_tags($matches[1]));
                $availability = preg_replace('/\s+/', ' ', $availability);
                
                if (!empty($availability) && strlen($availability) > 3) {
                    return substr($availability, 0, 255);
                }
            }
        }

        return $this->isInStock($html) ? 'In Stock' : 'Out of Stock';
    }

    /**
     * Extract main category
     */
    private function extractMainCategory(string $html): ?string
    {
        $categories = $this->extractAllCategories($html);
        return !empty($categories) ? end($categories) : null;
    }

    /**
     * Extract ALL categories (breadcrumb)
     */
    private function extractAllCategories(string $html): array
    {
        $categories = [];
        $patterns = [
            '/<div[^>]*id="wayfinding-breadcrumbs_feature_div"[^>]*>(.*?)<\/div>/is',
            '/<ul[^>]*class="[^"]*a-breadcrumb[^"]*"[^>]*>(.*?)<\/ul>/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                preg_match_all('/<a[^>]+>([^<]+)<\/a>/i', $matches[1], $links);
                
                foreach ($links[1] as $category) {
                    $category = trim(strip_tags($category));
                    if (!empty($category) && strlen($category) > 2 && $category !== '›') {
                        $categories[] = $category;
                    }
                }
                
                if (!empty($categories)) {
                    break;
                }
            }
        }

        return array_values(array_unique($categories));
    }

    /**
     * Extract category path
     */
    private function extractCategoryPath(string $html): ?string
    {
        $categories = $this->extractAllCategories($html);
        return !empty($categories) ? implode(' > ', $categories) : null;
    }

    /**
     * Extract price - VERSION AMÉLIORÉE
     */
    private function extractPrice(string $html, ?string $url = null): ?float
    {
        // 1. Essayer JSON-LD (le plus fiable)
        $price = $this->extractPriceFromJsonLd($html);
        if ($price !== null) {
            Log::debug("Price extracted from JSON-LD: {$price}");
            return $price;
        }

        // 2. Essayer JavaScript embarqué
        $price = $this->extractPriceFromJavaScript($html);
        if ($price !== null) {
            Log::debug("Price extracted from JavaScript: {$price}");
            return $price;
        }

        // 3. Patterns HTML améliorés
        $marketplace = $url ? $this->extractMarketplaceFromUrl($url) : 'US';
        $currencySymbols = $this->getCurrencySymbolsForMarketplace($marketplace);
        
        $patterns = [
            // Format avec whole et fraction séparés (le plus courant)
            '/<span[^>]*class="[^"]*a-price-whole[^"]*"[^>]*>([0-9.,]+)<\/span>\s*<span[^>]*class="[^"]*a-price-fraction[^"]*"[^>]*>([0-9]+)<\/span>/is',
            '/<span[^>]*class="[^"]*a-price-whole[^"]*"[^>]*>([0-9.,]+)<\/span>/is',
            // Format avec a-offscreen (prix caché mais accessible)
            '/<span[^>]*class="[^"]*a-price[^"]*"[^>]*>.*?<span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>([^<]+)<\/span>/is',
            '/<span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>([^<]+)<\/span>/is',
            // IDs spécifiques Amazon
            '/<span[^>]*id="priceblock_ourprice"[^>]*>([^<]+)<\/span>/is',
            '/<span[^>]*id="priceblock_dealprice"[^>]*>([^<]+)<\/span>/is',
            '/<span[^>]*id="priceblock_saleprice"[^>]*>([^<]+)<\/span>/is',
            '/<span[^>]*id="price_inside_buybox"[^>]*>([^<]+)<\/span>/is',
            '/<span[^>]*id="priceblock_buybox"[^>]*>([^<]+)<\/span>/is',
            // Format EU avec data-a-color
            '/<span[^>]*data-a-color="price"[^>]*>.*?<span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>([^<]+)<\/span>/is',
            // Format avec data-price
            '/data-price="([0-9.,]+)"/i',
            '/data-price-amount="([0-9.,]+)"/i',
            // Format générique avec symboles de devise
            '/[' . implode('', array_map('preg_quote', $currencySymbols)) . ']\s*([0-9.,]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                // Si on a whole et fraction séparés
                if (isset($matches[2])) {
                    // Nettoyer les deux parties (enlever virgules, points, espaces)
                    $whole = preg_replace('/[^\d]/', '', $matches[1]);
                    $fraction = preg_replace('/[^\d]/', '', $matches[2]);
                    $priceStr = $whole . '.' . $fraction;
                } else {
                    // Sinon, utiliser la valeur telle quelle (sera nettoyée par parsePriceString)
                    $priceStr = $matches[1];
                }
                
                $price = $this->parsePriceString($priceStr);
                
                if ($price !== null && $price > 0 && $price < 1000000) {
                    Log::debug("Price extracted via HTML pattern: {$price} (from: {$matches[1]})");
                    return $price;
                }
            }
        }

        Log::warning("Could not extract price from HTML");
        return null;
    }

    /**
     * Extract price from JSON-LD structured data
     */
    private function extractPriceFromJsonLd(string $html): ?float
    {
        if (preg_match_all('/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/is', $html, $matches)) {
            foreach ($matches[1] as $jsonStr) {
                $jsonData = json_decode($jsonStr, true);
                
                if (is_array($jsonData)) {
                    // Structure simple
                    if (isset($jsonData['offers']['price'])) {
                        $priceValue = $jsonData['offers']['price'];
                        // Si c'est déjà un nombre, le retourner directement
                        if (is_numeric($priceValue)) {
                            $price = floatval($priceValue);
                            if ($price > 0 && $price < 1000000) {
                                Log::debug("Price from JSON-LD (numeric): {$price}");
                                return $price;
                            }
                        } else {
                            $price = $this->parsePriceString((string)$priceValue);
                            if ($price !== null) {
                                Log::debug("Price from JSON-LD (string): {$price} (from: {$priceValue})");
                                return $price;
                            }
                        }
                    }
                    
                    // Structure avec array d'offers
                    if (isset($jsonData['offers'][0]['price'])) {
                        $priceValue = $jsonData['offers'][0]['price'];
                        if (is_numeric($priceValue)) {
                            $price = floatval($priceValue);
                            if ($price > 0 && $price < 1000000) {
                                Log::debug("Price from JSON-LD array (numeric): {$price}");
                                return $price;
                            }
                        } else {
                            $price = $this->parsePriceString((string)$priceValue);
                            if ($price !== null) {
                                Log::debug("Price from JSON-LD array (string): {$price} (from: {$priceValue})");
                                return $price;
                            }
                        }
                    }
                    
                    // Structure directe
                    if (isset($jsonData['price'])) {
                        $priceValue = $jsonData['price'];
                        if (is_numeric($priceValue)) {
                            $price = floatval($priceValue);
                            if ($price > 0 && $price < 1000000) {
                                Log::debug("Price from JSON-LD direct (numeric): {$price}");
                                return $price;
                            }
                        } else {
                            $price = $this->parsePriceString((string)$priceValue);
                            if ($price !== null) {
                                Log::debug("Price from JSON-LD direct (string): {$price} (from: {$priceValue})");
                                return $price;
                            }
                        }
                    }
                    
                    // Structure avec @type Product
                    if (isset($jsonData['@type']) && $jsonData['@type'] === 'Product') {
                        if (isset($jsonData['offers']['price'])) {
                            $priceValue = $jsonData['offers']['price'];
                            if (is_numeric($priceValue)) {
                                $price = floatval($priceValue);
                                if ($price > 0 && $price < 1000000) {
                                    Log::debug("Price from JSON-LD Product (numeric): {$price}");
                                    return $price;
                                }
                            } else {
                                $price = $this->parsePriceString((string)$priceValue);
                                if ($price !== null) {
                                    Log::debug("Price from JSON-LD Product (string): {$price} (from: {$priceValue})");
                                    return $price;
                                }
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Extract price from JavaScript data
     */
    private function extractPriceFromJavaScript(string $html): ?float
    {
        $patterns = [
            '/"price"\s*:\s*"?([0-9.,]+)"?/i',
            '/"priceAmount"\s*:\s*"?([0-9.,]+)"?/i',
            '/"displayPrice"\s*:\s*"?([0-9.,]+)"?/i',
            '/"buyingPrice"\s*:\s*"?([0-9.,]+)"?/i',
            '/"currentPrice"\s*:\s*"?([0-9.,]+)"?/i',
            '/twister\.price\s*=\s*"?([0-9.,]+)"?/i',
            '/var\s+price\s*=\s*"?([0-9.,]+)"?/i',
            '/priceToPay["\']?\s*:\s*["\']?([0-9.,]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $price = $this->parsePriceString($matches[1]);
                if ($price !== null && $price > 0) {
                    return $price;
                }
            }
        }

        return null;
    }

    /**
     * Extract original price (before discount)
     */
    private function extractOriginalPrice(string $html): ?float
    {
        $patterns = [
            '/<span[^>]*class="[^"]*a-price[^"]*a-text-price[^"]*"[^>]*>.*?<span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>([^<]+)<\/span>/is',
            '/<span[^>]*data-a-strike="true"[^>]*>.*?<span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>([^<]+)<\/span>/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $price = $this->parsePriceString($matches[1]);
                if ($price !== null && $price > 0) {
                    return $price;
                }
            }
        }

        return null;
    }

    /**
     * Parse price string to float
     * Gère les formats: 39.00, 39,00, 1.234,56, 1234.56, 426.01, (avec virgule en fin), etc.
     */
    private function parsePriceString(string $priceStr): ?float
    {
        // Si c'est déjà un nombre, le retourner directement
        if (is_numeric($priceStr)) {
            $price = floatval($priceStr);
            if ($price > 0 && $price < 1000000) {
                return $price;
            }
            return null;
        }
        
        // Nettoyer la chaîne (garder seulement chiffres, virgules, points)
        $priceStr = preg_replace('/[^\d.,]/', '', $priceStr);
        $priceStr = trim($priceStr);
        
        // Supprimer les virgules et points en fin de chaîne (artefacts de regex)
        $priceStr = rtrim($priceStr, ',.');
        
        if (empty($priceStr)) {
            return null;
        }

        // Log pour déboguer
        Log::debug("Parsing price string: '{$priceStr}'");

        // Cas 1: Les deux séparateurs présents (point ET virgule)
        if (strpos($priceStr, ',') !== false && strpos($priceStr, '.') !== false) {
            // Déterminer lequel est le séparateur décimal
            $lastComma = strrpos($priceStr, ',');
            $lastDot = strrpos($priceStr, '.');
            
            // Le dernier séparateur est généralement le séparateur décimal
            if ($lastComma > $lastDot) {
                // Format: 1.234,56 → 1234.56 (point = milliers, virgule = décimal)
                $priceStr = str_replace('.', '', $priceStr);
                $priceStr = str_replace(',', '.', $priceStr);
            } else {
                // Format: 1,234.56 → 1234.56 (virgule = milliers, point = décimal)
                $priceStr = str_replace(',', '', $priceStr);
            }
        }
        // Cas 2: Seulement une virgule
        elseif (strpos($priceStr, ',') !== false) {
            $parts = explode(',', $priceStr);
            // Filtrer les parties vides (au cas où il y aurait des virgules en fin)
            $parts = array_filter($parts, function($part) { return !empty($part); });
            $parts = array_values($parts);
            
            // Si on a exactement 2 parties
            if (count($parts) == 2) {
                // Si la partie après la virgule fait 2 chiffres ou moins, c'est un format européen (39,00 → 39.00)
                if (strlen($parts[1]) <= 2) {
                    $priceStr = $parts[0] . '.' . $parts[1];
                } else {
                    // Si plus de 2 chiffres après la virgule, c'est probablement un séparateur de milliers (1,2345 → 12345)
                    $priceStr = implode('', $parts);
                }
            } elseif (count($parts) > 2) {
                // Plusieurs virgules = séparateurs de milliers (1,234,567 → 1234567)
                $priceStr = implode('', $parts);
            } else {
                // Une seule partie après la virgule (probablement une virgule en fin qui a été supprimée)
                $priceStr = $parts[0];
            }
        }
        // Cas 3: Seulement un point (format US standard: 39.00 ou 1234.56)
        // On laisse tel quel, floatval() gère correctement

        Log::debug("Parsed price string to: '{$priceStr}'");
        $price = floatval($priceStr);
        
        // Validation: le prix doit être raisonnable
        if ($price > 0 && $price < 1000000) {
            Log::debug("Final parsed price: {$price}");
            return $price;
        }
        
        Log::warning("Price parsing failed or invalid: '{$priceStr}' → {$price}");
        return null;
    }

    /**
     * Calculate discount percentage
     */
    private function calculateDiscount(?float $originalPrice, ?float $currentPrice): ?float
    {
        if ($originalPrice && $currentPrice && $originalPrice > $currentPrice) {
            $discount = (($originalPrice - $currentPrice) / $originalPrice) * 100;
            return round($discount, 2);
        }

        return null;
    }

    /**
     * Extract main image - VERSION AMÉLIORÉE
     */
    private function extractMainImage(string $html): ?string
    {
        // 1. Essayer JSON-LD (le plus fiable)
        $imageUrl = $this->extractImageFromJsonLd($html);
        if ($imageUrl !== null) {
            Log::debug("Image extracted from JSON-LD: {$imageUrl}");
            return $imageUrl;
        }

        // 2. Essayer JavaScript embarqué
        $imageUrl = $this->extractImageFromJavaScript($html);
        if ($imageUrl !== null) {
            Log::debug("Image extracted from JavaScript: {$imageUrl}");
            return $imageUrl;
        }

        // 3. Patterns HTML améliorés
        $patterns = [
            // ID landingImage (le plus courant)
            '/<img[^>]*id="landingImage"[^>]*src="([^"]*)"[^>]*>/i',
            '/<img[^>]*id="landingImage"[^>]*data-src="([^"]*)"[^>]*>/i',
            '/<img[^>]*id="landingImage"[^>]*data-old-hires="([^"]*)"[^>]*>/i',
            '/<img[^>]*id="landingImage"[^>]*data-a-dynamic-image="([^"]*)"[^>]*>/i',
            // Classe a-dynamic-image
            '/<img[^>]*class="[^"]*a-dynamic-image[^"]*"[^>]*src="([^"]*)"[^>]*>/i',
            '/<img[^>]*class="[^"]*a-dynamic-image[^"]*"[^>]*data-src="([^"]*)"[^>]*>/i',
            '/<img[^>]*class="[^"]*a-dynamic-image[^"]*"[^>]*data-old-hires="([^"]*)"[^>]*>/i',
            // ID main-image
            '/<img[^>]*id="main-image"[^>]*src="([^"]*)"[^>]*>/i',
            '/<img[^>]*id="main-image"[^>]*data-src="([^"]*)"[^>]*>/i',
            // ID main-image-container
            '/<div[^>]*id="main-image-container"[^>]*>.*?<img[^>]*src="([^"]*)"[^>]*>/is',
            // Format avec data-a-dynamic-image (JSON)
            '/<img[^>]*data-a-dynamic-image="([^"]*)"[^>]*>/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $imageUrl = str_replace('&amp;', '&', trim($matches[1]));
                
                // Si c'est du JSON dans data-a-dynamic-image, parser
                if (str_starts_with($imageUrl, '{')) {
                    $imageData = json_decode($imageUrl, true);
                    if (is_array($imageData) && !empty($imageData)) {
                        // Prendre la première image (la plus grande)
                        $imageUrl = array_key_first($imageData);
                    }
                }
                
                if ($this->isValidImageUrl($imageUrl)) {
                    return $imageUrl;
                }
            }
        }

        Log::warning("Could not extract image from HTML");
        return null;
    }

    /**
     * Validate image URL
     */
    private function isValidImageUrl(string $url): bool
    {
        return !empty($url) && 
               !str_contains($url, 'sprite') &&
               !str_contains($url, 'placeholder') &&
               !str_contains($url, '1x1') &&
               (filter_var($url, FILTER_VALIDATE_URL) || str_starts_with($url, 'http'));
    }

    /**
     * Extract image from JSON-LD structured data
     */
    private function extractImageFromJsonLd(string $html): ?string
    {
        if (preg_match_all('/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/is', $html, $matches)) {
            foreach ($matches[1] as $jsonStr) {
                $jsonData = json_decode($jsonStr, true);
                
                if (is_array($jsonData)) {
                    // Structure simple
                    if (isset($jsonData['image'])) {
                        if (is_string($jsonData['image'])) {
                            return $jsonData['image'];
                        } elseif (is_array($jsonData['image']) && !empty($jsonData['image'])) {
                            $firstImage = $jsonData['image'][0];
                            return is_string($firstImage) ? $firstImage : ($firstImage['url'] ?? null);
                        }
                    }
                    
                    // Structure avec @type Product
                    if (isset($jsonData['@type']) && $jsonData['@type'] === 'Product') {
                        if (isset($jsonData['image'])) {
                            if (is_string($jsonData['image'])) {
                                return $jsonData['image'];
                            } elseif (is_array($jsonData['image']) && !empty($jsonData['image'])) {
                                $firstImage = $jsonData['image'][0];
                                return is_string($firstImage) ? $firstImage : ($firstImage['url'] ?? null);
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Extract image from JavaScript data
     */
    private function extractImageFromJavaScript(string $html): ?string
    {
        $patterns = [
            '/"mainImage"\s*:\s*"([^"]+)"/i',
            '/"largeImage"\s*:\s*"([^"]+)"/i',
            '/"hiResImage"\s*:\s*"([^"]+)"/i',
            '/"imageUrl"\s*:\s*"([^"]+)"/i',
            '/"primaryImage"\s*:\s*"([^"]+)"/i',
            '/colorImages.*?"initial".*?"hiRes"\s*:\s*"([^"]+)"/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $imageUrl = str_replace(['\\/', '&amp;'], ['/', '&'], trim($matches[1]));
                
                if ($this->isValidImageUrl($imageUrl)) {
                    return $imageUrl;
                }
            }
        }

        return null;
    }

    /**
     * Extract all product images
     */
    private function extractAllImages(string $html): array
    {
        $images = [];

        // 1. Extraire depuis colorImages JSON
        if (preg_match('/"colorImages":\s*\{[^}]*"initial":\s*\[(.*?)\]/s', $html, $matches)) {
            preg_match_all('/"hiRes"\s*:\s*"(https?:\/\/[^"]+)"/i', $matches[1], $imageMatches);
            $images = array_merge($images, $imageMatches[1]);
            
            preg_match_all('/"large"\s*:\s*"(https?:\/\/[^"]+)"/i', $matches[1], $imageMatches);
            $images = array_merge($images, $imageMatches[1]);
        }

        // 2. Extraire depuis data-a-dynamic-image (JSON)
        if (preg_match_all('/data-a-dynamic-image="([^"]+)"/i', $html, $matches)) {
            foreach ($matches[1] as $jsonStr) {
                $jsonStr = html_entity_decode($jsonStr, ENT_QUOTES, 'UTF-8');
                $imageData = json_decode($jsonStr, true);
                if (is_array($imageData)) {
                    $images = array_merge($images, array_keys($imageData));
                }
            }
        }

        // 3. Extraire depuis data-old-hires
        preg_match_all('/<img[^>]*data-old-hires="(https?:\/\/[^"]+)"/i', $html, $matches);
        $images = array_merge($images, $matches[1]);

        // 4. Extraire depuis les images de la galerie
        preg_match_all('/<img[^>]*class="[^"]*a-dynamic-image[^"]*"[^>]*src="(https?:\/\/[^"]+)"/i', $html, $matches);
        $images = array_merge($images, $matches[1]);

        // 5. Extraire depuis les données JavaScript
        if (preg_match('/"imageGalleryData":\s*\[(.*?)\]/s', $html, $matches)) {
            preg_match_all('/"mainUrl"\s*:\s*"(https?:\/\/[^"]+)"/i', $matches[1], $imageMatches);
            $images = array_merge($images, $imageMatches[1]);
        }

        // Nettoyer et dédupliquer
        $images = array_map(function($url) {
            return str_replace(['&amp;', '\\/'], ['&', '/'], trim($url));
        }, $images);

        $images = array_filter($images, function($url) {
            return $this->isValidImageUrl($url);
        });

        return array_values(array_unique($images));
    }

    /**
     * Extract description
     */
    private function extractDescription(string $html): ?string
    {
        $patterns = [
            '/<div[^>]*id="feature-bullets"[^>]*>(.*?)<\/div>/s',
            '/<div[^>]*id="productDescription"[^>]*>(.*?)<\/div>/s',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $description = trim(strip_tags($matches[1]));
                $description = preg_replace('/\s+/', ' ', $description);
                
                if (!empty($description) && strlen($description) > 20) {
                    return substr($description, 0, 1000);
                }
            }
        }

        return null;
    }

    /**
     * Extract product features (bullet points)
     */
    private function extractFeatures(string $html): array
    {
        $features = [];

        if (preg_match('/<div[^>]*id="feature-bullets"[^>]*>(.*?)<\/div>/s', $html, $matches)) {
            preg_match_all('/<span[^>]*class="[^"]*a-list-item[^"]*"[^>]*>([^<]+)<\/span>/i', $matches[1], $items);
            
            foreach ($items[1] as $item) {
                $item = trim(strip_tags($item));
                if (!empty($item) && strlen($item) > 10) {
                    $features[] = $item;
                }
            }
        }

        return $features;
    }

    /**
     * Extract brand
     */
    private function extractBrand(string $html): ?string
    {
        $patterns = [
            '/<a[^>]*id="bylineInfo"[^>]*>([^<]+)<\/a>/i',
            '/"brand"\s*:\s*"([^"]+)"/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $brand = trim(str_replace(['Visit the', 'Store', 'Brand:'], '', $matches[1]));
                if (!empty($brand)) {
                    return $brand;
                }
            }
        }

        return null;
    }

    /**
     * Extract seller
     */
    private function extractSeller(string $html): ?string
    {
        $patterns = [
            '/<a[^>]*id="sellerProfileTriggerId"[^>]*>([^<]+)<\/a>/i',
            '/Ships\s+from\s+and\s+sold\s+by\s+<a[^>]*>([^<]+)<\/a>/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    /**
     * Check if Prime eligible
     */
    private function isPrimeEligible(string $html): bool
    {
        return (bool) preg_match('/<i[^>]*class="[^"]*a-icon-prime[^"]*"/i', $html);
    }

    /**
     * Fetch product page HTML
     */
    private function fetchProductPage(string $url): string
    {
        // Délai aléatoire pour éviter la détection (1-3 secondes)
        $delay = rand(1000000, 3000000);
        usleep($delay);
        
        $userAgent = $this->userAgents[array_rand($this->userAgents)];
        
        // Extraire le domaine Amazon pour le Referer
        $parsedUrl = parse_url($url);
        $baseUrl = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? 'www.amazon.com');
        $referer = $baseUrl . '/';
        
        // Headers plus réalistes pour éviter la détection
        $headers = [
            'User-Agent' => $userAgent,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Accept-Encoding' => 'gzip, deflate, br, zstd',
            'DNT' => '1',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
            'sec-ch-ua' => '"Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'Cache-Control' => 'max-age=0',
            'Referer' => $referer,
        ];
        
        Log::debug("Fetching URL with headers", ['url' => $url, 'user-agent' => $userAgent]);
        
        $response = Http::withHeaders($headers)
            ->withOptions([
                'verify' => true,
                'timeout' => 30,
                'allow_redirects' => true,
            ])
            ->get($url);

        if (!$response->successful()) {
            Log::warning("HTTP request failed", [
                'status' => $response->status(),
                'url' => $url,
                'body_preview' => substr($response->body(), 0, 500)
            ]);
            throw new Exception("HTTP request failed with status: {$response->status()}");
        }

        $html = $response->body();

        if ($this->isCaptchaPage($html)) {
            Log::warning("Captcha detected", ['url' => $url]);
            throw new Exception('Amazon detected automated request (captcha). Please try again later.');
        }

        return $html;
    }

    /**
     * Check if captcha page
     */
    private function isCaptchaPage(string $html): bool
    {
        $indicators = [
            'captcha',
            'robot check',
            'Type the characters you see',
            'Sorry, we just need to make sure you\'re not a robot',
            'Enter the characters you see',
            'automated access',
            'unusual traffic',
            'verify you\'re human',
            'amazon.com/captcha',
            'id="captchacharacters"',
            'name="captchacharacters"',
        ];
        
        $htmlLower = strtolower($html);
        
        foreach ($indicators as $indicator) {
            if (str_contains($htmlLower, strtolower($indicator))) {
                Log::warning("Captcha indicator found", ['indicator' => $indicator]);
                return true;
            }
        }
        
        // Vérifier aussi si le titre de la page contient "captcha" ou "robot"
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            $title = strtolower($matches[1]);
            if (str_contains($title, 'captcha') || str_contains($title, 'robot')) {
                Log::warning("Captcha detected in page title", ['title' => $matches[1]]);
                return true;
            }
        }
        
        return false;
    }

    /**
     * Validate product data
     */
    private function isValidProductData(array $data): bool
    {
        return !empty($data['title']) && 
               !empty($data['asin']) &&
               !empty($data['marketplace']);
    }

    /**
     * Extract marketplace from URL
     */
    private function extractMarketplaceFromUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        $host = strtolower($host);
        
        $marketplaceMap = [
            'amzn.com.br' => 'BR',
            'amzn.co.uk' => 'UK',
            'amzn.de' => 'DE',
            'amzn.fr' => 'FR',
            'amzn.it' => 'IT',
            'amzn.es' => 'ES',
            'amzn.in' => 'IN',
            'amzn.ca' => 'CA',
            'amzn.eu' => 'EU',
            'amazon.com.br' => 'BR',
            'amazon.co.uk' => 'UK',
            'amazon.de' => 'DE',
            'amazon.fr' => 'FR',
            'amazon.it' => 'IT',
            'amazon.es' => 'ES',
            'amazon.in' => 'IN',
            'amazon.ca' => 'CA',
            'amazon.eu' => 'EU',
            'amazon.com' => 'US',
        ];

        foreach ($marketplaceMap as $domain => $marketplace) {
            if (str_contains($host, $domain)) {
                return $marketplace;
            }
        }
        
        return 'US';
    }

    /**
     * Get country name from marketplace
     */
    private function getCountryFromMarketplace(string $marketplace): string
    {
        return match ($marketplace) {
            'US' => 'United States',
            'UK' => 'United Kingdom',
            'DE' => 'Germany',
            'FR' => 'France',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'BR' => 'Brazil',
            'IN' => 'India',
            'CA' => 'Canada',
            'EU' => 'Europe',
            default => 'United States',
        };
    }

    /**
     * Get currency from marketplace
     */
    private function getCurrencyFromMarketplace(string $marketplace): string
    {
        return match ($marketplace) {
            'FR', 'DE', 'ES', 'IT', 'EU' => 'EUR',
            'UK' => 'GBP',
            'CA' => 'CAD',
            'BR' => 'BRL',
            'IN' => 'INR',
            default => 'USD',
        };
    }

    /**
     * Get currency symbols for marketplace
     */
    private function getCurrencySymbolsForMarketplace(string $marketplace): array
    {
        return match ($marketplace) {
            'US' => ['$', 'USD'],
            'UK' => ['£', 'GBP', 'p'],
            'DE', 'FR', 'IT', 'ES', 'EU' => ['€', 'EUR'],
            'BR' => ['R$', 'BRL'],
            'IN' => ['₹', 'INR', 'Rs'],
            'CA' => ['$', 'CAD', 'C$'],
            default => ['$', 'USD'],
        };
    }

    /**
     * Check if short URL
     */
    private function isShortUrl(string $url): bool
    {
        return str_contains($url, 'a.co') || 
               str_contains($url, 'amzn.to') || 
               str_contains($url, 'amzn.eu') ||
               str_contains($url, 'amzn.com');
    }

    /**
     * Resolve short URL
     */
    public function resolveShortUrl(string $shortUrl): ?string
    {
        try {
            $userAgent = $this->userAgents[array_rand($this->userAgents)];
            
            $response = Http::withHeaders([
                'User-Agent' => $userAgent,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'DNT' => '1',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Sec-Fetch-User' => '?1',
            ])->timeout(10)->get($shortUrl);

            if ($response->successful()) {
                return $response->effectiveUri();
            }
        } catch (Exception $e) {
            Log::warning('Short URL resolution failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Validate Amazon URL
     */
    public function validateAmazonUrl(string $url): bool
    {
        $amazonDomains = [
            'amazon.com', 'amazon.de', 'amazon.co.uk', 'amazon.fr',
            'amazon.it', 'amazon.es', 'amazon.com.br', 'amazon.in',
            'amazon.ca', 'amazon.eu', 'a.co', 'amzn.to', 'amzn.eu',
            'amzn.com', 'amzn.com.br', 'amzn.co.uk', 'amzn.de',
            'amzn.fr', 'amzn.it', 'amzn.es', 'amzn.in', 'amzn.ca',
        ];

        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        
        foreach ($amazonDomains as $domain) {
            if (str_contains($host, $domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scrape with retry
     */
    public function scrapeProductWithRetry(string $url, int $maxRetries = 3): array
    {
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            Log::info("Scraping attempt {$attempt}/{$maxRetries}", ['url' => $url]);
            
            $result = $this->scrapeProduct($url, $attempt === 1);
            
            if ($result['success']) {
                return $result;
            }
            
            // Si c'est un captcha, attendre plus longtemps avant de réessayer
            if (isset($result['error']) && str_contains($result['error'], 'captcha')) {
                $waitTime = pow(2, $attempt) * 2000000; // 2, 4, 8 secondes
                Log::warning("Captcha detected, waiting {$waitTime} microseconds before retry");
                if ($attempt < $maxRetries) {
                    usleep($waitTime);
                }
            } elseif ($attempt < $maxRetries) {
                // Délai exponentiel avec jitter aléatoire
                $waitTime = pow(2, $attempt) * 1000000 + rand(500000, 1500000);
                usleep($waitTime);
            }
        }
        
        return [
            'success' => false,
            'error' => 'Failed after ' . $maxRetries . ' attempts: ' . ($result['error'] ?? 'Unknown error'),
            'url' => $url,
        ];
    }
}
