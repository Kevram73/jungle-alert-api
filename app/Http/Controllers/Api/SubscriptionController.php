<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    /**
     * Get all user subscriptions
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $subscriptions = Subscription::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($subscriptions);
    }

    /**
     * Create a new subscription
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => 'required|in:premium_simple,premium_deluxe',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        try {
            $subscription = SubscriptionService::createSubscription(
                $user,
                $request->plan,
                $request->payment_reference
            );

            return response()->json($subscription, 201);
        } catch (\ValueError | \Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get available subscription plans
     */
    public function plans(): JsonResponse
    {
        $plans = [
            [
                'plan_id' => 'free',
                'name' => 'Free',
                'price' => 0.0,
                'duration_days' => null,
                'max_products' => 1,
                'features' => ['Email alerts', '1 product tracking'],
            ],
            [
                'plan_id' => 'premium_simple',
                'name' => 'Premium Simple',
                'price' => SubscriptionService::PLAN_PRICES['premium_simple'],
                'duration_days' => SubscriptionService::PLAN_DURATION_DAYS,
                'max_products' => 1,
                'features' => [
                    'Email alerts',
                    'WhatsApp alerts',
                    'Push notifications',
                    '1 product tracking'
                ],
            ],
            [
                'plan_id' => 'premium_deluxe',
                'name' => 'Premium Deluxe',
                'price' => SubscriptionService::PLAN_PRICES['premium_deluxe'],
                'duration_days' => SubscriptionService::PLAN_DURATION_DAYS,
                'max_products' => 999999,
                'features' => [
                    'Email alerts',
                    'WhatsApp alerts',
                    'Push notifications',
                    'Unlimited products'
                ],
            ],
        ];

        return response()->json(['plans' => $plans]);
    }

    /**
     * Get subscription limits
     */
    public function limits(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $maxProducts = SubscriptionService::getMaxProductsForPlan($user->subscription_tier);
        $currentCount = $user->products()->where('is_active', true)->count();
        $allowedChannels = SubscriptionService::getAllowedAlertChannels($user);

        return response()->json([
            'plan' => $user->subscription_tier,
            'max_products' => $maxProducts,
            'current_products' => $currentCount,
            'remaining_products' => max(0, $maxProducts - $currentCount),
            'allowed_alert_channels' => $allowedChannels,
            'subscription_expires_at' => $user->subscription_end_date?->toIso8601String(),
        ]);
    }
}

