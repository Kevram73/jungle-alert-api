<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->string('hashed_password');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('profile_picture_url')->nullable();
            $table->enum('subscription_tier', ['FREE', 'PREMIUM_SIMPLE', 'PREMIUM_DELUXE'])->default('FREE');
            $table->datetime('subscription_start_date')->nullable();
            $table->datetime('subscription_end_date')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->boolean('email_notifications')->default(true);
            $table->boolean('whatsapp_notifications')->default(false);
            $table->boolean('push_notifications')->default(true);
            $table->string('whatsapp_number')->nullable();
            $table->text('fcm_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->string('verification_token')->nullable();
            $table->boolean('gdpr_consent')->default(false);
            $table->boolean('data_retention_consent')->default(false);
            $table->datetime('last_login')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
