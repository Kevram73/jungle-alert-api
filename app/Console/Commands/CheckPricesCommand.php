<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\PriceHistory;
use App\Services\AmazonScrapingService;
use App\Services\NotificationService;
use App\Services\PriceChangeDetectionService;
use Illuminate\Support\Facades\Log;
use Exception;

class CheckPricesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prices:check 
                            {--limit=50 : Number of products to check per run}
                            {--user= : Check prices for specific user ID}
                            {--product= : Check price for specific product ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and update prices for tracked products using Amazon scraping';

    protected AmazonScrapingService $scrapingService;
    protected NotificationService $notificationService;
    protected PriceChangeDetectionService $priceDetectionService;

    public function __construct(
        AmazonScrapingService $scrapingService,
        NotificationService $notificationService,
        PriceChangeDetectionService $priceDetectionService
    ) {
        parent::__construct();
        $this->scrapingService = $scrapingService;
        $this->notificationService = $notificationService;
        $this->priceDetectionService = $priceDetectionService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting price check...');
        
        $limit = (int) $this->option('limit');
        $userId = $this->option('user');
        $productId = $this->option('product');

        // Build query
        $query = Product::where('is_active', true);
        
        if ($productId) {
            $query->where('id', $productId);
        } elseif ($userId) {
            $query->where('user_id', $userId);
        } else {
            // Check products that haven't been checked recently (last hour)
            $query->where(function($q) {
                $q->whereNull('last_price_check')
                  ->orWhere('last_price_check', '<', now()->subHour());
            });
        }

        $products = $query->limit($limit)->get();
        
        if ($products->isEmpty()) {
            $this->info('No products to check.');
            return Command::SUCCESS;
        }

        $this->info("Checking prices for {$products->count()} products...");
        
        $updated = 0;
        $errors = 0;
        $priceChanges = 0;
        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            try {
                $result = $this->checkProductPrice($product);
                
                if ($result['success']) {
                    $updated++;
                    if ($result['price_changed']) {
                        $priceChanges++;
                    }
                } else {
                    $errors++;
                    Log::warning("Failed to check price for product {$product->id}: {$result['error']}");
                }
            } catch (Exception $e) {
                $errors++;
                Log::error("Error checking product {$product->id}: " . $e->getMessage());
            }
            
            $bar->advance();
            
            // Small delay to avoid rate limiting
            usleep(500000); // 0.5 second delay
        }

        $bar->finish();
        $this->newLine();
        
        $this->info("Price check completed:");
        $this->info("  - Updated: {$updated}");
        $this->info("  - Price changes: {$priceChanges}");
        $this->info("  - Errors: {$errors}");

        return Command::SUCCESS;
    }

    /**
     * Check and update price for a single product
     */
    protected function checkProductPrice(Product $product): array
    {
        try {
            // Use scraping service with retry and cache
            $scrapedData = $this->scrapingService->scrapeProductWithRetry($product->amazon_url);
            
            if (!$scrapedData['success'] || !isset($scrapedData['data']['price'])) {
                return [
                    'success' => false,
                    'error' => $scrapedData['error'] ?? 'No price data found',
                ];
            }

            $newPrice = $scrapedData['data']['price'];
            $oldPrice = $product->current_price;

            // Use price change detection service
            $priceChange = $this->priceDetectionService->detectPriceChange(
                $oldPrice,
                $newPrice,
                $product->currency
            );

            $priceChanged = $priceChange['changed'];

            // Update product
            $updateData = [
                'current_price' => $newPrice,
                'last_price_check' => now(),
            ];

            // Update title and image if available
            if (isset($scrapedData['data']['title'])) {
                $updateData['title'] = $scrapedData['data']['title'];
            }
            if (isset($scrapedData['data']['image_url'])) {
                $updateData['image_url'] = $scrapedData['data']['image_url'];
            }
            if (isset($scrapedData['data']['asin'])) {
                $updateData['asin'] = $scrapedData['data']['asin'];
            }

            $product->update($updateData);

            // Save to price history if price changed
            if ($priceChanged && $newPrice) {
                PriceHistory::create([
                    'product_id' => $product->id,
                    'price' => $newPrice,
                    'recorded_at' => now(),
                ]);

                // Check and trigger alerts if price changed
                $product->refresh();
                $this->notificationService->checkAndTriggerAlerts($product, true);
            }

            return [
                'success' => true,
                'price_changed' => $priceChanged,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'price_change' => $priceChange,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

