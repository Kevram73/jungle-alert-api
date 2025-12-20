<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class AmazonScrapingService
{
    private array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:132.0) Gecko/20100101 Firefox/132.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.1 Safari/605.1.15',
    ];

    private function fetchProductPageWithProxy(string $url): string
    {
        $scraperApiKey = env('SCRAPER_API_KEY');
        
        if ($scraperApiKey) {
            return $this->fetchWithScraperAPI($url, $scraperApiKey);
        }
        
        return $this->fetchWithAdvancedEvasion($url);
    }

    private function fetchWithScraperAPI(string $url, string $apiKey): string
    {
        $scraperUrl = "http://api.scraperapi.com/";
        
        try {
            $response = Http::timeout(60)->get($scraperUrl, [
                'api_key' => $apiKey,
                'url' => $url,
                'render' => false,
                'country_code' => 'us',
            ]);

            if (!$response->successful()) {
                throw new Exception("ScraperAPI failed: " . $response->status());
            }

            return $response->body();
        } catch (Exception $e) {
            Log::error("ScraperAPI error: " . $e->getMessage());
            throw $e;
        }
    }

    private function fetchWithAdvancedEvasion(string $url): string
    {
        $delay = rand(5000000, 10000000);
        usleep($delay);
        
        $userAgent = $this->userAgents[array_rand($this->userAgents)];
        $marketplace = $this->extractMarketplaceFromUrl($url);
        
        $parsedUrl = parse_url($url);
        $baseUrl = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? 'www.amazon.com');
        
        $referer = 'https://www.google.com/search?q=amazon+product';
        
        $acceptLanguage = $this->getAcceptLanguageForMarketplace($marketplace);
        
        $headers = [
            'User-Agent' => $userAgent,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language' => $acceptLanguage,
            'Accept-Encoding' => 'gzip, deflate, br',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'cross-site',
            'Sec-Fetch-User' => '?1',
            'Cache-Control' => 'max-age=0',
            'Referer' => $referer,
        ];
        
        Log::info("🚀 Fetching with EXTREME evasion", [
            'url' => substr($url, 0, 80),
            'delay_seconds' => $delay / 1000000,
        ]);
        
        try {
            $response = Http::withHeaders($headers)
                ->withOptions([
                    'verify' => true,
                    'timeout' => 45,
                    'connect_timeout' => 15,
                    'allow_redirects' => true,
                ])
                ->get($url);

            if (!$response->successful()) {
                throw new Exception("HTTP {$response->status()} error");
            }

            $html = $response->body();

            if ($this->isCaptchaPage($html)) {
                throw new Exception('🤖 Amazon captcha detected');
            }

            return $html;
            
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function scrapeProduct(string $url, bool $useCache = true): array
    {
        try {
            $url = $this->normalizeAmazonUrl($url);
            
            if ($this->isShortUrl($url)) {
                $resolvedUrl = $this->resolveShortUrl($url);
                if ($resolvedUrl) {
                    $url = $this->normalizeAmazonUrl($resolvedUrl);
                }
            }

            if ($useCache) {
                $cacheKey = 'amazon_enriched_' . md5($url);
                $cachedData = Cache::get($cacheKey);
                
                if ($cachedData !== null) {
                    return [
                        'success' => true,
                        'data' => $cachedData,
                        'cached' => true,
                    ];
                }
            }

            $asin = $this->extractAsinFromUrl($url);
            if (!$asin) {
                throw new Exception('Could not extract ASIN from URL');
            }

            $marketplace = $this->extractMarketplaceFromUrl($url);
            $country = $this->getCountryFromMarketplace($marketplace);

            $html = $this->fetchProductPageWithProxy($url);

            $productData = $this->extractAllProductData($html, $url, $asin, $marketplace, $country);
            
            if (empty($productData['image_url']) && !empty($productData['images'])) {
                $productData['image_url'] = $productData['images'][0];
            }

            if (!$this->isValidProductData($productData)) {
                throw new Exception('Scraped data is incomplete or invalid');
            }

            if ($useCache) {
                $cacheKey = 'amazon_enriched_' . md5($url);
                Cache::put($cacheKey, $productData, now()->addHours(1));
            }

            return [
                'success' => true,
                'data' => $productData,
                'cached' => false,
            ];

        } catch (Exception $e) {
            Log::error('❌ Scraping error', [
                'message' => $e->getMessage(),
                'url' => substr($url, 0, 100),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'url' => $url,
            ];
        }
    }

    public function scrapeProductWithRetry(string $url, int $maxRetries = 2): array
    {
        $lastError = null;
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            Log::info("🔄 Attempt {$attempt}/{$maxRetries}");
            
            $result = $this->scrapeProduct($url, $attempt === 1);
            
            if ($result['success']) {
                return $result;
            }
            
            $lastError = $result['error'] ?? 'Unknown error';
            
            if ($attempt < $maxRetries) {
                $waitSeconds = 30 + rand(0, 30);
                Log::warning("⏳ Waiting {$waitSeconds}s before retry");
                sleep($waitSeconds);
            }
        }
        
        return [
            'success' => false,
            'error' => "Failed after {$maxRetries} attempts: {$lastError}",
            'url' => $url,
        ];
    }

    private function normalizeAmazonUrl(string $url): string
    {
        $asin = $this->extractAsinFromUrl($url);
        if (!$asin) {
            return $url;
        }
        $baseUrl = $this->getAmazonBaseUrl($url);
        return "{$baseUrl}/dp/{$asin}";
    }

    private function getAmazonBaseUrl(?string $url = null): string
    {
        if ($url) {
            $host = parse_url($url, PHP_URL_HOST) ?? '';
            $host = strtolower(str_replace('m.amazon', 'www.amazon', $host));
            
            $domains = [
                'amazon.fr' => 'https://www.amazon.fr',
                'amazon.de' => 'https://www.amazon.de',
                'amazon.co.uk' => 'https://www.amazon.co.uk',
                'amazon.it' => 'https://www.amazon.it',
                'amazon.es' => 'https://www.amazon.es',
                'amazon.com' => 'https://www.amazon.com',
            ];

            foreach ($domains as $domain => $base) {
                if (str_contains($host, $domain)) {
                    return $base;
                }
            }
        }
        return 'https://www.amazon.com';
    }

    private function extractAsinFromUrl(string $url): ?string
    {
        $patterns = [
            '/\/dp\/([A-Z0-9]{10})/',
            '/\/product\/([A-Z0-9]{10})/',
            '/\/gp\/product\/([A-Z0-9]{10})/',
            '/\/gp\/aw\/d\/([A-Z0-9]{10})/',
            '/\/aw\/d\/([A-Z0-9]{10})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    private function extractMarketplaceFromUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        $host = strtolower($host);
        
        if (str_contains($host, 'amazon.fr')) return 'FR';
        if (str_contains($host, 'amazon.de')) return 'DE';
        if (str_contains($host, 'amazon.co.uk')) return 'UK';
        if (str_contains($host, 'amazon.it')) return 'IT';
        if (str_contains($host, 'amazon.es')) return 'ES';
        
        return 'US';
    }

    private function getCountryFromMarketplace(string $marketplace): string
    {
        return match ($marketplace) {
            'FR' => 'France',
            'DE' => 'Germany',
            'UK' => 'United Kingdom',
            'IT' => 'Italy',
            'ES' => 'Spain',
            default => 'United States',
        };
    }

    private function getCurrencyFromMarketplace(string $marketplace): string
    {
        return match ($marketplace) {
            'FR', 'DE', 'IT', 'ES' => 'EUR',
            'UK' => 'GBP',
            default => 'USD',
        };
    }

    private function getAcceptLanguageForMarketplace(string $marketplace): string
    {
        return match ($marketplace) {
            'FR' => 'fr-FR,fr;q=0.9,en;q=0.8',
            'DE' => 'de-DE,de;q=0.9,en;q=0.8',
            'UK' => 'en-GB,en;q=0.9',
            'IT' => 'it-IT,it;q=0.9,en;q=0.8',
            'ES' => 'es-ES,es;q=0.9,en;q=0.8',
            default => 'en-US,en;q=0.9',
        };
    }

    private function isShortUrl(string $url): bool
    {
        return str_contains($url, 'amzn.to') || str_contains($url, 'a.co');
    }

    private function resolveShortUrl(string $shortUrl): ?string
    {
        try {
            $response = Http::timeout(10)->get($shortUrl);
            return $response->effectiveUri();
        } catch (Exception $e) {
            return null;
        }
    }

    private function isCaptchaPage(string $html): bool
    {
        $indicators = ['captcha', 'robot check', 'automated access', 'unusual traffic'];
        $htmlLower = strtolower($html);
        
        foreach ($indicators as $indicator) {
            if (str_contains($htmlLower, $indicator)) {
                return true;
            }
        }
        return false;
    }

    private function isValidProductData(array $data): bool
    {
        return !empty($data['title']) && !empty($data['asin']);
    }

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

    private function extractTitle(string $html): ?string
    {
        $patterns = [
            '/<span[^>]*id="productTitle"[^>]*>(.*?)<\/span>/is',
            '/<h1[^>]*id="title"[^>]*>(.*?)<\/h1>/is',
            '/<title[^>]*>(.*?)\s*:\s*Amazon\./is',
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

    private function extractPrice(string $html, string $url): ?float
    {
        $marketplace = $this->extractMarketplaceFromUrl($url);
        
        $patterns = [
            '/<span[^>]*class="[^"]*a-price-whole[^"]*"[^>]*>([\d\s.,]+)<\/span>/i',
            '/<span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>.*?([\d\s.,]+).*?<\/span>/i',
            '/<span[^>]*id="priceblock_ourprice"[^>]*>([\d\s.,]+)<\/span>/i',
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

    private function parsePrice(string $priceString, string $marketplace): float
    {
        $priceString = preg_replace('/\s+/', '', $priceString);
        
        if (in_array($marketplace, ['FR', 'DE', 'IT', 'ES'])) {
            $priceString = str_replace('.', '', $priceString);
            $priceString = str_replace(',', '.', $priceString);
        } else {
            $priceString = str_replace(',', '', $priceString);
        }
        
        $priceString = preg_replace('/[^\d.]/', '', $priceString);
        
        return (float) $priceString;
    }

    private function extractOriginalPrice(string $html): ?float
    {
        $patterns = [
            '/<span[^>]*class="[^"]*a-price[^"]*a-text-price[^"]*"[^>]*>.*?<span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>([\d\s.,]+)<\/span>/is',
            '/<span[^>]*class="[^"]*a-text-strike[^"]*"[^>]*>([\d\s.,]+)<\/span>/i',
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

    private function extractDiscountPercentage(string $html): ?int
    {
        if (preg_match('/-(\d+)%/', $html, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    private function extractAvailability(string $html): string
    {
        $patterns = [
            '/<div[^>]*id="availability"[^>]*>.*?<span[^>]*>(.*?)<\/span>/is',
            '/<span[^>]*class="[^"]*a-color-success[^"]*"[^>]*>(.*?)<\/span>/is',
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

    private function isInStock(string $html): bool
    {
        $inStockIndicators = [
            'In Stock',
            'En stock',
            'Auf Lager',
            'Disponibile',
            'Add to Cart',
        ];

        $outOfStockIndicators = [
            'Currently unavailable',
            'Out of Stock',
            'Temporairement en rupture',
            'Derzeit nicht verfügbar',
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

    private function extractStockQuantity(string $html): ?int
    {
        if (preg_match('/Only\s+(\d+)\s+left\s+in\s+stock/i', $html, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    private function extractImageUrl(string $html): ?string
    {
        $patterns = [
            '/"hiRes":"(https:\/\/[^"]+\.jpg)"/i',
            '/"large":"(https:\/\/[^"]+\.jpg)"/i',
            '/<img[^>]*id="landingImage"[^>]*src="([^"]+)"/i',
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

        return array_values(array_unique($images));
    }

    private function extractDescription(string $html): ?string
    {
        $patterns = [
            '/<div[^>]*id="feature-bullets"[^>]*>(.*?)<\/div>/is',
            '/<div[^>]*id="productDescription"[^>]*>(.*?)<\/div>/is',
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

    private function extractRating(string $html): ?float
    {
        $patterns = [
            '/<span[^>]*class="[^"]*a-icon-alt[^"]*"[^>]*>([\d.,]+)\s+out\s+of\s+5\s+stars/i',
            '/<span[^>]*class="[^"]*a-icon-alt[^"]*"[^>]*>([\d.,]+)\s+sur\s+5\s+étoiles/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $rating = str_replace(',', '.', $matches[1]);
                return (float) $rating;
            }
        }
        return null;
    }

    private function extractRatingCount(string $html): ?int
    {
        if (preg_match('/<span[^>]*id="acrCustomerReviewText"[^>]*>([\d,\s.]+)\s+/i', $html, $matches)) {
            $count = preg_replace('/[^\d]/', '', $matches[1]);
            return (int) $count;
        }
        return null;
    }

    private function extractReviewCount(string $html): ?int
    {
        if (preg_match('/(\d+)\s+customer\s+reviews?/i', $html, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

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

    private function extractBrand(string $html): ?string
    {
        if (preg_match('/<a[^>]*id="bylineInfo"[^>]*>(.*?)<\/a>/is', $html, $matches)) {
            $brand = strip_tags(trim($matches[1]));
            $brand = preg_replace('/^(Brand:\s*|Marque\s*:\s*)/i', '', $brand);
            if (!empty($brand)) {
                return $brand;
            }
        }
        return null;
    }

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

    private function extractSeller(string $html): ?string
    {
        $patterns = [
            '/<div[^>]*id="merchant-info"[^>]*>.*?<a[^>]*>(.*?)<\/a>/is',
            '/<span[^>]*>Ships from and sold by\s+(.*?)<\/span>/is',
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

    private function isPrimeEligible(string $html): bool
    {
        return (bool) preg_match('/<i[^>]*class="[^"]*a-icon-prime[^"]*"/i', $html);
    }

    public function validateAmazonUrl(string $url): bool
    {
        $amazonDomains = [
            'amazon.com', 'amazon.de', 'amazon.co.uk', 'amazon.fr',
            'amazon.it', 'amazon.es', 'a.co', 'amzn.to',
        ];

        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        
        foreach ($amazonDomains as $domain) {
            if (str_contains($host, $domain)) {
                return true;
            }
        }

        return false;
    }
}