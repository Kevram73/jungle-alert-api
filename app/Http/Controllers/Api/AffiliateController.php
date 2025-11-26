<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\AffiliateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AffiliateController extends Controller
{
    protected AffiliateService $affiliateService;

    public function __construct(AffiliateService $affiliateService)
    {
        $this->affiliateService = $affiliateService;
    }

    /**
     * Get affiliate link for a product (BUY IT button)
     */
    public function getBuyItLink(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();

        // Verify product belongs to user
        if ($product->user_id !== $user->id) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $data = $this->affiliateService->getBuyItButtonData($product, $user->id);

        return response()->json($data);
    }

    /**
     * Track affiliate click
     */
    public function trackClick(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();

        // Verify product belongs to user
        if ($product->user_id !== $user->id) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $this->affiliateService->trackAffiliateClick($product->id, $user->id);

        return response()->json([
            'message' => 'Click tracked successfully'
        ]);
    }
}

