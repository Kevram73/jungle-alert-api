<?php

namespace App\Jobs;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendEmailNotificationJob implements ShouldQueue
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

            if (!$user || !$product) {
                Log::warning("Cannot send email: Alert {$this->alert->id} missing user or product");
                return;
            }

            $alertType = $this->getAlertTypeLabel($this->alert->alert_type);
            $priceChange = $this->calculatePriceChange($product);

            $subject = "🎯 Price Alert: {$product->title}";
            $message = $this->buildEmailMessage($product, $alertType, $priceChange);

            // Envoyer l'email
            Mail::raw($message, function ($mail) use ($user, $subject) {
                $mail->to($user->email)
                    ->subject($subject);
            });

            Log::info("Email notification sent for alert {$this->alert->id} to {$user->email}");
        } catch (\Exception $e) {
            Log::error("Failed to send email notification for alert {$this->alert->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtenir le libellé du type d'alerte
     */
    private function getAlertTypeLabel(string $type): string
    {
        return match($type) {
            'PRICE_DROP' => 'Price Drop Alert',
            'PRICE_INCREASE' => 'Price Increase Alert',
            'STOCK_AVAILABLE' => 'Stock Available Alert',
            default => 'Price Alert',
        };
    }

    /**
     * Calculer le changement de prix
     */
    private function calculatePriceChange($product): ?float
    {
        $latestHistory = $product->priceHistories()
            ->orderBy('recorded_at', 'desc')
            ->skip(1)
            ->first();

        if ($latestHistory) {
            return $product->current_price - $latestHistory->price;
        }

        return null;
    }

    /**
     * Construire le message email
     */
    private function buildEmailMessage($product, string $alertType, ?float $priceChange): string
    {
        $message = "Hello!\n\n";
        $message .= "Your price alert has been triggered!\n\n";
        $message .= "Product: {$product->title}\n";
        $message .= "Current Price: {$product->current_price}\n";
        $message .= "Target Price: {$this->alert->target_price}\n";
        $message .= "Alert Type: {$alertType}\n\n";

        if ($priceChange !== null) {
            $direction = $priceChange > 0 ? 'increased' : 'decreased';
            $message .= "Price has {$direction} by " . abs($priceChange) . "\n\n";
        }

        $message .= "View product: {$product->amazon_url}\n\n";
        $message .= "Thank you for using Jungle Alert!";

        return $message;
    }
}

