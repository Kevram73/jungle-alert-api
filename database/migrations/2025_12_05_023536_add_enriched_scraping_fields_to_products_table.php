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
        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock_quantity')->nullable()->after('review_count');
            $table->string('stock_status')->nullable()->after('stock_quantity');
            $table->string('brand')->nullable()->after('stock_status');
            $table->string('seller')->nullable()->after('brand');
            $table->boolean('is_prime')->default(false)->after('seller');
            $table->decimal('original_price', 10, 2)->nullable()->after('current_price');
            $table->decimal('discount_percentage', 5, 2)->nullable()->after('original_price');
            $table->text('category_path')->nullable()->after('category');
            $table->json('features')->nullable()->after('description');
            $table->json('images')->nullable()->after('image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'stock_quantity',
                'stock_status',
                'brand',
                'seller',
                'is_prime',
                'original_price',
                'discount_percentage',
                'category_path',
                'features',
                'images',
            ]);
        });
    }
};
