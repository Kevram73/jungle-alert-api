<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Vérifier si l'admin existe déjà
        $admin = User::where('email', 'admin@junglealert.com')->first();

        if (!$admin) {
            User::create([
                'email' => 'admin@junglealert.com',
                'username' => 'admin',
                'hashed_password' => Hash::make('admin123'),
                'first_name' => 'Admin',
                'last_name' => 'Jungle Alert',
                'subscription_tier' => 'PREMIUM_DELUXE',
                'is_active' => true,
                'is_verified' => true,
                'email_notifications' => true,
                'push_notifications' => true,
                'whatsapp_notifications' => false,
                'gdpr_consent' => true,
                'data_retention_consent' => true,
                'newsletter_consent' => false,
            ]);

            $this->command->info('Compte administrateur créé avec succès !');
            $this->command->info('Email: admin@junglealert.com');
            $this->command->info('Mot de passe: admin123');
        } else {
            $this->command->info('Le compte administrateur existe déjà.');
        }
    }
}

