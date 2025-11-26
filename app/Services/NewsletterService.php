<?php

namespace App\Services;

use App\Models\User;
use App\Models\Product;
use App\Models\Alert;
use App\Services\AffiliateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NewsletterService
{
    protected AffiliateService $affiliateService;

    public function __construct(AffiliateService $affiliateService)
    {
        $this->affiliateService = $affiliateService;
    }

    /**
     * Get trending products for a marketplace
     */
    public function getTrendingProducts(string $marketplace, ?string $category = null, int $limit = 10): array
    {
        $thirtyDaysAgo = now()->subDays(30);
        
        $trendingProducts = Product::select('products.*', DB::raw('COUNT(alerts.id) as alert_count'))
            ->join('alerts', 'products.id', '=', 'alerts.product_id')
            ->where('products.marketplace', $marketplace)
            ->where('products.is_active', true)
            ->whereNotNull('alerts.triggered_at')
            ->where('alerts.triggered_at', '>=', $thirtyDaysAgo)
            ->groupBy('products.id')
            ->orderByDesc('alert_count')
            ->limit($limit)
            ->get();

        $result = [];
        foreach ($trendingProducts as $product) {
            $priceDropPercent = null;
            if ($product->current_price && $product->target_price) {
                if ($product->current_price < $product->target_price) {
                    $priceDropPercent = (($product->target_price - $product->current_price) / $product->target_price) * 100;
                }
            }

            $affiliateData = $this->affiliateService->getBuyItButtonData($product);
            
            $result[] = [
                'title' => $product->title,
                'description' => substr($product->title ?? '', 0, 100),
                'current_price' => $product->current_price,
                'target_price' => $product->target_price,
                'price_drop_percent' => $priceDropPercent ? round($priceDropPercent, 2) : null,
                'alert_count' => $product->alert_count,
                'affiliate_link' => $affiliateData['affiliate_link'],
                'image_url' => $product->image_url,
                'amazon_url' => $product->amazon_url,
            ];
        }

        return $result;
    }

    /**
     * Generate trend report for marketplace
     */
    public function generateTrendReport(string $marketplace, ?string $category = null): array
    {
        $trendingProducts = $this->getTrendingProducts($marketplace, $category);
        
        return [
            'marketplace' => $marketplace,
            'category' => $category,
            'period' => 'monthly',
            'generated_at' => now()->toIso8601String(),
            'trending_products' => $trendingProducts,
            'summary' => [
                'total_products' => count($trendingProducts),
                'average_price_drop' => $this->calculateAvgPriceDrop($trendingProducts),
            ],
        ];
    }

    /**
     * Calculate average price drop
     */
    protected function calculateAvgPriceDrop(array $products): ?float
    {
        if (empty($products)) {
            return null;
        }

        $drops = array_filter(array_column($products, 'price_drop_percent'));
        
        return !empty($drops) ? array_sum($drops) / count($drops) : null;
    }

    /**
     * Generate newsletter content for user
     */
    public function generateNewsletterContent(User $user): array
    {
        $userProducts = $user->products()->where('is_active', true)->get();
        
        $newsletterContent = [
            'user_name' => $user->first_name . ' ' . $user->last_name ?: 'User',
            'generated_at' => now()->toIso8601String(),
            'sections' => [],
        ];

        // Section 1: Tracked products
        if ($userProducts->isNotEmpty()) {
            $trackedSection = [
                'title' => 'Your Tracked Products',
                'products' => [],
            ];

            foreach ($userProducts as $product) {
                $affiliateData = $this->affiliateService->getBuyItButtonData($product, $user->id);
                
                $trackedSection['products'][] = [
                    'title' => $product->title,
                    'current_price' => $product->current_price,
                    'target_price' => $product->target_price,
                    'price_change' => $this->calculatePriceChange($product),
                    'affiliate_link' => $affiliateData['affiliate_link'],
                    'image_url' => $product->image_url,
                ];
            }

            $newsletterContent['sections'][] = $trackedSection;
        }

        // Section 2: Trends by marketplace
        $userMarketplaces = $userProducts->pluck('marketplace')->unique()->filter();
        
        foreach ($userMarketplaces as $marketplace) {
            $trendReport = $this->generateTrendReport($marketplace);
            if (!empty($trendReport['trending_products'])) {
                $trendSection = [
                    'title' => "Trending Products - {$marketplace}",
                    'marketplace' => $marketplace,
                    'products' => [],
                ];

                foreach (array_slice($trendReport['trending_products'], 0, 5) as $trendProduct) {
                    $trendSection['products'][] = [
                        'title' => $trendProduct['title'] ?? null,
                        'description' => substr($trendProduct['description'] ?? '', 0, 100),
                        'current_price' => $trendProduct['current_price'] ?? null,
                        'price_drop_percent' => $trendProduct['price_drop_percent'] ?? null,
                        'affiliate_link' => $trendProduct['affiliate_link'] ?? null,
                        'image_url' => $trendProduct['image_url'] ?? null,
                    ];
                }

                $newsletterContent['sections'][] = $trendSection;
            }
        }

        return $newsletterContent;
    }

    /**
     * Calculate price change for product
     */
    protected function calculatePriceChange(Product $product): ?array
    {
        // TODO: Use price history if available
        return null;
    }

    /**
     * Get users for newsletter
     */
    public function getUsersForNewsletter(): \Illuminate\Database\Eloquent\Collection
    {
        return User::where('newsletter_consent', true)
            ->where('is_active', true)
            ->get();
    }
}

