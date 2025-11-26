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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('target_price', 10, 2);
            $table->enum('alert_type', ['PRICE_DROP', 'PRICE_INCREASE', 'STOCK_AVAILABLE'])->default('PRICE_DROP');
            $table->boolean('is_active')->default(true);
            $table->boolean('email_sent')->default(false);
            $table->boolean('whatsapp_sent')->default(false);
            $table->boolean('push_sent')->default(false);
            $table->datetime('triggered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
