<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amazon_url',
        'title',
        'description',
        'image_url',
        'current_price',
        'original_price',
        'target_price',
        'asin',
        'is_active',
        'currency',
        'marketplace',
        'availability',
        'rating',
        'review_count',
        'category',
        'category_path',
        'stock_quantity',
        'stock_status',
        'brand',
        'seller',
        'is_prime',
        'discount_percentage',
        'features',
        'images',
    ];

    protected $casts = [
        'current_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'target_price' => 'decimal:2',
        'is_active' => 'boolean',
        'rating' => 'decimal:2',
        'review_count' => 'integer',
        'stock_quantity' => 'integer',
        'is_prime' => 'boolean',
        'discount_percentage' => 'decimal:2',
        'features' => 'array',
        'images' => 'array',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function priceHistories()
    {
        return $this->hasMany(PriceHistory::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}