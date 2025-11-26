<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Get dashboard data
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $totalProducts = Product::where('user_id', $user->id)->count();
        $activeAlerts = Alert::where('user_id', $user->id)->where('is_active', true)->count();
        $priceDropsToday = Alert::where('user_id', $user->id)
            ->where('alert_type', 'PRICE_DROP')
            ->whereDate('triggered_at', today())
            ->count();
        
        $totalSavings = $this->calculateTotalSavings($user->id);
        
        $recentActivity = $this->getRecentActivity($user->id);

        // Récupérer les produits avec alertes actives
        $productsWithAlerts = Product::where('user_id', $user->id)
            ->whereHas('alerts', function($query) {
                $query->where('is_active', true);
            })
            ->with(['alerts' => function($query) {
                $query->where('is_active', true);
            }])
            ->limit(5)
            ->get();

        return response()->json([
            'message' => 'Dashboard data retrieved successfully',
            'stats' => [
                'total_products' => $totalProducts,
                'active_alerts' => $activeAlerts,
                'price_drops_today' => $priceDropsToday,
                'total_savings' => $totalSavings,
            ],
            'recent_activity' => $recentActivity,
            'products_with_alerts' => $productsWithAlerts,
        ]);
    }

    /**
     * Calculate total savings from price drops
     */
    private function calculateTotalSavings($userId)
    {
        $triggeredAlerts = Alert::where('user_id', $userId)
            ->where('alert_type', 'PRICE_DROP')
            ->whereNotNull('triggered_at')
            ->with('product')
            ->get();

        $totalSavings = 0;
        foreach ($triggeredAlerts as $alert) {
            if ($alert->product) {
                $savings = $alert->product->current_price - $alert->target_price;
                if ($savings > 0) {
                    $totalSavings += $savings;
                }
            }
        }

        return round($totalSavings, 2);
    }

    /**
     * Get recent activity
     */
    private function getRecentActivity($userId)
    {
        $activities = collect();

        // Alertes déclenchées récemment
        $triggeredAlerts = Alert::where('user_id', $userId)
            ->whereNotNull('triggered_at')
            ->with('product')
            ->orderBy('triggered_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($triggeredAlerts as $alert) {
            $activities->push([
                'type' => 'alert_triggered',
                'message' => "Price alert triggered for {$alert->product->title}",
                'timestamp' => $alert->triggered_at,
                'data' => [
                    'product_id' => $alert->product_id,
                    'target_price' => $alert->target_price,
                    'current_price' => $alert->product->current_price,
                ]
            ]);
        }

        // Nouveaux produits ajoutés
        $newProducts = Product::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        foreach ($newProducts as $product) {
            $activities->push([
                'type' => 'product_added',
                'message' => "New product added: {$product->title}",
                'timestamp' => $product->created_at,
                'data' => [
                    'product_id' => $product->id,
                    'price' => $product->current_price,
                ]
            ]);
        }

        return $activities->sortByDesc('timestamp')->values()->take(10);
    }
}