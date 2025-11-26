<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'target_price',
        'alert_type',
        'is_active',
        'email_sent',
        'whatsapp_sent',
        'push_sent',
        'triggered_at',
    ];

    protected $casts = [
        'target_price' => 'decimal:2',
        'is_active' => 'boolean',
        'email_sent' => 'boolean',
        'whatsapp_sent' => 'boolean',
        'push_sent' => 'boolean',
        'triggered_at' => 'datetime',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
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

    public function scopeTriggered($query)
    {
        return $query->whereNotNull('triggered_at');
    }
}