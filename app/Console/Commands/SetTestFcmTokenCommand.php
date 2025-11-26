<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class SetTestFcmTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fcm:set-test-token {email} {token?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set a test FCM token for a user (for testing purposes)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $token = $this->argument('token') ?? 'test_fcm_token_' . time();
        
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("❌ User not found with email: {$email}");
            return Command::FAILURE;
        }

        $user->update(['fcm_token' => $token]);

        $this->info("✅ FCM token set for user: {$user->username} ({$email})");
        $this->info("   Token: {$token}");
        $this->warn("⚠️  Note: This is a test token. For real push notifications, use a valid FCM token from Firebase.");

        return Command::SUCCESS;
    }
}

