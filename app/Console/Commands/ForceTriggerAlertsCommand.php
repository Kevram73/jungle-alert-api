<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Alert;
use App\Models\Product;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class ForceTriggerAlertsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:force-trigger {email : Email of the user} {--send-notifications : Send notifications when alerts are triggered}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Force trigger alerts for a user by temporarily adjusting prices';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $email = $this->argument('email');
        $sendNotifications = $this->option('send-notifications');
        
        $this->info("🔔 Force triggering alerts for user: {$email}");

        // Find user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("❌ User not found with email: {$email}");
            return Command::FAILURE;
        }

        $this->info("✅ User found: {$user->username} (ID: {$user->id})");

        // Get all active alerts for this user that haven't been triggered
        $alerts = Alert::where('user_id', $user->id)
            ->where('is_active', true)
            ->whereNull('triggered_at')
            ->with('product')
            ->get();

        if ($alerts->isEmpty()) {
            $this->warn("⚠️  No active untriggered alerts found for this user");
            return Command::SUCCESS;
        }

        $this->info("📋 Found {$alerts->count()} active alert(s) to trigger");

        $triggeredCount = 0;
        $priceChanges = [];

        foreach ($alerts as $alert) {
            if (!$alert->product) {
                $this->warn("  ⚠️  Alert #{$alert->id} has no associated product, skipping");
                continue;
            }

            $product = $alert->product;
            $oldPrice = $product->current_price;
            $targetPrice = $alert->target_price;

            $this->line("Processing Alert #{$alert->id}:");
            $this->line("  Product: {$product->title}");
            $this->line("  Current Price: {$oldPrice}");
            $this->line("  Target Price: {$targetPrice}");
            $this->line("  Alert Type: {$alert->alert_type}");

            // Determine new price based on alert type
            $newPrice = match($alert->alert_type) {
                'PRICE_DROP' => $targetPrice - 0.01, // Slightly below target to trigger
                'PRICE_INCREASE' => $targetPrice + 0.01, // Slightly above target to trigger
                'STOCK_AVAILABLE' => $oldPrice, // No price change needed
                default => $targetPrice - 0.01,
            };

            // Only change price if needed
            if ($alert->alert_type !== 'STOCK_AVAILABLE' && $newPrice != $oldPrice) {
                // Store old price for restoration
                $priceChanges[$product->id] = $oldPrice;
                
                // Update product price
                $product->update(['current_price' => $newPrice]);
                
                // Record in price history
                \App\Models\PriceHistory::create([
                    'product_id' => $product->id,
                    'price' => $newPrice,
                    'recorded_at' => now(),
                ]);

                $this->info("  ✓ Price updated: {$oldPrice} → {$newPrice}");
            }

            // Refresh product to get updated price
            $product->refresh();

            // Check and trigger the alert
            $triggeredAlerts = $notificationService->checkAndTriggerAlerts($product, $sendNotifications);

            if (count($triggeredAlerts) > 0) {
                $triggeredCount++;
                $this->info("  ✅ Alert #{$alert->id} triggered!");
            } else {
                $this->warn("  ⚠️  Alert #{$alert->id} was not triggered (check alert conditions)");
            }

            $this->line("");
        }

        if ($triggeredCount > 0) {
            $this->info("✅ Successfully triggered {$triggeredCount} alert(s) for {$user->username}");
            if (!$sendNotifications) {
                $this->info("ℹ️  Notifications were not sent (alerts only updated in database)");
            }
            Log::info("Force triggered alerts for user {$user->id} ({$email}): {$triggeredCount} alert(s)");
        } else {
            $this->warn("⚠️  No alerts were triggered");
        }

        // Note: Prices are kept at the new values to reflect the triggered state
        // If you want to restore original prices, uncomment the following:
        /*
        $this->info("Restoring original prices...");
        foreach ($priceChanges as $productId => $originalPrice) {
            Product::find($productId)->update(['current_price' => $originalPrice]);
        }
        */

        return Command::SUCCESS;
    }
}

