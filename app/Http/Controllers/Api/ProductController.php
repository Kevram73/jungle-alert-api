<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PriceHistory;
use App\Models\Alert;
use App\Services\AmazonScrapingService;
use App\Services\NotificationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    protected $scrapingService;
    protected $notificationService;

    public function __construct(AmazonScrapingService $scrapingService, NotificationService $notificationService)
    {
        $this->scrapingService = $scrapingService;
        $this->notificationService = $notificationService;
    }

    private function extractMarketplaceFromUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        $host = strtolower($host);
        
        // Check specific domains first (more specific before less specific)
        // Supports all major Amazon marketplaces: US, DE, UK, FR, IT, ES, BR, IN, CA, EU
        if (str_contains($host, 'amazon.com.br')) return 'BR';
        if (str_contains($host, 'amazon.co.uk')) return 'UK';
        if (str_contains($host, 'amazon.de')) return 'DE';
        if (str_contains($host, 'amazon.fr')) return 'FR';
        if (str_contains($host, 'amazon.it')) return 'IT';
        if (str_contains($host, 'amazon.es')) return 'ES';
        if (str_contains($host, 'amazon.in')) return 'IN';
        if (str_contains($host, 'amazon.ca')) return 'CA';
        if (str_contains($host, 'amazon.eu')) return 'EU';
        // Check for amazon.com last (as it's the most generic)
        if (str_contains($host, 'amazon.com')) return 'US';
        
        return 'US';
    }

    private function currencyForMarketplace(string $marketplace): string
    {
        return match ($marketplace) {
            'FR', 'DE', 'ES', 'IT', 'EU' => 'EUR',
            'UK' => 'GBP',
            'CA' => 'CAD',
            'BR' => 'BRL',
            'IN' => 'INR',
            default => 'USD',
        };
    }

    /**
     * Ensure product has currency and marketplace set
     * Ne modifie pas la devise si elle est déjà définie (préserve l'EUR si déjà défini)
     */
    private function ensureProductCurrency(Product $product): void
    {
        $needsSave = false;
        
        // Si le produit n'a pas de marketplace, l'extraire de l'URL
        if (empty($product->marketplace)) {
            $product->marketplace = $this->extractMarketplaceFromUrl($product->amazon_url);
            $needsSave = true;
        }
        
        // Si le produit n'a pas de currency, la déterminer depuis le marketplace
        // IMPORTANT: Ne pas remplacer une devise existante (préserve l'EUR si déjà défini)
        if (empty($product->currency)) {
            $product->currency = $this->currencyForMarketplace($product->marketplace);
            $needsSave = true;
        }
        
        if ($needsSave) {
            $product->save();
        }
    }
    /**
     * Display a listing of the user's products
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $products = Product::forUser($user->id)
            ->with(['alerts' => function($query) {
                $query->active();
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // S'assurer que tous les produits ont currency et marketplace
        foreach ($products->items() as $product) {
            $this->ensureProductCurrency($product);
        }

        return response()->json([
            'message' => 'Products retrieved successfully',
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'amazon_url' => 'required|url|max:500',
            'target_price' => 'nullable|numeric|min:0',
            'scrape_data' => 'boolean', // Option pour forcer le scraping
        ]);

        $user = $request->user();

        // Valider que c'est une URL Amazon
        if (!$this->scrapingService->validateAmazonUrl($request->amazon_url)) {
            return response()->json([
                'message' => 'Invalid Amazon URL',
                'errors' => ['amazon_url' => ['Please provide a valid Amazon product URL']]
            ], 422);
        }

        // Vérifier si le produit existe déjà pour cet utilisateur
        $existingProduct = Product::where('user_id', $user->id)
            ->where('amazon_url', $request->amazon_url)
            ->first();

        if ($existingProduct) {
            return response()->json([
                'message' => 'Product already exists',
                'product' => $existingProduct->load('alerts'),
            ], 409);
        }

        // Extraire le marketplace et la devise depuis l'URL
        $marketplace = $this->extractMarketplaceFromUrl($request->amazon_url);
        $currency = $this->currencyForMarketplace($marketplace);

        $productData = [
            'user_id' => $user->id,
            'amazon_url' => $request->amazon_url,
            'target_price' => $request->target_price,
            'is_active' => true,
            'marketplace' => $marketplace,
            'currency' => $currency,
        ];

        // Scraper les données du produit si demandé ou si pas de données manuelles
        $scrapeData = $request->get('scrape_data', true);
        if ($scrapeData) {
            $scrapedData = $this->scrapingService->scrapeProduct($request->amazon_url);
            
            if ($scrapedData['success']) {
                $data = $scrapedData['data'];
                $productData = array_merge($productData, [
                    'title' => $data['title'] ?? 'Product from Amazon',
                    'image_url' => $data['image_url'] ?? null,
                    'current_price' => $data['price'] ?? 0,
                    'asin' => $data['asin'] ?? null,
                ]);
            } else {
                // Si le scraping échoue, utiliser des données de base
                $productData = array_merge($productData, [
                    'title' => 'Product from Amazon',
                    'image_url' => null,
                    'current_price' => 0,
                    'asin' => null,
                ]);
            }
        } else {
            // Données manuelles (fallback)
            $request->validate([
                'title' => 'required|string|max:255',
                'image_url' => 'nullable|url|max:500',
                'current_price' => 'required|numeric|min:0',
                'asin' => 'nullable|string|max:20',
            ]);

            $productData = array_merge($productData, [
                'title' => $request->title,
                'image_url' => $request->image_url,
                'current_price' => $request->current_price,
                'asin' => $request->asin,
            ]);
            
            // Si le marketplace/currency n'est pas défini, l'extraire de l'URL
            if (!isset($productData['marketplace'])) {
                $productData['marketplace'] = $this->extractMarketplaceFromUrl($request->amazon_url);
            }
            if (!isset($productData['currency'])) {
                $productData['currency'] = $this->currencyForMarketplace($productData['marketplace']);
            }
        }

        $product = Product::create($productData);

        // Enregistrer le prix initial dans l'historique
        if ($product->current_price > 0) {
            PriceHistory::create([
                'product_id' => $product->id,
                'price' => $product->current_price,
                'recorded_at' => now(),
            ]);
        }

        // Créer automatiquement une alerte PRICE_DROP si un target_price est fourni
        if ($request->target_price && $request->target_price > 0) {
            Alert::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'target_price' => $request->target_price,
                'alert_type' => 'PRICE_DROP',
                'is_active' => true,
            ]);
        }

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product->load('alerts'),
            'scraped' => $scrapeData,
        ], 201);
    }

    /**
     * Display the specified product
     */
    public function show(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();

        // Vérifier que le produit appartient à l'utilisateur
        if ($product->user_id !== $user->id) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->load(['alerts', 'priceHistories' => function($query) {
            $query->orderBy('recorded_at', 'desc')->limit(30);
        }]);

        // S'assurer que le produit a currency et marketplace
        $this->ensureProductCurrency($product);
        $product->refresh();

        return response()->json([
            'message' => 'Product retrieved successfully',
            'product' => $product,
        ]);
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();

        // Vérifier que le produit appartient à l'utilisateur
        if ($product->user_id !== $user->id) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'image_url' => 'nullable|url|max:500',
            'current_price' => 'sometimes|numeric|min:0',
            'target_price' => 'nullable|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'currency' => 'sometimes|string|size:3',
            'marketplace' => 'sometimes|string|max:10',
        ]);

        $oldPrice = $product->current_price;
        
        $updateData = $request->only([
            'title',
            'image_url',
            'current_price',
            'target_price',
            'is_active',
            'currency',
            'marketplace',
        ]);
        
        // Si marketplace est mis à jour, mettre à jour aussi la devise UNIQUEMENT si elle n'est pas fournie
        // et si le produit n'a pas déjà une devise définie (préserve l'EUR si déjà défini)
        if (isset($updateData['marketplace']) && !isset($updateData['currency']) && empty($product->currency)) {
            $updateData['currency'] = $this->currencyForMarketplace($updateData['marketplace']);
        }
        
        // Si l'URL Amazon est mise à jour, mettre à jour marketplace
        // IMPORTANT: Ne pas remplacer la devise si elle existe déjà (préserve l'EUR si déjà défini)
        if ($request->has('amazon_url')) {
            $marketplace = $this->extractMarketplaceFromUrl($request->amazon_url);
            $updateData['marketplace'] = $marketplace;
            
            // Seulement définir la devise si elle n'est pas déjà définie
            if (empty($product->currency) && !isset($updateData['currency'])) {
                $updateData['currency'] = $this->currencyForMarketplace($marketplace);
            }
        }
        
        $product->update($updateData);

        // Si le prix a changé, l'enregistrer dans l'historique
        if ($request->has('current_price') && $request->current_price != $oldPrice) {
            PriceHistory::create([
                'product_id' => $product->id,
                'price' => $request->current_price,
                'recorded_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product->fresh()->load('alerts'),
        ]);
    }

    /**
     * Remove the specified product
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();

        // Vérifier que le produit appartient à l'utilisateur
        if ($product->user_id !== $user->id) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * Get price history for a product
     */
    public function priceHistory(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();

        // Vérifier que le produit appartient à l'utilisateur
        if ($product->user_id !== $user->id) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $days = $request->get('days', 30);
        
        $priceHistory = PriceHistory::forProduct($product->id)
            ->recent($days)
            ->orderBy('recorded_at', 'asc')
            ->get();

        return response()->json([
            'message' => 'Price history retrieved successfully',
            'price_history' => $priceHistory,
        ]);
    }

    /**
     * Update product price
     */
    public function updatePrice(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();

        // Vérifier que le produit appartient à l'utilisateur
        if ($product->user_id !== $user->id) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $request->validate([
            'current_price' => 'required|numeric|min:0',
        ]);

        $oldPrice = $product->current_price;
        $product->update(['current_price' => $request->current_price]);

        // Enregistrer le nouveau prix dans l'historique
        PriceHistory::create([
            'product_id' => $product->id,
            'price' => $request->current_price,
            'recorded_at' => now(),
        ]);

        // Vérifier et déclencher les alerts si le prix a changé (sans envoyer de notifications)
        if ($request->current_price != $oldPrice) {
            $product->refresh();
            $this->notificationService->checkAndTriggerAlerts($product, false);
        }

        return response()->json([
            'message' => 'Price updated successfully',
            'product' => $product->fresh(),
            'price_change' => $request->current_price - $oldPrice,
        ]);
    }

    /**
     * Refresh product data (alias for scrapeAndUpdate)
     */
    public function refresh(Request $request, Product $product): JsonResponse
    {
        return $this->scrapeAndUpdate($request, $product);
    }

    /**
     * Scrape and update product data
     */
    public function scrapeAndUpdate(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();

        // Vérifier que le produit appartient à l'utilisateur
        if ($product->user_id !== $user->id) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $scrapedData = $this->scrapingService->scrapeProduct($product->amazon_url);
        
        if (!$scrapedData['success']) {
            return response()->json([
                'message' => 'Failed to scrape product data',
                'error' => $scrapedData['error']
            ], 400);
        }

        $data = $scrapedData['data'];
        $oldPrice = $product->current_price;
        
        // Extraire le marketplace depuis l'URL si nécessaire
        $marketplace = $this->extractMarketplaceFromUrl($product->amazon_url);
        
        // Préparer les données de mise à jour
        $updateData = [
            'title' => $data['title'] ?? $product->title,
            'image_url' => $data['image_url'] ?? $product->image_url,
            'current_price' => $data['price'] ?? $product->current_price,
            'asin' => $data['asin'] ?? $product->asin,
            'marketplace' => $marketplace,
        ];
        
        // IMPORTANT: Ne pas remplacer la devise si elle existe déjà (préserve l'EUR si déjà défini)
        // Seulement définir la devise si elle n'est pas déjà définie
        if (empty($product->currency)) {
            $updateData['currency'] = $this->currencyForMarketplace($marketplace);
        }
        // Sinon, garder la devise existante (EUR, USD, etc.)

        // Mettre à jour les données du produit
        $product->update($updateData);

        // Enregistrer le nouveau prix dans l'historique si il a changé
        if ($data['price'] && $data['price'] != $oldPrice) {
            PriceHistory::create([
                'product_id' => $product->id,
                'price' => $data['price'],
                'recorded_at' => now(),
            ]);

            // Vérifier et déclencher les alerts si le prix a changé (sans envoyer de notifications)
            $product->refresh();
            $triggeredAlerts = $this->notificationService->checkAndTriggerAlerts($product, false);
        }

        return response()->json([
            'message' => 'Product data updated successfully',
            'product' => $product->fresh()->load('alerts'),
            'price_change' => $data['price'] ? $data['price'] - $oldPrice : 0,
        ]);
    }

    /**
     * Scrape product data without saving (preview)
     */
    public function scrapePreview(Request $request): JsonResponse
    {
        $request->validate([
            'amazon_url' => 'required|url|max:500',
        ]);

        // Valider que c'est une URL Amazon
        if (!$this->scrapingService->validateAmazonUrl($request->amazon_url)) {
            return response()->json([
                'message' => 'Invalid Amazon URL',
                'errors' => ['amazon_url' => ['Please provide a valid Amazon product URL']]
            ], 422);
        }

        $scrapedData = $this->scrapingService->scrapeProduct($request->amazon_url);
        
        if (!$scrapedData['success']) {
            return response()->json([
                'message' => 'Failed to scrape product data',
                'error' => $scrapedData['error']
            ], 400);
        }

        // Enrichir les données pour correspondre aux attentes du client mobile
        $raw = $scrapedData['data'];

        $marketplace = $this->extractMarketplaceFromUrl($request->amazon_url);
        $currency = $this->currencyForMarketplace($marketplace);
        $price = $raw['price'] ?? null;

        $enriched = [
            // Champs existants
            'asin' => $raw['asin'] ?? null,
            'title' => $raw['title'] ?? 'Product from URL',
            'image_url' => $raw['image_url'] ?? null,
            'availability' => $raw['availability'] ?? 'In Stock',
            'price' => $price,

            // Champs additionnels attendus par l'app Flutter
            'name' => $raw['title'] ?? 'Product from URL',
            'current_price' => $price,
            'suggested_price' => ($price && $price > 0) ? round($price * 0.8, 2) : 0.0,
            'marketplace' => $marketplace,
            'currency' => $currency,
            'category' => $raw['category'] ?? 'General',
            'rating' => $raw['rating'] ?? 4.5,
            'review_count' => $raw['review_count'] ?? 100,
        ];

        return response()->json([
            'message' => 'Product data scraped successfully',
            'data' => $enriched,
        ]);
    }

    /**
     * Bulk update prices for all user products
     */
    public function bulkUpdatePrices(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $products = Product::forUser($user->id)->active()->get();
        $updated = 0;
        $errors = [];

        foreach ($products as $product) {
            try {
                $scrapedData = $this->scrapingService->scrapeProduct($product->amazon_url);
                
                if ($scrapedData['success'] && isset($scrapedData['data']['price'])) {
                    $newPrice = $scrapedData['data']['price'];
                    $oldPrice = $product->current_price;
                    
                    if ($newPrice != $oldPrice) {
                        $product->update(['current_price' => $newPrice]);
                        
                        PriceHistory::create([
                            'product_id' => $product->id,
                            'price' => $newPrice,
                            'recorded_at' => now(),
                        ]);
                        
                        // Vérifier et déclencher les alerts (sans envoyer de notifications)
                        $product->refresh();
                        $this->notificationService->checkAndTriggerAlerts($product, false);
                        
                        $updated++;
                    }
                }
            } catch (Exception $e) {
                $errors[] = "Product {$product->id}: " . $e->getMessage();
            }
        }

        return response()->json([
            'message' => 'Bulk price update completed',
            'updated_products' => $updated,
            'total_products' => $products->count(),
            'errors' => $errors,
        ]);
    }
}