<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\User;
use App\Jobs\SendEmailNotificationJob;
use App\Jobs\SendPushNotificationJob;
use App\Jobs\SendWhatsAppNotificationJob;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Envoyer les notifications pour une alerte déclenchée
     */
    public function sendAlertNotifications(Alert $alert): void
    {
        $user = $alert->user;
        $product = $alert->product;

        if (!$user || !$product) {
            Log::warning("Cannot send notifications: Alert {$alert->id} missing user or product");
            return;
        }

        $updateData = [];
        $notificationsSent = false;

        // Email notification
        if ($user->email_notifications && !$alert->email_sent) {
            try {
                SendEmailNotificationJob::dispatch($alert);
                $updateData['email_sent'] = true;
                $notificationsSent = true;
            } catch (\Exception $e) {
                Log::error("Failed to queue email notification for alert {$alert->id}: " . $e->getMessage());
            }
        }

        // Push notification
        if ($user->push_notifications && !$alert->push_sent && $user->fcm_token) {
            try {
                SendPushNotificationJob::dispatch($alert);
                $updateData['push_sent'] = true;
                $notificationsSent = true;
            } catch (\Exception $e) {
                Log::error("Failed to queue push notification for alert {$alert->id}: " . $e->getMessage());
            }
        }

        // WhatsApp notification
        if ($user->whatsapp_notifications && !$alert->whatsapp_sent && $user->whatsapp_number) {
            try {
                SendWhatsAppNotificationJob::dispatch($alert);
                $updateData['whatsapp_sent'] = true;
                $notificationsSent = true;
            } catch (\Exception $e) {
                Log::error("Failed to queue WhatsApp notification for alert {$alert->id}: " . $e->getMessage());
            }
        }

        // Mettre à jour l'alerte si des notifications ont été envoyées
        if ($notificationsSent) {
            $alert->update($updateData);
        }
    }

    /**
     * Vérifier et déclencher les alerts pour un produit
     * 
     * @param mixed $product Le produit à vérifier
     * @param bool $sendNotifications Si true, envoie les notifications. Si false, met juste à jour les alerts.
     * @return array Liste des alerts déclenchés
     */
    public function checkAndTriggerAlerts($product, bool $sendNotifications = false): array
    {
        $triggeredAlerts = [];
        
        $alerts = \App\Models\Alert::where('product_id', $product->id)
            ->where('is_active', true)
            ->whereNull('triggered_at')
            ->with(['user', 'product'])
            ->get();

        foreach ($alerts as $alert) {
            $shouldTrigger = false;

            switch ($alert->alert_type) {
                case 'PRICE_DROP':
                    $shouldTrigger = $product->current_price <= $alert->target_price;
                    break;
                case 'PRICE_INCREASE':
                    $shouldTrigger = $product->current_price >= $alert->target_price;
                    break;
                case 'STOCK_AVAILABLE':
                    // Pour l'instant, on considère que le produit est toujours en stock
                    $shouldTrigger = true;
                    break;
            }

            if ($shouldTrigger) {
                $alert->update([
                    'triggered_at' => now(),
                ]);

                // Envoyer les notifications seulement si demandé
                if ($sendNotifications) {
                    $this->sendAlertNotifications($alert->fresh());
                }

                $triggeredAlerts[] = $alert;
            }
        }

        return $triggeredAlerts;
    }
}

