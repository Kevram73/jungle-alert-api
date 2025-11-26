<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class GdprController extends Controller
{
    /**
     * Delete user account (GDPR compliance)
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        // Verify password
        if (!Hash::check($request->password, $user->hashed_password)) {
            return response()->json([
                'message' => 'Password is incorrect'
            ], 400);
        }

        // Delete all products (soft delete by setting is_active to false)
        $user->products()->update(['is_active' => false]);

        // Delete all alerts
        $user->alerts()->delete();

        // Delete all subscriptions
        $user->subscriptions()->delete();

        // Deactivate user account
        $user->is_active = false;
        $user->email = "deleted_{$user->id}_{$user->email}";
        $user->first_name = null;
        $user->last_name = null;
        $user->whatsapp_number = null;
        $user->fcm_token = null;
        $user->newsletter_consent = false;
        $user->save();

        // Delete all user tokens
        $user->tokens()->delete();

        return response()->json(null, 204);
    }

    /**
     * Export all user data (GDPR compliance)
     */
    public function exportData(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = [
            'user' => [
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'subscription_tier' => $user->subscription_tier,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'products' => $user->products()->get()->map(function ($product) {
                return [
                    'title' => $product->title,
                    'amazon_url' => $product->amazon_url,
                    'target_price' => $product->target_price,
                    'current_price' => $product->current_price,
                    'created_at' => $product->created_at?->toIso8601String(),
                ];
            }),
            'alerts' => $user->alerts()->get()->map(function ($alert) {
                return [
                    'alert_type' => $alert->alert_type,
                    'target_price' => $alert->target_price,
                    'is_active' => $alert->is_active,
                    'triggered_at' => $alert->triggered_at?->toIso8601String(),
                    'created_at' => $alert->created_at?->toIso8601String(),
                ];
            }),
            'subscriptions' => $user->subscriptions()->get()->map(function ($subscription) {
                return [
                    'plan' => $subscription->plan,
                    'status' => $subscription->status,
                    'amount' => $subscription->amount,
                    'starts_at' => $subscription->starts_at?->toIso8601String(),
                    'expires_at' => $subscription->expires_at?->toIso8601String(),
                ];
            }),
        ];

        return response()->json($data);
    }
}

