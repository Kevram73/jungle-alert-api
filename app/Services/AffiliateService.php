<?php

namespace App\Services;

use App\Models\Product;
use App\Models\AffiliateClick;
use Carbon\Carbon;

class AffiliateService
{
    protected string $affiliateTag;
    protected bool $trackingEnabled;

    public function __construct()
    {
        $this->affiliateTag = config('services.amazon.affiliate_tag', 'junglealert-20');
        $this->trackingEnabled = config('services.amazon.track_affiliate_clicks', true);
    }

    /**
     * Get marketplace domain for a marketplace code
     */
    protected function getMarketplaceDomain(string $marketplace): string
    {
        $domainMap = [
            'US' => 'amazon.com',
            'DE' => 'amazon.de',
            'UK' => 'amazon.co.uk',
            'FR' => 'amazon.fr',
            'IT' => 'amazon.it',
            'ES' => 'amazon.es',
            'BR' => 'amazon.com.br',
            'IN' => 'amazon.in',
            'CA' => 'amazon.ca',
        ];

        return $domainMap[$marketplace] ?? 'amazon.com';
    }

    /**
     * Generate affiliate link for a product
     */
    public function generateAffiliateLink(Product $product, ?int $userId = null): string
    {
        $marketplace = $product->marketplace ?? 'US';
        $domain = $this->getMarketplaceDomain($marketplace);
        $asin = $product->asin ?? $this->extractAsinFromUrl($product->amazon_url);
        
        $baseUrl = "https://www.{$domain}/dp/{$asin}";
        
        // Add affiliate tag
        $affiliateUrl = "{$baseUrl}?tag={$this->affiliateTag}";
        
        // If tracking is enabled, add tracking parameter
        if ($this->trackingEnabled && $userId) {
            $affiliateUrl .= "&ref_=junglealert_{$userId}";
        }
        
        return $affiliateUrl;
    }

    /**
     * Extract ASIN from Amazon URL
     */
    protected function extractAsinFromUrl(string $url): ?string
    {
        // Try to extract ASIN from URL
        if (preg_match('/\/dp\/([A-Z0-9]{10})/', $url, $matches)) {
            return $matches[1];
        }
        if (preg_match('/\/product\/([A-Z0-9]{10})/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Track affiliate click
     */
    public function trackAffiliateClick(int $productId, int $userId): void
    {
        AffiliateClick::create([
            'product_id' => $productId,
            'user_id' => $userId,
            'clicked_at' => now(),
        ]);
    }

    /**
     * Get BUY IT button data
     */
    public function getBuyItButtonData(Product $product, ?int $userId = null): array
    {
        $affiliateLink = $this->generateAffiliateLink($product, $userId);
        
        return [
            'affiliate_link' => $affiliateLink,
            'product_title' => $product->title,
            'current_price' => $product->current_price,
            'marketplace' => $product->marketplace ?? 'US',
            'disclaimer' => 'As an Amazon Associate, JungleAlert earns from qualifying purchases.',
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

