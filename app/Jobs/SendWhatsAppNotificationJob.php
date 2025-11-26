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

class SendWhatsAppNotificationJob implements ShouldQueue
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

            if (!$user || !$product || !$user->whatsapp_number) {
                Log::warning("Cannot send WhatsApp: Alert {$this->alert->id} missing user, product, or WhatsApp number");
                return;
            }

            // Utiliser Twilio ou un autre service WhatsApp Business API
            // Pour l'instant, on log juste l'intention
            // Vous pouvez intégrer avec Twilio, WhatsApp Business API, etc.

            $whatsappApiUrl = env('WHATSAPP_API_URL');
            $whatsappApiKey = env('WHATSAPP_API_KEY');

            if (!$whatsappApiUrl || !$whatsappApiKey) {
                Log::warning("WhatsApp API not configured, skipping WhatsApp notification");
                // Pour le développement, on peut utiliser un service de test
                // ou simplement logger l'intention
                Log::info("Would send WhatsApp to {$user->whatsapp_number} for alert {$this->alert->id}");
                return;
            }

            $message = $this->buildWhatsAppMessage($product);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $whatsappApiKey,
                'Content-Type' => 'application/json',
            ])->post($whatsappApiUrl, [
                'to' => $user->whatsapp_number,
                'message' => $message,
            ]);

            if ($response->successful()) {
                Log::info("WhatsApp notification sent for alert {$this->alert->id} to {$user->whatsapp_number}");
            } else {
                Log::error("Failed to send WhatsApp notification for alert {$this->alert->id}: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Exception sending WhatsApp notification for alert {$this->alert->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Construire le message WhatsApp
     */
    private function buildWhatsAppMessage($product): string
    {
        $alertType = match($this->alert->alert_type) {
            'PRICE_DROP' => '💰 Prix en baisse',
            'PRICE_INCREASE' => '📈 Prix en hausse',
            'STOCK_AVAILABLE' => '✅ Disponible',
            default => '🔔 Alerte',
        };

        $productTitle = strlen($product->title) > 60 
            ? substr($product->title, 0, 57) . '...' 
            : $product->title;

        $message = "🎯 *Alerte Prix Jungle Alert*\n\n";
        $message .= "{$alertType}\n\n";
        $message .= "*Produit:* {$productTitle}\n";
        $message .= "*Prix actuel:* {$product->current_price}\n";
        $message .= "*Prix cible:* {$this->alert->target_price}\n\n";
        $message .= "Voir le produit:\n{$product->amazon_url}";

        return $message;
    }
}

