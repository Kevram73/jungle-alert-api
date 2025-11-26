<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PriceHistory;
use Illuminate\Support\Facades\Log;

class PriceChangeDetectionService
{
    /**
     * Detect if price has changed significantly
     * 
     * @param float|null $oldPrice
     * @param float|null $newPrice
     * @param string|null $currency
     * @return array ['changed' => bool, 'percent_change' => float, 'absolute_change' => float, 'direction' => 'up'|'down'|'stable']
     */
    public function detectPriceChange(?float $oldPrice, ?float $newPrice, ?string $currency = null): array
    {
        // If no old price, consider it a change if we have a new price
        if ($oldPrice === null || $oldPrice == 0) {
            return [
                'changed' => $newPrice !== null && $newPrice > 0,
                'percent_change' => null,
                'absolute_change' => $newPrice ?? 0,
                'direction' => $newPrice !== null && $newPrice > 0 ? 'new' : 'stable',
            ];
        }

        // If no new price, no change
        if ($newPrice === null || $newPrice == 0) {
            return [
                'changed' => false,
                'percent_change' => 0,
                'absolute_change' => 0,
                'direction' => 'stable',
            ];
        }

        $absoluteChange = abs($newPrice - $oldPrice);
        $percentChange = ($absoluteChange / $oldPrice) * 100;
        
        // Get minimum change threshold based on currency
        $minChangePercent = $this->getMinChangePercent($currency);
        $minChangeAbsolute = $this->getMinChangeAbsolute($currency);

        // Consider it a change if:
        // - Percent change > threshold (default 1%)
        // - OR absolute change > minimum (default 0.01)
        $changed = $percentChange > $minChangePercent || $absoluteChange > $minChangeAbsolute;

        $direction = 'stable';
        if ($changed) {
            if ($newPrice > $oldPrice) {
                $direction = 'up';
            } elseif ($newPrice < $oldPrice) {
                $direction = 'down';
            }
        }

        return [
            'changed' => $changed,
            'percent_change' => round($percentChange, 2),
            'absolute_change' => round($absoluteChange, 2),
            'direction' => $direction,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
        ];
    }

    /**
     * Get minimum percentage change threshold based on currency
     */
    protected function getMinChangePercent(?string $currency): float
    {
        // Different thresholds for different currencies
        $thresholds = [
            'USD' => 1.0,  // 1% for USD
            'EUR' => 1.0,  // 1% for EUR
            'GBP' => 1.0,  // 1% for GBP
            'CAD' => 1.0,  // 1% for CAD
            'BRL' => 1.0,  // 1% for BRL
            'INR' => 1.0,  // 1% for INR
        ];

        return $thresholds[$currency] ?? 1.0; // Default 1%
    }

    /**
     * Get minimum absolute change threshold based on currency
     */
    protected function getMinChangeAbsolute(?string $currency): float
    {
        // Different absolute thresholds for different currencies
        $thresholds = [
            'USD' => 0.01,  // $0.01
            'EUR' => 0.01,  // €0.01
            'GBP' => 0.01,  // £0.01
            'CAD' => 0.01,  // C$0.01
            'BRL' => 0.05,  // R$0.05
            'INR' => 0.50,  // ₹0.50
        ];

        return $thresholds[$currency] ?? 0.01; // Default 0.01
    }

    /**
     * Analyze price trend for a product
     */
    public function analyzePriceTrend(Product $product, int $days = 30): array
    {
        $priceHistory = PriceHistory::where('product_id', $product->id)
            ->where('recorded_at', '>=', now()->subDays($days))
            ->orderBy('recorded_at', 'asc')
            ->get();

        if ($priceHistory->isEmpty()) {
            return [
                'trend' => 'insufficient_data',
                'average_price' => $product->current_price,
                'min_price' => $product->current_price,
                'max_price' => $product->current_price,
                'volatility' => 0,
            ];
        }

        $prices = $priceHistory->pluck('price')->toArray();
        if ($product->current_price) {
            $prices[] = $product->current_price;
        }

        $averagePrice = array_sum($prices) / count($prices);
        $minPrice = min($prices);
        $maxPrice = max($prices);
        
        // Calculate volatility (standard deviation)
        $variance = 0;
        foreach ($prices as $price) {
            $variance += pow($price - $averagePrice, 2);
        }
        $volatility = sqrt($variance / count($prices));

        // Determine trend
        $trend = 'stable';
        if (count($prices) >= 2) {
            $firstHalf = array_slice($prices, 0, ceil(count($prices) / 2));
            $secondHalf = array_slice($prices, ceil(count($prices) / 2));
            
            $firstAvg = array_sum($firstHalf) / count($firstHalf);
            $secondAvg = array_sum($secondHalf) / count($secondHalf);
            
            $trendPercent = (($secondAvg - $firstAvg) / $firstAvg) * 100;
            
            if ($trendPercent > 5) {
                $trend = 'increasing';
            } elseif ($trendPercent < -5) {
                $trend = 'decreasing';
            }
        }

        return [
            'trend' => $trend,
            'average_price' => round($averagePrice, 2),
            'min_price' => round($minPrice, 2),
            'max_price' => round($maxPrice, 2),
            'volatility' => round($volatility, 2),
            'data_points' => count($prices),
        ];
    }

    /**
     * Check if price drop is significant enough to trigger alert
     */
    public function isSignificantPriceDrop(float $currentPrice, float $targetPrice, ?string $currency = null): bool
    {
        if ($currentPrice >= $targetPrice) {
            return false; // Price hasn't dropped to target
        }

        $dropAmount = $targetPrice - $currentPrice;
        $dropPercent = ($dropAmount / $targetPrice) * 100;

        // Consider significant if:
        // - Drop is > 5% of target price
        // - OR drop is > minimum absolute threshold
        $minDropPercent = 5.0;
        $minDropAbsolute = $this->getMinChangeAbsolute($currency) * 10; // 10x minimum change

        return $dropPercent > $minDropPercent || $dropAmount > $minDropAbsolute;
    }
}

