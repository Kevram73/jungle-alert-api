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
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
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
     * 🔄 NOUVELLE MÉTHODE: Normaliser les URLs Amazon
     * Convertit les liens mobiles en liens desktop pour avoir plus de données
     */
    private function normalizeAmazonUrl(string $url): string
    {
        // Extraire l'ASIN
        $asin = $this->extractAsinFromUrl($url);
        if (!$asin) {
            return $url; // Retourner l'URL originale si pas d'ASIN
        }

        // Extraire le domaine de base
        $baseUrl = $this->getAmazonBaseUrl($url);

        // 🎯 Construire l'URL desktop canonique
        // Format: https://www.amazon.XX/dp/{ASIN}
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
            
            // Remplacer les domaines mobiles par les domaines desktop
            $host = str_replace('m.amazon', 'www.amazon', $host);
            
            // Check short URLs first
            if (str_contains($host, 'amzn.com.br')) return 'https://www.amazon.com.br';
            if (str_contains($host, 'amzn.co.uk')) return 'https://www.amazon.co.uk';
            if (str_contains($host, 'amzn.de')) return 'https://www.amazon.de';
            if (str_contains($host, 'amzn.fr')) return 'https://www.amazon.fr';
            if (str_contains($host, 'amzn.it')) return 'https://www.amazon.it';
            if (str_contains($host, 'amzn.es')) return 'https://www.amazon.es';
            if (str_contains($host, 'amzn.in')) return 'https://www.amazon.in';
            if (str_contains($host, 'amzn.ca')) return 'https://www.amazon.ca';
            if (str_contains($host, 'amzn.eu')) return 'https://www.amazon.eu';
            
            // Check specific domains
            if (str_contains($host, 'amazon.com.br')) return 'https://www.amazon.com.br';
            if (str_contains($host, 'amazon.co.uk')) return 'https://www.amazon.co.uk';
            if (str_contains($host, 'amazon.de')) return 'https://www.amazon.de';
            if (str_contains($host, 'amazon.fr')) return 'https://www.amazon.fr';
            if (str_contains($host, 'amazon.it')) return 'https://www.amazon.it';
            if (str_contains($host, 'amazon.es')) return 'https://www.amazon.es';
            if (str_contains($host, 'amazon.in')) return 'https://www.amazon.in';
            if (str_contains($host, 'amazon.ca')) return 'https://www.amazon.ca';
            if (str_contains($host, 'amazon.eu')) return 'https://www.amazon.eu';
            if (str_contains($host, 'amazon.com')) return 'https://www.amazon.com';
        }
        
        return 'https://www.amazon.com';
    }

    /**
     * Extract ASIN from URL (supporte mobile et desktop)
     */
    private function extractAsinFromUrl(string $url): ?string
    {
        $patterns = [
            '/\/dp\/([A-Z0-9]{10})/',           // Desktop: /dp/ASIN
            '/\/product\/([A-Z0-9]{10})/',      // Desktop: /product/ASIN
            '/\/gp\/product\/([A-Z0-9]{10})/',  // Desktop: /gp/product/ASIN
            '/\/gp\/aw\/d\/([A-Z0-9]{10})/',    // 📱 Mobile: /gp/aw/d/ASIN
            '/\/aw\/d\/([A-Z0-9]{10})/',        // 📱 Mobile court
            '/\/[^\/]*\/([A-Z0-9]{10})/',       // Pattern générique
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
            // BASIC INFO
            'asin' => $asin,
            'amazon_url' => $url,
            'marketplace' => $marketplace,
            'country' => $country,
            
            // PRODUCT NAME
            'title' => $this->extractTitle($html),
            'name' => $this->extractTitle($html),
            
            // PRICING
            'price' => $this->extractPrice($html, $url),
            'current_price' => $this->extractPrice($html, $url),
            'original_price' => $this->extractOriginalPrice($html),
            'currency' => $this->getCurrencyFromMarketplace($marketplace),
            'discount_percentage' => $this->calculateDiscount(
                $this->extractOriginalPrice($html),
                $this->extractPrice($html)
            ),
            
            // RATINGS & REVIEWS ⭐
            'rating' => $this->extractRating($html),
            'stars' => $this->extractRating($html),
            'review_count' => $this->extractReviewCount($html),
            'number_of_reviews' => $this->extractReviewCount($html),
            
            // AVAILABILITY & STOCK 📦
            'availability' => $this->extractAvailability($html),
            'in_stock' => $this->isInStock($html),
            'stock_quantity' => $this->extractStockQuantity($html),
            'stock_status' => $this->getStockStatus($html),
            
            // CATEGORIES 🏷️
            'category' => $this->extractMainCategory($html),
            'categories' => $this->extractAllCategories($html),
            'category_path' => $this->extractCategoryPath($html),
            
            // IMAGES & MEDIA
            'image_url' => $this->extractMainImage($html),
            'images' => $this->extractAllImages($html),
            
            // ADDITIONAL INFO
            'description' => $this->extractDescription($html),
            'features' => $this->extractFeatures($html),
            'brand' => $this->extractBrand($html),
            'seller' => $this->extractSeller($html),
            'is_prime' => $this->isPrimeEligible($html),
            
            // METADATA
            'scraped_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Extract product title/name (COMPLET)
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
     * Extract rating (nombre d'étoiles) ⭐
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
     * Extract review count (nombre d'avis) 💬
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
     * Extract stock quantity (nombre en stock) 📦
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
        $inStockIndicators = [
            'in stock', 'en stock', 'auf lager', 'disponible',
            'available', 'add to cart', 'ajouter au panier',
            'in den einkaufswagen', 'aggiungi al carrello',
        ];

        $outOfStockIndicators = [
            'out of stock', 'rupture de stock', 'non disponible',
            'currently unavailable', 'actuellement indisponible',
            'nicht verfügbar', 'non disponibile',
        ];

        $htmlLower = strtolower($html);

        foreach ($outOfStockIndicators as $indicator) {
            if (str_contains($htmlLower, $indicator)) {
                return false;
            }
        }

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
            if ($quantity <= 5) {
                return 'low_stock';
            }
            return 'in_stock_limited';
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
     * Extract main category 🏷️
     */
    private function extractMainCategory(string $html): ?string
    {
        $categories = $this->extractAllCategories($html);
        
        if (!empty($categories)) {
            return end($categories);
        }

        return null;
    }

    /**
     * Extract ALL categories (breadcrumb) 🏷️
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
     * Extract category path (chemin complet)
     */
    private function extractCategoryPath(string $html): ?string
    {
        $categories = $this->extractAllCategories($html);
        
        if (!empty($categories)) {
            return implode(' > ', $categories);
        }

        return null;
    }

    /**
     * Extract price
     */
    private function extractPrice(string $html, ?string $url = null): ?float
    {
        // Si l'URL n'est pas fournie, essayer d'extraire depuis le HTML
        $marketplace = $url ? $this->extractMarketplaceFromUrl($url) : 'US';
        $currencySymbols = $this->getCurrencySymbolsForMarketplace($marketplace);
        
        // 1. Essayer d'extraire depuis les données JSON-LD (plus fiable)
        $price = $this->extractPriceFromJsonLd($html);
        if ($price !== null) {
            return $price;
        }

        // 2. Essayer d'extraire depuis les données JavaScript
        $price = $this->extractPriceFromJavaScript($html);
        if ($price !== null) {
            return $price;
        }

        // 3. Patterns HTML classiques
        $patterns = [
            // Format avec whole et fraction séparés
            '/<span[^>]*class="[^"]*a-price-whole[^"]*"[^>]*>([0-9.,]+)<\/span>\s*<span[^>]*class="[^"]*a-price-fraction[^"]*"[^>]*>([0-9]+)<\/span>/is',
            '/<span[^>]*class="[^"]*a-price-whole[^"]*"[^>]*>([0-9.,]+)<\/span>/is',
            // Format avec a-offscreen (prix caché mais accessible)
            '/<span[^>]*class="[^"]*a-price[^"]*"[^>]*>.*?<span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>([^<]+)<\/span>/is',
            '/<span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>([^<]+)<\/span>/is',
            // IDs spécifiques
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
                if (isset($matches[2])) {
                    $priceStr = $matches[1] . '.' . $matches[2];
                } else {
                    $priceStr = $matches[1];
                }
                
                $price = $this->parsePriceString($priceStr);
                
                if ($price !== null && $price > 0 && $price < 1000000) {
                    Log::debug("Price extracted via pattern: {$price}");
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
        // Chercher les données JSON-LD
        if (preg_match('/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/is', $html, $matches)) {
            $jsonData = json_decode($matches[1], true);
            
            if (is_array($jsonData)) {
                // Essayer d'extraire le prix depuis différentes structures
                if (isset($jsonData['offers']['price'])) {
                    $price = $this->parsePriceString((string)$jsonData['offers']['price']);
                    if ($price !== null) {
                        return $price;
                    }
                }
                
                if (isset($jsonData['offers'][0]['price'])) {
                    $price = $this->parsePriceString((string)$jsonData['offers'][0]['price']);
                    if ($price !== null) {
                        return $price;
                    }
                }
                
                if (isset($jsonData['price'])) {
                    $price = $this->parsePriceString((string)$jsonData['price']);
                    if ($price !== null) {
                        return $price;
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
        // Chercher dans les données JavaScript embarquées
        $patterns = [
            '/"price"\s*:\s*"?([0-9.,]+)"?/i',
            '/"priceAmount"\s*:\s*"?([0-9.,]+)"?/i',
            '/"displayPrice"\s*:\s*"?([0-9.,]+)"?/i',
            '/"buyingPrice"\s*:\s*"?([0-9.,]+)"?/i',
            '/twister\.price\s*=\s*"?([0-9.,]+)"?/i',
            '/var\s+price\s*=\s*"?([0-9.,]+)"?/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $price = $this->parsePriceString($matches[1]);
                if ($price !== null && $price > 0) {
                    Log::debug("Price extracted from JavaScript: {$price}");
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
     */
    private function parsePriceString(string $priceStr): ?float
    {
        $priceStr = preg_replace('/[^\d.,\s]/', '', $priceStr);
        $priceStr = trim($priceStr);

        if (strpos($priceStr, ',') !== false && strpos($priceStr, '.') !== false) {
            $priceStr = str_replace(['.', ','], ['', '.'], $priceStr);
        } elseif (strpos($priceStr, ',') !== false) {
            $parts = explode(',', $priceStr);
            if (count($parts) == 2 && strlen($parts[1]) <= 2) {
                $priceStr = str_replace(',', '.', $priceStr);
            } else {
                $priceStr = str_replace(',', '', $priceStr);
            }
        }

        $price = floatval($priceStr);
        
        return $price > 0 ? $price : null;
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
     * Extract main image
     */
    private function extractMainImage(string $html): ?string
    {
        // 1. Essayer d'extraire depuis les données JSON-LD
        $imageUrl = $this->extractImageFromJsonLd($html);
        if ($imageUrl !== null) {
            return $imageUrl;
        }

        // 2. Essayer d'extraire depuis les données JavaScript
        $imageUrl = $this->extractImageFromJavaScript($html);
        if ($imageUrl !== null) {
            return $imageUrl;
        }

        // 3. Patterns HTML classiques
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
                
                if (!empty($imageUrl) && 
                    !str_contains($imageUrl, 'sprite') &&
                    !str_contains($imageUrl, 'placeholder') &&
                    !str_contains($imageUrl, '1x1') &&
                    (filter_var($imageUrl, FILTER_VALIDATE_URL) || str_starts_with($imageUrl, 'http'))) {
                    Log::debug("Image extracted: {$imageUrl}");
                    return $imageUrl;
                }
            }
        }

        Log::warning("Could not extract image from HTML");
        return null;
    }

    /**
     * Extract image from JSON-LD structured data
     */
    private function extractImageFromJsonLd(string $html): ?string
    {
        // Chercher les données JSON-LD
        if (preg_match('/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/is', $html, $matches)) {
            $jsonData = json_decode($matches[1], true);
            
            if (is_array($jsonData)) {
                // Essayer d'extraire l'image depuis différentes structures
                if (isset($jsonData['image'])) {
                    if (is_string($jsonData['image'])) {
                        return $jsonData['image'];
                    } elseif (is_array($jsonData['image']) && !empty($jsonData['image'])) {
                        return is_string($jsonData['image'][0]) ? $jsonData['image'][0] : $jsonData['image'][0]['url'] ?? null;
                    }
                }
                
                if (isset($jsonData['offers']['image'])) {
                    return $jsonData['offers']['image'];
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
        // Chercher dans les données JavaScript embarquées
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
                $imageUrl = str_replace('\\/', '/', $matches[1]);
                $imageUrl = str_replace('&amp;', '&', trim($imageUrl));
                
                if (!empty($imageUrl) && 
                    !str_contains($imageUrl, 'sprite') &&
                    !str_contains($imageUrl, 'placeholder') &&
                    (filter_var($imageUrl, FILTER_VALIDATE_URL) || str_starts_with($imageUrl, 'http'))) {
                    Log::debug("Image extracted from JavaScript: {$imageUrl}");
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
            
            // Aussi chercher large et medium
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
            return !empty($url) && 
                   !str_contains($url, 'sprite') &&
                   !str_contains($url, 'placeholder') &&
                   !str_contains($url, '1x1') &&
                   (filter_var($url, FILTER_VALIDATE_URL) || str_starts_with($url, 'http'));
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
        $userAgent = $this->userAgents[array_rand($this->userAgents)];
        
        $response = Http::withHeaders([
            'User-Agent' => $userAgent,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.5',
            'Accept-Encoding' => 'gzip, deflate, br',
            'DNT' => '1',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
        ])->timeout(30)->get($url);

        if (!$response->successful()) {
            throw new Exception("HTTP request failed with status: {$response->status()}");
        }

        $html = $response->body();

        if ($this->isCaptchaPage($html)) {
            throw new Exception('Amazon detected automated request (captcha). Please try again later.');
        }

        return $html;
    }

    /**
     * Check if captcha page
     */
    private function isCaptchaPage(string $html): bool
    {
        $indicators = ['captcha', 'robot check', 'Type the characters you see'];
        $htmlLower = strtolower($html);
        
        foreach ($indicators as $indicator) {
            if (str_contains($htmlLower, strtolower($indicator))) {
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
        
        // Check short URLs first
        if (str_contains($host, 'amzn.com.br')) return 'BR';
        if (str_contains($host, 'amzn.co.uk')) return 'UK';
        if (str_contains($host, 'amzn.de')) return 'DE';
        if (str_contains($host, 'amzn.fr')) return 'FR';
        if (str_contains($host, 'amzn.it')) return 'IT';
        if (str_contains($host, 'amzn.es')) return 'ES';
        if (str_contains($host, 'amzn.in')) return 'IN';
        if (str_contains($host, 'amzn.ca')) return 'CA';
        if (str_contains($host, 'amzn.eu')) return 'EU';
        
        // Check specific domains
        if (str_contains($host, 'amazon.com.br')) return 'BR';
        if (str_contains($host, 'amazon.co.uk')) return 'UK';
        if (str_contains($host, 'amazon.de')) return 'DE';
        if (str_contains($host, 'amazon.fr')) return 'FR';
        if (str_contains($host, 'amazon.it')) return 'IT';
        if (str_contains($host, 'amazon.es')) return 'ES';
        if (str_contains($host, 'amazon.in')) return 'IN';
        if (str_contains($host, 'amazon.ca')) return 'CA';
        if (str_contains($host, 'amazon.eu')) return 'EU';
        if (str_contains($host, 'amazon.com')) return 'US';
        
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
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgents[0],
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
            $result = $this->scrapeProduct($url, $attempt === 1);
            
            if ($result['success']) {
                return $result;
            }
            
            if ($attempt < $maxRetries) {
                usleep(pow(2, $attempt) * 1000000);
            }
        }
        
        return [
            'success' => false,
            'error' => 'Failed after ' . $maxRetries . ' attempts',
        ];
    }

    /**
     * Get fallback image URL from ASIN
     * Format: https://images-na.ssl-images-amazon.com/images/I/{ASIN_HASH}.{EXT}
     */
    private function getFallbackImageUrl(string $asin, string $marketplace): ?string
    {
        $baseUrl = $this->getAmazonBaseUrl(null);
        
        // Construire l'URL d'image standard d'Amazon
        // Format: https://m.media-amazon.com/images/I/{hash}.{ext}
        // On peut essayer plusieurs formats courants
        $imageFormats = [
            "https://m.media-amazon.com/images/I/{$asin}._AC_SL1500_.jpg",
            "https://images-na.ssl-images-amazon.com/images/I/{$asin}._AC_SL1500_.jpg",
            "https://images-eu.ssl-images-amazon.com/images/I/{$asin}._AC_SL1500_.jpg",
        ];

        // Note: Cette méthode ne garantit pas que l'image existe
        // Mais c'est mieux que de retourner null
        return $imageFormats[0] ?? null;
    }
}
