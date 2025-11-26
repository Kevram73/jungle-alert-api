<?php

namespace App\Jobs;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $alert;

    /**
     * Create a new job instance.
     */
    public function __construct(Alert $alert)
    {
        $this->alert = $alert;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $user = $this->alert->user;
            $product = $this->alert->product;

            if (!$user || !$product || !$user->fcm_token) {
                Log::warning("Cannot send push: Alert {$this->alert->id} missing user, product, or FCM token");
                return;
            }

            $fcmServerKey = env('FCM_SERVER_KEY');
            if (!$fcmServerKey) {
                Log::warning("FCM_SERVER_KEY not configured, skipping push notification");
                return;
            }

            $title = "🎯 Price Alert!";
            $body = $this->buildPushMessage($product);

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $user->fcm_token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                ],
                'data' => [
                    'type' => 'price_alert',
                    'alert_id' => $this->alert->id,
                    'product_id' => $product->id,
                    'current_price' => (string) $product->current_price,
                    'target_price' => (string) $this->alert->target_price,
                ],
                'priority' => 'high',
            ]);

            if ($response->successful()) {
                Log::info("Push notification sent for alert {$this->alert->id} to user {$user->id}");
            } else {
                Log::error("Failed to send push notification for alert {$this->alert->id}: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Exception sending push notification for alert {$this->alert->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Construire le message push
     */
    private function buildPushMessage($product): string
    {
        $alertType = match($this->alert->alert_type) {
            'PRICE_DROP' => 'Price dropped',
            'PRICE_INCREASE' => 'Price increased',
            'STOCK_AVAILABLE' => 'Back in stock',
            default => 'Alert triggered',
        };

        $productTitle = strlen($product->title) > 50 
            ? substr($product->title, 0, 47) . '...' 
            : $product->title;

        return "{$productTitle} - {$alertType} to {$product->current_price}";
    }
}

