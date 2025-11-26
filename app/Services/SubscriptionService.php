<?php

namespace App\Services;

use App\Models\User;
use App\Models\Subscription;
use Carbon\Carbon;
use ValueError;

class SubscriptionService
{
    const PLAN_PRICES = [
        'premium_simple' => 10.0,
        'premium_deluxe' => 30.0,
    ];

    const PLAN_DURATION_DAYS = 365; // 1 an

    /**
     * Get max products for a subscription plan
     */
    public static function getMaxProductsForPlan(string $plan): int
    {
        return match ($plan) {
            'FREE' => 1,
            'PREMIUM_SIMPLE' => 1,
            'PREMIUM_DELUXE' => 999999, // Unlimited
            default => 1,
        };
    }

    /**
     * Check if user can add a product
     */
    public static function canAddProduct(User $user): bool
    {
        // Check if subscription is active
        if ($user->subscription_tier !== 'FREE') {
            if (!$user->subscription_end_date || $user->subscription_end_date < now()) {
                // Subscription expired, revert to free
                $user->subscription_tier = 'FREE';
                $user->subscription_end_date = null;
                $user->save();
            }
        }

        $maxProducts = self::getMaxProductsForPlan($user->subscription_tier);
        $currentCount = $user->products()->where('is_active', true)->count();

        return $currentCount < $maxProducts;
    }

    /**
     * Check if user can add an alert
     */
    public static function canAddAlert(User $user): bool
    {
        // For now, alerts are unlimited for all plans
        // Can be adjusted based on business requirements
        return true;
    }

    /**
     * Create a new subscription
     */
    public static function createSubscription(
        User $user,
        string $planName,
        ?string $paymentReference = null
    ): Subscription {
        if (!isset(self::PLAN_PRICES[$planName])) {
            throw new ValueError("Invalid plan: {$planName}");
        }

        // Create subscription
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan' => $planName,
            'status' => 'active',
            'amount' => self::PLAN_PRICES[$planName],
            'currency' => 'EUR',
            'starts_at' => now(),
            'expires_at' => now()->addDays(self::PLAN_DURATION_DAYS),
            'payment_reference' => $paymentReference,
        ]);

        // Update user
        if ($planName === 'premium_simple') {
            $user->subscription_tier = 'PREMIUM_SIMPLE';
        } elseif ($planName === 'premium_deluxe') {
            $user->subscription_tier = 'PREMIUM_DELUXE';
        }

        $user->subscription_expires_at = $subscription->expires_at;
        $user->subscription_start_date = $subscription->starts_at;
        $user->save();

        return $subscription;
    }

    /**
     * Get allowed alert channels for user
     */
    public static function getAllowedAlertChannels(User $user): array
    {
        if ($user->subscription_tier === 'FREE') {
            return ['email'];
        } else {
            return ['email', 'whatsapp', 'push'];
        }
    }

    /**
     * Get max alerts for plan
     */
    public static function getMaxAlertsForPlan(string $plan): int
    {
        // For now, unlimited for all plans
        return 999999;
    }
}

