<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendTestPushNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:test {email? : Email of the user to send notification to} {--fcm-key= : FCM Server Key (optional, overrides .env)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test push notification to a user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email') ?? 'jeankiller1@gmail.com';
        
        $this->info("🔔 Sending test push notification to: {$email}");

        // Find user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("❌ User not found with email: {$email}");
            return Command::FAILURE;
        }

        $this->info("✅ User found: {$user->username} (ID: {$user->id})");

        // Check if user has FCM token
        if (!$user->fcm_token) {
            $this->warn("⚠️  User does not have an FCM token. Push notifications require the user to have logged in from the mobile app.");
            $this->info("💡 To get an FCM token, the user needs to:");
            $this->info("   1. Open the mobile app");
            $this->info("   2. Log in with this email");
            $this->info("   3. Grant notification permissions");
            
            // Ask if we should still try to send (maybe they want to test the API)
            if (!$this->confirm('Do you want to test the FCM API anyway? (This will fail without a token)', false)) {
                return Command::FAILURE;
            }
        } else {
            $this->info("✅ FCM token found: " . substr($user->fcm_token, 0, 20) . "...");
        }

        // Check FCM server key
        $fcmServerKey = $this->option('fcm-key') ?? env('FCM_SERVER_KEY');
        if (!$fcmServerKey) {
            $this->error("❌ FCM_SERVER_KEY not configured in .env file");
            $this->info("💡 Add FCM_SERVER_KEY=your_key to your .env file");
            $this->info("💡 Or use --fcm-key option: php artisan push:test {$email} --fcm-key=YOUR_KEY");
            return Command::FAILURE;
        }

        $this->info("✅ FCM Server Key configured");

        // Prepare notification
        $title = "🎯 Test Push Notification";
        $body = "This is a test notification from Jungle Alert! If you receive this, push notifications are working correctly.";

        $this->info("📤 Sending notification...");
        $this->info("   Title: {$title}");
        $this->info("   Body: {$body}");

        try {
            $payload = [
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'badge' => '1',
                ],
                'data' => [
                    'type' => 'test',
                    'message' => 'This is a test notification',
                    'timestamp' => now()->toIso8601String(),
                ],
                'priority' => 'high',
            ];

            // If user has FCM token, send to specific device
            if ($user->fcm_token) {
                $payload['to'] = $user->fcm_token;
            } else {
                // This will fail, but we can test the API
                $payload['to'] = 'test_token_that_does_not_exist';
            }

            // Use FCM Legacy API (still works for server key)
            // Note: For production, consider migrating to FCM v1 API with OAuth2
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $fcmServerKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post('https://fcm.googleapis.com/fcm/send', $payload);
            
            $this->line("   FCM API URL: https://fcm.googleapis.com/fcm/send");
            $this->line("   Using Legacy API with Server Key");

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['success']) && $responseData['success'] == 1) {
                    $this->info("✅ Push notification sent successfully!");
                    $this->info("   Message ID: " . ($responseData['message_id'] ?? 'N/A'));
                    Log::info("Test push notification sent to user {$user->id} ({$email})");
                    return Command::SUCCESS;
                } else {
                    $this->error("❌ FCM returned an error:");
                    $this->error("   " . json_encode($responseData, JSON_PRETTY_PRINT));
                    
                    if (isset($responseData['results'][0]['error'])) {
                        $error = $responseData['results'][0]['error'];
                        $this->error("   Error: {$error}");
                        
                        if ($error === 'InvalidRegistration' || $error === 'NotRegistered') {
                            $this->warn("💡 The FCM token is invalid or expired. The user needs to log in again from the mobile app.");
                        }
                    }
                    
                    return Command::FAILURE;
                }
            } else {
                $this->error("❌ Failed to send push notification");
                $this->error("   Status: {$response->status()}");
                $this->error("   Response: {$response->body()}");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
            Log::error("Exception sending test push notification: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

