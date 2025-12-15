<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Product;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class AlertController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * Mapping des types d'alertes depuis l'application mobile
     */
    private function getAlertTypeMapping(): array
    {
        return [
            'email_notification' => 'PRICE_DROP',
            'price_drop' => 'PRICE_DROP',
            'price_increase' => 'PRICE_INCREASE',
            'stock_available' => 'STOCK_AVAILABLE',
            'immediate' => 'PRICE_DROP',
            'daily' => 'PRICE_DROP',
            'weekly' => 'PRICE_DROP',
        ];
    }

    /**
     * Mapper le type d'alerte depuis l'application mobile vers le type API
     */
    private function mapAlertType(string $inputType): string
    {
        $mapping = $this->getAlertTypeMapping();
        $inputType = strtolower($inputType);
        
        if (isset($mapping[$inputType])) {
            return $mapping[$inputType];
        }
        
        // Si c'est déjà un type valide en majuscules, le retourner
        $upperType = strtoupper($inputType);
        if (in_array($upperType, ['PRICE_DROP', 'PRICE_INCREASE', 'STOCK_AVAILABLE'])) {
            return $upperType;
        }
        
        // Par défaut, considérer comme PRICE_DROP
        return 'PRICE_DROP';
    }
    /**
     * Display a listing of the user's alerts
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $alerts = Alert::forUser($user->id)
            ->with(['product' => function($query) {
                $query->select('id', 'title', 'current_price', 'image_url', 'amazon_url', 'currency', 'marketplace');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'message' => 'Alerts retrieved successfully',
            'alerts' => $alerts,
        ]);
    }

    /**
     * Store a newly created alert
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'target_price' => 'required|numeric|min:0',
            'alert_type' => 'required|string',
            'frequency' => 'sometimes|string',
            'notification_methods' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $user = $request->user();

        // Mapper le type d'alerte depuis l'application mobile
        $alertType = $this->mapAlertType($request->alert_type);

        // Vérifier que le produit appartient à l'utilisateur
        $product = Product::where('id', $request->product_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$product) {
            // Récupérer les produits disponibles pour l'utilisateur pour aider au debugging
            $availableProducts = Product::where('user_id', $user->id)
                ->select('id', 'title', 'current_price')
                ->get();
            
            return response()->json([
                'message' => 'Product not found',
                'error' => 'The specified product does not exist or does not belong to you',
                'requested_product_id' => $request->product_id,
                'available_products' => $availableProducts,
                'suggestion' => 'Please use one of the available product IDs listed above'
            ], 404);
        }

        // Vérifier qu'il n'y a pas déjà une alerte active pour ce produit avec le même type
        $existingAlert = Alert::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->where('alert_type', $alertType)
            ->where('is_active', true)
            ->first();

        if ($existingAlert) {
            return response()->json([
                'message' => 'An active alert already exists for this product with the same type',
                'alert' => $existingAlert
            ], 409);
        }

        $alert = Alert::create([
            'user_id' => $user->id,
            'product_id' => $request->product_id,
            'target_price' => $request->target_price,
            'alert_type' => $alertType,
            'is_active' => $request->get('is_active', true),
        ]);

        return response()->json([
            'message' => 'Alert created successfully',
            'alert' => $alert->load('product'),
        ], 201);
    }

    /**
     * Display the specified alert
     */
    public function show(Request $request, Alert $alert): JsonResponse
    {
        $user = $request->user();

        // Vérifier que l'alerte appartient à l'utilisateur
        if ($alert->user_id !== $user->id) {
            return response()->json(['message' => 'Alert not found'], 404);
        }

        $alert->load(['product' => function($query) {
            $query->select('id', 'title', 'current_price', 'image_url', 'amazon_url', 'currency', 'marketplace');
        }]);

        return response()->json([
            'message' => 'Alert retrieved successfully',
            'alert' => $alert,
        ]);
    }

    /**
     * Update the specified alert
     */
    public function update(Request $request, Alert $alert): JsonResponse
    {
        $user = $request->user();

        // Vérifier que l'alerte appartient à l'utilisateur
        if ($alert->user_id !== $user->id) {
            return response()->json(['message' => 'Alert not found'], 404);
        }

        $request->validate([
            'target_price' => 'sometimes|numeric|min:0',
            'alert_type' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $updateData = $request->only(['target_price', 'is_active']);
        
        if ($request->has('alert_type')) {
            $updateData['alert_type'] = $this->mapAlertType($request->alert_type);
        }

        $alert->update($updateData);

        return response()->json([
            'message' => 'Alert updated successfully',
            'alert' => $alert->fresh()->load('product'),
        ]);
    }

    /**
     * Remove the specified alert
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        // Convertir l'ID en entier
        $alertId = is_numeric($id) ? (int)$id : null;
        if (!$alertId || $alertId <= 0) {
            return response()->json([
                'message' => 'Invalid alert ID',
                'error' => 'Invalid alert ID'
            ], 400);
        }
        
        // Chercher l'alert avec vérification que l'alert appartient à l'utilisateur
        $alert = Alert::where('id', $alertId)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$alert) {
            return response()->json([
                'message' => 'Alert not found or does not belong to you',
                'error' => 'Alert not found'
            ], 404);
        }

        $alert->delete();

        return response()->json([
            'message' => 'Alert deleted successfully',
            'success' => true
        ], 200);
    }

    /**
     * Toggle alert active status
     */
    public function toggle(Request $request, Alert $alert): JsonResponse
    {
        $user = $request->user();

        // Vérifier que l'alerte appartient à l'utilisateur
        if ($alert->user_id !== $user->id) {
            return response()->json(['message' => 'Alert not found'], 404);
        }

        $alert->update(['is_active' => !$alert->is_active]);

        return response()->json([
            'message' => 'Alert status updated successfully',
            'alert' => $alert->fresh()->load('product'),
        ]);
    }

    /**
     * Get alerts by product
     */
    public function byProduct(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();

        // Vérifier que le produit appartient à l'utilisateur
        if ($product->user_id !== $user->id) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $alerts = Alert::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Product alerts retrieved successfully',
            'alerts' => $alerts,
        ]);
    }

    /**
     * Get active alerts
     */
    public function active(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $alerts = Alert::forUser($user->id)
            ->active()
            ->with(['product' => function($query) {
                $query->select('id', 'title', 'current_price', 'image_url', 'amazon_url', 'currency', 'marketplace');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Active alerts retrieved successfully',
            'alerts' => $alerts,
        ]);
    }

    /**
     * Get triggered alerts
     */
    public function triggered(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $alerts = Alert::forUser($user->id)
            ->triggered()
            ->with(['product' => function($query) {
                $query->select('id', 'title', 'current_price', 'image_url', 'amazon_url', 'currency', 'marketplace');
            }])
            ->orderBy('triggered_at', 'desc')
            ->paginate(20);

        return response()->json([
            'message' => 'Triggered alerts retrieved successfully',
            'alerts' => $alerts,
        ]);
    }

    /**
     * Check and trigger alerts for a product
     */
    public function checkAlerts(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();

        // Vérifier que le produit appartient à l'utilisateur
        if ($product->user_id !== $user->id) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Utiliser le NotificationService pour vérifier et déclencher les alerts (sans envoyer de notifications)
        $triggeredAlerts = $this->notificationService->checkAndTriggerAlerts($product, false);

        return response()->json([
            'message' => 'Alert check completed',
            'triggered_alerts' => $triggeredAlerts,
            'total_checked' => Alert::where('product_id', $product->id)
                ->where('is_active', true)
                ->count(),
        ]);
    }

    /**
     * Bulk update alert status
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'alert_ids' => 'required|array',
            'alert_ids.*' => 'exists:alerts,id',
            'is_active' => 'required|boolean',
        ]);

        $user = $request->user();
        
        $updated = Alert::where('user_id', $user->id)
            ->whereIn('id', $request->alert_ids)
            ->update(['is_active' => $request->is_active]);

        return response()->json([
            'message' => 'Alerts updated successfully',
            'updated_count' => $updated,
        ]);
    }

    /**
     * Delete multiple alerts
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'alert_ids' => 'required|array',
            'alert_ids.*' => 'exists:alerts,id',
        ]);

        $user = $request->user();
        
        $deleted = Alert::where('user_id', $user->id)
            ->whereIn('id', $request->alert_ids)
            ->delete();

        return response()->json([
            'message' => 'Alerts deleted successfully',
            'deleted_count' => $deleted,
        ]);
    }
}