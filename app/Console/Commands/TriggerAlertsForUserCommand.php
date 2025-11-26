<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Product;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class TriggerAlertsForUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:trigger-user {email : Email of the user} {--send-notifications : Send notifications when alerts are triggered}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and trigger alerts for all products of a specific user';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $email = $this->argument('email');
        $sendNotifications = $this->option('send-notifications');
        
        $this->info("🔍 Checking alerts for user: {$email}");

        // Find user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("❌ User not found with email: {$email}");
            return Command::FAILURE;
        }

        $this->info("✅ User found: {$user->username} (ID: {$user->id})");

        // Get all products for this user
        $products = Product::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        if ($products->isEmpty()) {
            $this->warn("⚠️  No active products found for this user");
            return Command::SUCCESS;
        }

        $this->info("📦 Found {$products->count()} active product(s)");

        $totalTriggered = 0;

        foreach ($products as $product) {
            $this->line("Checking product: {$product->title} (ID: {$product->id})");
            $this->line("   Current price: {$product->current_price}");

            $triggeredAlerts = $notificationService->checkAndTriggerAlerts($product, $sendNotifications);

            if (count($triggeredAlerts) > 0) {
                $totalTriggered += count($triggeredAlerts);
                $this->info("  ✓ Triggered " . count($triggeredAlerts) . " alert(s)");
                
                foreach ($triggeredAlerts as $alert) {
                    $this->line("    - Alert #{$alert->id} ({$alert->alert_type})");
                    $this->line("      Target: {$alert->target_price}, Current: {$product->current_price}");
                    if ($alert->triggered_at) {
                        $this->line("      Triggered at: {$alert->triggered_at}");
                    }
                }
            } else {
                $this->line("  - No alerts triggered");
            }
        }

        if ($totalTriggered > 0) {
            $this->info("✅ Check completed. {$totalTriggered} alert(s) triggered for {$user->username}.");
            if (!$sendNotifications) {
                $this->info("ℹ️  Notifications were not sent (alerts only updated in database)");
            }
            Log::info("Alerts triggered for user {$user->id} ({$email}): {$totalTriggered} alert(s)");
        } else {
            $this->info("✅ Check completed. No alerts triggered.");
        }

        return Command::SUCCESS;
    }
}

