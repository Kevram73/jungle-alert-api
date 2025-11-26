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
        'image_url',
        'current_price',
        'target_price',
        'asin',
        'is_active',
        'currency',
        'marketplace',
    ];

    protected $casts = [
        'current_price' => 'decimal:2',
        'target_price' => 'decimal:2',
        'is_active' => 'boolean',
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