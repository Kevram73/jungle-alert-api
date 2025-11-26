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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('plan', 50); // "premium_simple" or "premium_deluxe"
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('EUR');
            $table->datetime('starts_at');
            $table->datetime('expires_at');
            $table->string('payment_reference')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
