<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class CheckAlertsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:check {--product-id= : Check alerts for a specific product only} {--send-notifications : Send notifications when alerts are triggered}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all active alerts and trigger notifications when conditions are met';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $this->info('🔍 Checking alerts...');

        $productId = $this->option('product-id');

        if ($productId) {
            $product = Product::find($productId);
            if (!$product) {
                $this->error("Product {$productId} not found");
                return Command::FAILURE;
            }
            $products = collect([$product]);
        } else {
            // Récupérer tous les produits avec des alerts actives
            $products = Product::whereHas('alerts', function ($query) {
                $query->where('is_active', true)
                    ->whereNull('triggered_at');
            })->get();
        }

        $totalTriggered = 0;

        foreach ($products as $product) {
            $this->line("Checking product: {$product->title} (ID: {$product->id})");

            // Mettre à jour le prix si nécessaire (scraping optionnel)
            // Pour l'instant, on utilise le prix actuel en base

            $sendNotifications = $this->option('send-notifications');
            $triggeredAlerts = $notificationService->checkAndTriggerAlerts($product, $sendNotifications);
            
            if (!$sendNotifications) {
                $this->line("   (Notifications disabled - only updating alert status)");
            }

            if (count($triggeredAlerts) > 0) {
                $totalTriggered += count($triggeredAlerts);
                $this->info("  ✓ Triggered " . count($triggeredAlerts) . " alert(s)");
                
                foreach ($triggeredAlerts as $alert) {
                    $this->line("    - Alert #{$alert->id} ({$alert->alert_type}) - Target: {$alert->target_price}, Current: {$product->current_price}");
                }
            } else {
                $this->line("  - No alerts triggered");
            }
        }

        if ($totalTriggered > 0) {
            $this->info("✅ Check completed. {$totalTriggered} alert(s) triggered.");
            Log::info("Alert check completed: {$totalTriggered} alert(s) triggered");
        } else {
            $this->info("✅ Check completed. No alerts triggered.");
        }

        return Command::SUCCESS;
    }
}

