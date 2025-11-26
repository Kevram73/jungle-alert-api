<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Get current user profile
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'username' => $user->username,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'profile_picture_url' => $user->profile_picture_url,
            'subscription_tier' => $user->subscription_tier,
            'subscription_start_date' => $user->subscription_start_date,
            'subscription_end_date' => $user->subscription_end_date,
            'email_notifications' => $user->email_notifications,
            'whatsapp_notifications' => $user->whatsapp_notifications,
            'push_notifications' => $user->push_notifications,
            'whatsapp_number' => $user->whatsapp_number,
            'is_active' => $user->is_active,
            'is_verified' => $user->is_verified,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'last_login' => $user->last_login,
        ]);
    }

    /**
     * Update user profile
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'username' => ['sometimes', 'string', 'min:3', 'max:100', Rule::unique('users')->ignore($user->id)],
            'email_notifications' => 'sometimes|boolean',
            'whatsapp_notifications' => 'sometimes|boolean',
            'push_notifications' => 'sometimes|boolean',
            'whatsapp_number' => 'sometimes|nullable|string|max:20',
            'fcm_token' => 'sometimes|nullable|string',
        ]);

        $user->update($request->only([
            'first_name',
            'last_name',
            'email',
            'username',
            'email_notifications',
            'whatsapp_notifications',
            'push_notifications',
            'whatsapp_number',
            'fcm_token',
        ]));

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Update FCM token
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();
        $user->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'message' => 'FCM token updated successfully',
            'fcm_token_set' => !empty($user->fcm_token),
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->hashed_password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 400);
        }

        $user->update([
            'hashed_password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Delete user account
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
            'confirm_deletion' => 'required|boolean|accepted',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->hashed_password)) {
            return response()->json([
                'message' => 'Password is incorrect'
            ], 400);
        }

        // Delete user and all related data
        $user->delete();

        return response()->json([
            'message' => 'Account deleted successfully'
        ]);
    }

    /**
     * Get available subscription plans
     */
    public function getSubscriptionPlans(): JsonResponse
    {
        $plans = [
            [
                'tier' => 'FREE',
                'name' => 'Free Plan',
                'price' => 0,
                'features' => [
                    'Track up to 5 products',
                    'Email notifications',
                    'Basic price alerts',
                    'Up to 10 alerts'
                ],
                'max_products' => 5,
                'max_alerts' => 10
            ],
            [
                'tier' => 'PREMIUM_SIMPLE',
                'name' => 'Premium Simple',
                'price' => 9.99,
                'features' => [
                    'Track up to 50 products',
                    'Email & Push notifications',
                    'Advanced price alerts',
                    'Price history charts',
                    'Up to 100 alerts'
                ],
                'max_products' => 50,
                'max_alerts' => 100
            ],
            [
                'tier' => 'PREMIUM_DELUXE',
                'name' => 'Premium Deluxe',
                'price' => 19.99,
                'features' => [
                    'Unlimited product tracking',
                    'All notification channels',
                    'Advanced analytics',
                    'Priority support',
                    'WhatsApp notifications',
                    'Unlimited alerts'
                ],
                'max_products' => -1, // Unlimited
                'max_alerts' => -1 // Unlimited
            ]
        ];

        return response()->json([
            'message' => 'Subscription plans retrieved successfully',
            'plans' => $plans
        ]);
    }

    /**
     * Get current user's subscription limits
     */
    public function getSubscriptionLimits(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $limits = $this->getSubscriptionLimitsForTier($user->subscription_tier);
        $currentCounts = [
            'products' => \App\Models\Product::where('user_id', $user->id)->where('is_active', true)->count(),
            'alerts' => \App\Models\Alert::where('user_id', $user->id)->where('is_active', true)->count()
        ];

        return response()->json([
            'message' => 'Subscription limits retrieved successfully',
            'subscription_tier' => $user->subscription_tier,
            'limits' => $limits,
            'current_usage' => $currentCounts,
            'can_add_product' => $limits['max_products'] === -1 || $currentCounts['products'] < $limits['max_products'],
            'can_add_alert' => $limits['max_alerts'] === -1 || $currentCounts['alerts'] < $limits['max_alerts']
        ]);
    }

    /**
     * Upgrade user subscription
     */
    public function upgradeSubscription(Request $request): JsonResponse
    {
        $request->validate([
            'subscription_tier' => 'required|in:FREE,PREMIUM_SIMPLE,PREMIUM_DELUXE'
        ]);

        $user = $request->user();
        $newTier = $request->subscription_tier;

        if ($user->subscription_tier === $newTier) {
            return response()->json([
                'message' => 'You are already on this subscription tier'
            ], 400);
        }

        // For demo purposes, allow any upgrade without payment
        $user->subscription_tier = $newTier;
        $user->subscription_start_date = now();
        $user->subscription_end_date = now()->addYear();
        $user->save();

        $limits = $this->getSubscriptionLimitsForTier($newTier);

        return response()->json([
            'message' => 'Subscription upgraded successfully',
            'subscription_tier' => $user->subscription_tier,
            'subscription_start_date' => $user->subscription_start_date,
            'subscription_end_date' => $user->subscription_end_date,
            'new_limits' => $limits
        ]);
    }

    /**
     * Get subscription limits for a specific tier
     */
    private function getSubscriptionLimitsForTier(string $tier): array
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
