<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Product;
use App\Models\Alert;

class CheckSubscriptionLimits
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type = 'product'): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Définir les limites selon le plan d'abonnement
        $limits = $this->getSubscriptionLimits($user->subscription_tier);
        
        switch ($type) {
            case 'product':
                $currentCount = Product::where('user_id', $user->id)->where('is_active', true)->count();
                if ($limits['max_products'] !== -1 && $currentCount >= $limits['max_products']) {
                    return response()->json([
                        'message' => 'Product limit reached',
                        'error' => 'SUBSCRIPTION_LIMIT_REACHED',
                        'details' => "You can track up to {$limits['max_products']} products with your {$user->subscription_tier} plan. Upgrade to track more products.",
                        'current_count' => $currentCount,
                        'max_allowed' => $limits['max_products'],
                        'subscription_tier' => $user->subscription_tier
                    ], 403);
                }
                break;
                
            case 'alert':
                $currentCount = Alert::where('user_id', $user->id)->where('is_active', true)->count();
                if ($limits['max_alerts'] !== -1 && $currentCount >= $limits['max_alerts']) {
                    return response()->json([
                        'message' => 'Alert limit reached',
                        'error' => 'SUBSCRIPTION_LIMIT_REACHED',
                        'details' => "You can create up to {$limits['max_alerts']} alerts with your {$user->subscription_tier} plan. Upgrade to create more alerts.",
                        'current_count' => $currentCount,
                        'max_allowed' => $limits['max_alerts'],
                        'subscription_tier' => $user->subscription_tier
                    ], 403);
                }
                break;
        }

        return $next($request);
    }

    /**
     * Get subscription limits based on tier
     */
    private function getSubscriptionLimits(string $tier): array
    {
        return match ($tier) {
            'FREE' => [
                'max_products' => 5,
                'max_alerts' => 10,
                'features' => ['email_notifications', 'basic_alerts']
            ],
            'PREMIUM_SIMPLE' => [
                'max_products' => 50,
                'max_alerts' => 100,
                'features' => ['email_notifications', 'push_notifications', 'advanced_alerts', 'price_history']
            ],
            'PREMIUM_DELUXE' => [
                'max_products' => -1, // Unlimited
                'max_alerts' => -1, // Unlimited
                'features' => ['email_notifications', 'push_notifications', 'whatsapp_notifications', 'advanced_alerts', 'price_history', 'priority_support', 'analytics']
            ],
            default => [
                'max_products' => 5,
                'max_alerts' => 10,
                'features' => ['email_notifications', 'basic_alerts']
            ]
        };
    }
}