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
    // 🔥 User-Agents ultra-réalistes et variés (2024-2025)
    private array $userAgents = [
        // Chrome Windows (les plus courants)
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
        
        // Chrome macOS
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 13_6_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
        
        // Firefox Windows
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:132.0) Gecko/20100101 Firefox/132.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:131.0) Gecko/20100101 Firefox/131.0',
        
        // Firefox macOS
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:132.0) Gecko/20100101 Firefox/132.0',
        
        // Safari macOS (très important pour Amazon)
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.1 Safari/605.1.15',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15',
        
        // Edge Windows (basé sur Chromium)
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0',
    ];
    
    // 🍪 Cookies simulés pour paraître plus naturel
    private array $cookieJar = [];

    /**
     * Scrape product with ALL enriched data
     * Supporte les liens web ET mobile
     */
    public function scrapeProduct(string $url, bool $useCache = true): array
    {
        try {
            // 🎲 Délai initial ALÉATOIRE et INTELLIGENT pour éviter la détection
            // Varie entre 0.5s et 2.5s pour paraître plus humain
            $initialDelay = rand(500000, 2500000);
            usleep($initialDelay);
            
            // 🔄 ÉTAPE 1: Normaliser l'URL (mobile → desktop)
            $url = $this->normalizeAmazonUrl($url);
            Log::info("📋 Normalized URL", ['url' => substr($url, 0, 100) . '...']);

            // Resolve short URLs
            if ($this->isShortUrl($url)) {
                $resolvedUrl = $this->resolveShortUrl($url);
                if ($resolvedUrl) {
                    $url = $this->normalizeAmazonUrl($resolvedUrl);
                    Log::info("🔗 Short URL resolved", ['url' => substr($url, 0, 100) . '...']);
                }
            }

            // Check cache with longer TTL
            if ($useCache) {
                $cacheKey = 'amazon_enriched_' . md5($url);
                $cachedData = Cache::get($cacheKey);
                
                if ($cachedData !== null) {
                    Log::info("💾 Cache HIT", [
                        'url' => substr($url, 0, 100),
                        'title' => $cachedData['title'] ?? 'N/A',
                    ]);
                    return [
                        'success' => true,
                        'data' => $cachedData,
                        'cached' => true,
                    ];
                }
                Log::debug("💾 Cache MISS", ['url' => substr($url, 0, 100)]);
            }

            // Extract ASIN and marketplace
            $asin = $this->extractAsinFromUrl($url);
            if (!$asin) {
                throw new Exception('Could not extract ASIN from URL');
            }

            $marketplace = $this->extractMarketplaceFromUrl($url);
            $country = $this->getCountryFromMarketplace($marketplace);

            Log::info("🎯 Scraping target", [
                'asin' => $asin,
                'marketplace' => $marketplace,
                'country' => $country,
            ]);

            // Fetch HTML
            $html = $this->fetchProductPage($url);

            // Extract ALL data
            $productData = $this->extractAllProductData($html, $url, $asin, $marketplace, $country);
            
            // Fallback: Si l'image principale n'est pas trouvée, utiliser la première image de la liste
            if (empty($productData['image_url']) && !empty($productData['images'])) {
                $productData['image_url'] = $productData['images'][0];
                Log::info("🖼️ Using first image from images array as main image");
            }
            
            // Logging pour déboguer les problèmes d'extraction
            if (empty($productData['price']) && empty($productData['current_price'])) {
                Log::warning("💰 Price extraction failed", [
                    'asin' => $asin,
                    'marketplace' => $marketplace,
                ]);
            }
            if (empty($productData['image_url'])) {
                Log::warning("🖼️ Image extraction failed", [
                    'asin' => $asin,
                    'marketplace' => $marketplace,
                ]);
            }

            // Validate
            if (!$this->isValidProductData($productData)) {
                throw new Exception('Scraped data is incomplete or invalid');
            }

            // 💾 Cache result avec TTL PLUS LONG pour réduire la fréquence des requêtes
            // 30 minutes au lieu de 15, encore mieux pour éviter les blocages
            if ($useCache) {
                $cacheKey = 'amazon_enriched_' . md5($url);
                $cacheDuration = now()->addMinutes(30);
                Cache::put($cacheKey, $productData, $cacheDuration);
                Log::debug("💾 Cached for 30 minutes", [
                    'key' => substr($cacheKey, 0, 20) . '...',
                ]);
            }

            Log::info("✅ Successfully scraped", [
                'title' => substr($productData['title'], 0, 60) . '...',
                'price' => $productData['current_price'] ?? 'N/A',
            ]);

            return [
                'success' => true,
                'data' => $productData,
                'cached' => false,
            ];

        } catch (Exception $e) {
            Log::error('❌ Scraping error', [
                'message' => $e->getMessage(),
                'url' => substr($url, 0, 100),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
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
            'discount_percentage' => $this->extractDiscountPercentage($html),
            'availability' => $this->extractAvailability($html),
            'in_stock' => $this->isInStock($html),
            'stock_quantity' => $this->extractStockQuantity($html),
            'image_url' => $this->extractImageUrl($html),
            'images' => $this->extractAllImages($html),
            'description' => $this->extractDescription($html),
            'features' => $this->extractFeatures($html),
            'rating' => $this->extractRating($html),
            'rating_count' => $this->extractRatingCount($html),
            'review_count' => $this->extractReviewCount($html),
            'categories' => $this->extractCategories($html),
            'brand' => $this->extractBrand($html),
            'specifications' => $this->extractSpecifications($html),
            'prime_eligible' => $this->isPrimeEligible($html),
            'seller' => $this->extractSeller($html),
        ];
    }

    /**
     * Extract product title
     */
    private function extractTitle(string $html): ?string
    {
        $patterns = [
            '/<span[^>]*id="productTitle"[^>]*>(.*?)<\/span>/is',
            '/<h1[^>]*id="title"[^>]*>(.*?)<\/h1>/is',
            '/<title[^>]*>(.*?)\s*:\s*Amazon\./is',
            '/<meta[^>]*property="og:title"[^>]*content="([^"]+)"/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $title = html_entity_decode(strip_tags(trim($matches[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (!empty($title)) {
                    return $title;
                }
            }
        }

        return null;
    }

    /**
     * Extract current price
     */
    private function extractPrice(string $html, string $url): ?float
    {
        $marketplace = $this->extractMarketplaceFromUrl($url);
        $symbols = $this->getCurrencySymbolsForMarketplace($marketplace);
        
        $patterns = [
            '/<span[^>]*class="[^"]*a-price-whole[^"]*"[^>]*>([\d\s.,]+)<\/span>/i',
            '/<span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>.*?([\d\s.,]+).*?<\/span>/i',
            '/<span[^>]*id="priceblock_ourprice"[^>]*>([\d\s.,]+)<\/span>/i',
            '/<span[^>]*id="priceblock_dealprice"[^>]*>([\d\s.,]+)<\/span>/i',
            '/<span[^>]*class="[^"]*apexPriceToPay[^"]*"[^>]*>.*?([\d\s.,]+).*?<\/span>/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                foreach ($matches[1] as $match) {
                    $price = $this->parsePrice($match, $marketplace);
                    if ($price > 0) {
                        return $price;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Parse price string to float
     */
    private function parsePrice(string $priceString, string $marketplace): float
    {
        $priceString = preg_replace('/\s+/', '', $priceString);
        
        if (in_array($marketplace, ['FR', 'DE', 'IT', 'ES', 'EU'])) {
            $priceString = str_replace('.', '', $priceString);
            $priceString = str_replace(',', '.', $priceString);
        } else {
            $priceString = str_replace(',', '', $priceString);
        }
        
        $priceString = preg_replace('/[^\d.]/', '', $priceString);
        
        return (float) $priceString;
    }

    /**
     * Extract original price (before discount)
     */
    private function extractOriginalPrice(string $html): ?float
    {
        $patterns = [
            '/<span[^>]*class="[^"]*a-price[^"]*a-text-price[^"]*"[^>]*>.*?<span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>([\d\s.,]+)<\/span>/is',
            '/<span[^>]*class="[^"]*a-text-strike[^"]*"[^>]*>([\d\s.,]+)<\/span>/i',
            '/<span[^>]*class="[^"]*basisPrice[^"]*"[^>]*>([\d\s.,]+)<\/span>/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $price = $this->parsePrice($matches[1], 'US');
                if ($price > 0) {
                    return $price;
                }
            }
        }

        return null;
    }

    /**
     * Extract discount percentage
     */
    private function extractDiscountPercentage(string $html): ?int
    {
        $patterns = [
            '/-(\d+)%/',
            '/Save\s+(\d+)%/i',
            '/(\d+)%\s+off/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    /**
     * Extract availability status
     */
    private function extractAvailability(string $html): string
    {
        $patterns = [
            '/<div[^>]*id="availability"[^>]*>.*?<span[^>]*>(.*?)<\/span>/is',
            '/<span[^>]*class="[^"]*a-color-success[^"]*"[^>]*>(.*?)<\/span>/is',
            '/<span[^>]*class="[^"]*a-color-price[^"]*"[^>]*>(.*?)<\/span>/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $availability = strip_tags(trim($matches[1]));
                if (!empty($availability)) {
                    return $availability;
                }
            }
        }

        return 'Unknown';
    }

    /**
     * Check if product is in stock
     */
    private function isInStock(string $html): bool
    {
        $inStockIndicators = [
            'In Stock',
            'En stock',
            'Auf Lager',
            'Disponibile',
            'En existencia',
            'Add to Cart',
            'Ajouter au panier',
            'In den Einkaufswagen',
        ];

        $outOfStockIndicators = [
            'Currently unavailable',
            'Out of Stock',
            'Temporairement en rupture',
            'Derzeit nicht verfügbar',
            'Non disponibile',
            'Agotado',
        ];

        $htmlLower = strtolower($html);

        foreach ($outOfStockIndicators as $indicator) {
            if (str_contains($htmlLower, strtolower($indicator))) {
                return false;
            }
        }

        foreach ($inStockIndicators as $indicator) {
            if (str_contains($htmlLower, strtolower($indicator))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract stock quantity
     */
    private function extractStockQuantity(string $html): ?int
    {
        $patterns = [
            '/Only\s+(\d+)\s+left\s+in\s+stock/i',
            '/(\d+)\s+disponibles?/i',
            '/Nur\s+noch\s+(\d+)\s+auf\s+Lager/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    /**
     * Extract main image URL
     */
    private function extractImageUrl(string $html): ?string
    {
        $patterns = [
            '/"hiRes":"(https:\/\/[^"]+\.jpg)"/i',
            '/"large":"(https:\/\/[^"]+\.jpg)"/i',
            '/<img[^>]*id="landingImage"[^>]*src="([^"]+)"/i',
            '/<img[^>]*class="[^"]*a-dynamic-image[^"]*"[^>]*src="([^"]+)"/i',
            '/<meta[^>]*property="og:image"[^>]*content="([^"]+)"/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $imageUrl = $matches[1];
                if (!str_contains($imageUrl, 'data:image')) {
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
        
        if (preg_match('/"colorImages":\s*{[^}]*"initial":\s*(\[[^\]]+\])/s', $html, $matches)) {
            $jsonImages = json_decode($matches[1], true);
            if ($jsonImages) {
                foreach ($jsonImages as $img) {
                    if (isset($img['hiRes']) && !empty($img['hiRes'])) {
                        $images[] = $img['hiRes'];
                    } elseif (isset($img['large']) && !empty($img['large'])) {
                        $images[] = $img['large'];
                    }
                }
            }
        }

        if (empty($images)) {
            if (preg_match_all('/"(https:\/\/[^"]+_AC_[^"]+\.jpg)"/i', $html, $matches)) {
                $images = array_unique($matches[1]);
            }
        }

        return array_values(array_unique($images));
    }

    /**
     * Extract product description
     */
    private function extractDescription(string $html): ?string
    {
        $patterns = [
            '/<div[^>]*id="feature-bullets"[^>]*>(.*?)<\/div>/is',
            '/<div[^>]*id="productDescription"[^>]*>(.*?)<\/div>/is',
            '/<meta[^>]*name="description"[^>]*content="([^"]+)"/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $description = strip_tags(trim($matches[1]));
                if (!empty($description)) {
                    return $description;
                }
            }
        }

        return null;
    }

    /**
     * Extract product features
     */
    private function extractFeatures(string $html): array
    {
        $features = [];
        
        if (preg_match('/<div[^>]*id="feature-bullets"[^>]*>(.*?)<\/div>/is', $html, $matches)) {
            if (preg_match_all('/<li[^>]*><span[^>]*>(.*?)<\/span>/is', $matches[1], $items)) {
                foreach ($items[1] as $item) {
                    $feature = strip_tags(trim($item));
                    if (!empty($feature)) {
                        $features[] = $feature;
                    }
                }
            }
        }

        return $features;
    }

    /**
     * Extract rating (stars)
     */
    private function extractRating(string $html): ?float
    {
        $patterns = [
            '/<span[^>]*class="[^"]*a-icon-alt[^"]*"[^>]*>([\d.,]+)\s+out\s+of\s+5\s+stars/i',
            '/<span[^>]*class="[^"]*a-icon-alt[^"]*"[^>]*>([\d.,]+)\s+sur\s+5\s+étoiles/i',
            '/<span[^>]*class="[^"]*a-icon-alt[^"]*"[^>]*>([\d.,]+)\s+von\s+5\s+Sternen/i',
            '/<i[^>]*class="[^"]*a-star[^"]*"[^>]*>.*?([\d.,]+)\s+out\s+of/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $rating = str_replace(',', '.', $matches[1]);
                return (float) $rating;
            }
        }

        return null;
    }

    /**
     * Extract rating count
     */
    private function extractRatingCount(string $html): ?int
    {
        $patterns = [
            '/<span[^>]*id="acrCustomerReviewText"[^>]*>([\d,]+)\s+ratings?/i',
            '/<span[^>]*id="acrCustomerReviewText"[^>]*>([\d\s.]+)\s+évaluations?/i',
            '/<span[^>]*id="acrCustomerReviewText"[^>]*>([\d\s.]+)\s+Sternebewertungen?/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $count = preg_replace('/[^\d]/', '', $matches[1]);
                return (int) $count;
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
            '/(\d+)\s+customer\s+reviews?/i',
            '/(\d+)\s+commentaires?\s+client/i',
            '/(\d+)\s+Kundenrezensionen?/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    /**
     * Extract product categories
     */
    private function extractCategories(string $html): array
    {
        $categories = [];
        
        if (preg_match('/<div[^>]*id="wayfinding-breadcrumbs_feature_div"[^>]*>(.*?)<\/div>/is', $html, $matches)) {
            if (preg_match_all('/<a[^>]*>(.*?)<\/a>/is', $matches[1], $links)) {
                foreach ($links[1] as $link) {
                    $category = strip_tags(trim($link));
                    if (!empty($category)) {
                        $categories[] = $category;
                    }
                }
            }
        }

        return $categories;
    }

    /**
     * Extract brand
     */
    private function extractBrand(string $html): ?string
    {
        $patterns = [
            '/<a[^>]*id="bylineInfo"[^>]*>(.*?)<\/a>/is',
            '/<span[^>]*class="[^"]*a-size-base[^"]*po-break-word[^"]*"[^>]*>(.*?)<\/span>/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $brand = strip_tags(trim($matches[1]));
                $brand = preg_replace('/^(Brand:\s*|Marque\s*:\s*|Marke:\s*)/i', '', $brand);
                if (!empty($brand)) {
                    return $brand;
                }
            }
        }

        return null;
    }

    /**
     * Extract specifications
     */
    private function extractSpecifications(string $html): array
    {
        $specs = [];
        
        if (preg_match('/<table[^>]*id="productDetails_techSpec_section_1"[^>]*>(.*?)<\/table>/is', $html, $matches)) {
            if (preg_match_all('/<tr[^>]*>.*?<th[^>]*>(.*?)<\/th>.*?<td[^>]*>(.*?)<\/td>/is', $matches[1], $rows)) {
                foreach ($rows[1] as $index => $key) {
                    $key = strip_tags(trim($key));
                    $value = strip_tags(trim($rows[2][$index]));
                    if (!empty($key) && !empty($value)) {
                        $specs[$key] = $value;
                    }
                }
            }
        }

        return $specs;
    }

    /**
     * Extract seller information
     */
    private function extractSeller(string $html): ?string
    {
        $patterns = [
            '/<div[^>]*id="merchant-info"[^>]*>.*?<a[^>]*>(.*?)<\/a>/is',
            '/<span[^>]*>Ships from and sold by\s+(.*?)<\/span>/is',
            '/<span[^>]*>Expédié et vendu par\s+(.*?)<\/span>/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $seller = strip_tags(trim($matches[1]));
                if (!empty($seller)) {
                    return $seller;
                }
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
     * Fetch product page HTML with ADVANCED anti-detection
     */
    private function fetchProductPage(string $url): string
    {
        // 🎲 Délai aléatoire intelligent (1-3 secondes)
        $delay = rand(1000000, 3000000);
        usleep($delay);
        
        $userAgent = $this->userAgents[array_rand($this->userAgents)];
        $marketplace = $this->extractMarketplaceFromUrl($url);
        
        // Extraire le domaine Amazon pour le Referer
        $parsedUrl = parse_url($url);
        $baseUrl = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? 'www.amazon.com');
        
        // 🎯 Varier le referer pour paraître plus naturel
        $referers = [
            $baseUrl . '/',
            $baseUrl . '/s?k=search',
            'https://www.google.com/',
            'https://www.google.' . strtolower($marketplace === 'UK' ? 'co.uk' : ($marketplace === 'US' ? 'com' : strtolower($marketplace))) . '/',
        ];
        $referer = $referers[array_rand($referers)];
        
        // 🌍 Accept-Language basé sur le marketplace
        $acceptLanguage = $this->getAcceptLanguageForMarketplace($marketplace);
        
        // 🔥 Headers ultra-réalistes
        $headers = [
            'User-Agent' => $userAgent,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => $acceptLanguage,
            'Accept-Encoding' => 'gzip, deflate, br, zstd',
            'DNT' => '1',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => rand(0, 1) ? 'none' : 'same-origin', // Varier
            'Sec-Fetch-User' => '?1',
            'sec-ch-ua' => '"Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'Cache-Control' => 'max-age=0',
            'Referer' => $referer,
        ];
        
        Log::info("🚀 Fetching with anti-detection", [
            'url' => substr($url, 0, 100) . '...',
            'marketplace' => $marketplace,
            'user_agent_preview' => substr($userAgent, 0, 50) . '...',
            'referer' => $referer,
            'accept_language' => $acceptLanguage,
        ]);
        
        try {
            $response = Http::withHeaders($headers)
                ->withOptions([
                    'verify' => true,
                    'timeout' => 30,
                    'connect_timeout' => 10,
                    'allow_redirects' => [
                        'max' => 5,
                        'strict' => false,
                        'referer' => true,
                        'track_redirects' => true,
                    ],
                    'http_errors' => false,
                ])
                ->retry(2, 2000, function ($exception) {
                    // Retry seulement sur timeout ou erreur réseau
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                })
                ->get($url);

            if (!$response->successful()) {
                Log::warning("❌ HTTP request failed", [
                    'status' => $response->status(),
                    'url' => substr($url, 0, 100),
                    'headers' => $response->headers(),
                ]);
                throw new Exception("HTTP {$response->status()} error");
            }

            $html = $response->body();

            if ($this->isCaptchaPage($html)) {
                Log::error("⚠️ CAPTCHA DETECTED!", [
                    'url' => substr($url, 0, 100),
                    'marketplace' => $marketplace,
                    'user_agent' => substr($userAgent, 0, 60),
                ]);
                throw new Exception('🤖 Amazon captcha detected - automated access blocked. Please wait and retry later.');
            }

            Log::info("✅ Successfully fetched page", [
                'url' => substr($url, 0, 100),
                'html_size' => strlen($html),
            ]);

            return $html;
            
        } catch (Exception $e) {
            Log::error("💥 Fetch exception", [
                'url' => substr($url, 0, 100),
                'error' => $e->getMessage(),
                'type' => get_class($e),
            ]);
            throw $e;
        }
    }
    
    /**
     * Get Accept-Language header based on marketplace
     */
    private function getAcceptLanguageForMarketplace(string $marketplace): string
    {
        return match ($marketplace) {
            'FR' => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
            'DE' => 'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
            'ES' => 'es-ES,es;q=0.9,en-US;q=0.8,en;q=0.7',
            'IT' => 'it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
            'UK' => 'en-GB,en;q=0.9,en-US;q=0.8',
            'BR' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'IN' => 'en-IN,en;q=0.9,hi;q=0.8,en-US;q=0.7',
            'CA' => 'en-CA,en;q=0.9,fr-CA;q=0.8,fr;q=0.7',
            'EU' => 'en-GB,en;q=0.9,de;q=0.8,fr;q=0.7',
            default => 'en-US,en;q=0.9',
        };
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
     * Scrape with intelligent retry and exponential backoff
     */
    public function scrapeProductWithRetry(string $url, int $maxRetries = 3): array
    {
        $lastError = null;
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            Log::info("🔄 Scraping attempt {$attempt}/{$maxRetries}", [
                'url' => substr($url, 0, 100) . '...',
            ]);
            
            // Premier essai avec cache, les suivants sans cache
            $result = $this->scrapeProduct($url, $attempt === 1);
            
            if ($result['success']) {
                Log::info("✅ Success on attempt {$attempt}", [
                    'title' => $result['data']['title'] ?? 'Unknown',
                ]);
                return $result;
            }
            
            $lastError = $result['error'] ?? 'Unknown error';
            
            // Si c'est un captcha, attendre BEAUCOUP plus longtemps
            if (str_contains(strtolower($lastError), 'captcha')) {
                if ($attempt < $maxRetries) {
                    // Backoff très long pour captcha : 5, 10, 20 secondes
                    $waitSeconds = pow(2, $attempt) * 5;
                    Log::warning("⚠️ Captcha on attempt {$attempt}, waiting {$waitSeconds}s before retry");
                    sleep($waitSeconds);
                }
            } 
            // Erreur HTTP : backoff modéré
            elseif (preg_match('/HTTP (\d+)/', $lastError, $matches)) {
                $statusCode = (int) $matches[1];
                
                // 429 Too Many Requests : attendre beaucoup
                if ($statusCode === 429) {
                    $waitSeconds = 30 + ($attempt * 10); // 40s, 50s, 60s
                    Log::warning("⏳ Rate limited (429), waiting {$waitSeconds}s");
                    if ($attempt < $maxRetries) {
                        sleep($waitSeconds);
                    }
                }
                // 503 Service Unavailable : attendre moyennement
                elseif ($statusCode === 503) {
                    $waitSeconds = 10 + ($attempt * 5); // 15s, 20s, 25s
                    Log::warning("🔧 Service unavailable (503), waiting {$waitSeconds}s");
                    if ($attempt < $maxRetries) {
                        sleep($waitSeconds);
                    }
                }
                // Autres erreurs : backoff standard
                else {
                    $waitSeconds = pow(2, $attempt); // 2s, 4s, 8s
                    if ($attempt < $maxRetries) {
                        Log::info("⏱️ HTTP error, waiting {$waitSeconds}s");
                        sleep($waitSeconds);
                    }
                }
            }
            // Autres erreurs : backoff standard avec jitter
            else {
                if ($attempt < $maxRetries) {
                    $waitSeconds = pow(2, $attempt) + rand(1, 3); // 3-5s, 5-7s, 9-11s
                    Log::info("⏱️ Error, waiting {$waitSeconds}s before retry");
                    sleep($waitSeconds);
                }
            }
        }
        
        Log::error("❌ All {$maxRetries} attempts failed", [
            'url' => substr($url, 0, 100),
            'last_error' => $lastError,
        ]);
        
        return [
            'success' => false,
            'error' => "Failed after {$maxRetries} attempts: {$lastError}",
            'url' => $url,
        ];
    }
}