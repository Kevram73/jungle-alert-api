<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\AffiliateController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\GdprController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'healthy', 'message' => 'API is running']);
});

// Public routes
Route::prefix('v1')->group(function () {
    // Auth routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    
    // Public product routes (no auth required)
    Route::post('/products/scrape-preview', [ProductController::class, 'scrapePreview']);
    
           // Protected routes
           Route::middleware('auth:sanctum')->group(function () {
               Route::post('/auth/logout', [AuthController::class, 'logout']);
               Route::get('/auth/me', [AuthController::class, 'me']);
               
               // User routes
               Route::get('/users/me', [UserController::class, 'me']);
               Route::put('/users/me', [UserController::class, 'update']);
              Route::post('/users/me/fcm-token', [UserController::class, 'updateFcmToken']);
               Route::post('/users/change-password', [UserController::class, 'changePassword']);
               Route::delete('/users/me', [UserController::class, 'destroy']);
               
               // Subscription routes
               Route::get('/subscriptions', [SubscriptionController::class, 'index']);
               Route::post('/subscriptions', [SubscriptionController::class, 'store']);
               Route::get('/subscriptions/plans', [SubscriptionController::class, 'plans']);
               Route::get('/subscriptions/limits', [SubscriptionController::class, 'limits']);
               
               // Legacy subscription routes (keep for backward compatibility)
               Route::get('/subscription/plans', [UserController::class, 'getSubscriptionPlans']);
               Route::get('/subscription/limits', [UserController::class, 'getSubscriptionLimits']);
               Route::put('/subscription/upgrade', [UserController::class, 'upgradeSubscription']);
               
               // Dashboard routes
               Route::get('/dashboard', [DashboardController::class, 'index']);
               
               // Product routes
               Route::post('/products', [ProductController::class, 'store'])->middleware('subscription.limits:product');
               Route::get('/products', [ProductController::class, 'index']);
               Route::get('/products/{product}', [ProductController::class, 'show']);
               Route::put('/products/{product}', [ProductController::class, 'update']);
               Route::delete('/products/{product}', [ProductController::class, 'destroy']);
               Route::post('/products/{product}/scrape-update', [ProductController::class, 'scrapeAndUpdate']);
               Route::post('/products/{product}/refresh', [ProductController::class, 'refresh']);
               Route::post('/products/bulk-update-prices', [ProductController::class, 'bulkUpdatePrices']);
               Route::get('/products/{product}/price-history', [ProductController::class, 'priceHistory']);
               Route::get('/products/{product}/price-trend', [ProductController::class, 'priceTrend']);
               Route::post('/products/{product}/update-price', [ProductController::class, 'updatePrice']);
               
               // Alert routes
               Route::post('/alerts', [AlertController::class, 'store'])->middleware('subscription.limits:alert');
               Route::get('/alerts', [AlertController::class, 'index']);
               Route::get('/alerts/active', [AlertController::class, 'active']);
               Route::get('/alerts/triggered', [AlertController::class, 'triggered']);
               Route::get('/alerts/{alert}', [AlertController::class, 'show']);
               Route::put('/alerts/{alert}', [AlertController::class, 'update']);
               Route::delete('/alerts/{alert}', [AlertController::class, 'destroy']);
               Route::get('/products/{product}/alerts', [AlertController::class, 'byProduct']);
               Route::post('/alerts/{alert}/toggle', [AlertController::class, 'toggle']);
               Route::post('/products/{product}/check-alerts', [AlertController::class, 'checkAlerts']);
               Route::post('/alerts/bulk-update', [AlertController::class, 'bulkUpdate']);
               Route::post('/alerts/bulk-delete', [AlertController::class, 'bulkDelete']);
               
               // Affiliate routes
               Route::get('/affiliate/products/{product}/buy-link', [AffiliateController::class, 'getBuyItLink']);
               Route::post('/affiliate/products/{product}/track-click', [AffiliateController::class, 'trackClick']);
               
               // Newsletter routes
               Route::get('/newsletter/preview', [NewsletterController::class, 'preview']);
               Route::put('/newsletter/consent', [NewsletterController::class, 'updateConsent']);
               Route::get('/newsletter/consent', [NewsletterController::class, 'getConsent']);
               
               // GDPR routes
               Route::get('/gdpr/export-data', [GdprController::class, 'exportData']);
               Route::delete('/gdpr/delete-account', [GdprController::class, 'deleteAccount']);
           });
});
