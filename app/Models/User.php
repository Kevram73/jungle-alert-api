<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'username',
        'hashed_password',
        'first_name',
        'last_name',
        'profile_picture_url',
        'subscription_tier',
        'subscription_start_date',
        'subscription_end_date',
        'stripe_customer_id',
        'email_notifications',
        'whatsapp_notifications',
        'push_notifications',
        'whatsapp_number',
        'fcm_token',
        'is_active',
        'is_verified',
        'verification_token',
        'gdpr_consent',
        'data_retention_consent',
        'newsletter_consent',
        'last_login',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'hashed_password', // Changed from 'password'
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'email_notifications' => 'boolean',
        'whatsapp_notifications' => 'boolean',
        'push_notifications' => 'boolean',
        'gdpr_consent' => 'boolean',
        'data_retention_consent' => 'boolean',
        'newsletter_consent' => 'boolean',
        'last_login' => 'datetime',
        'subscription_start_date' => 'datetime',
        'subscription_end_date' => 'datetime',
    ];

    // Relations
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->hashed_password;
    }

    /**
     * Set the password attribute.
     *
     * @param  string  $value
     * @return void
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['hashed_password'] = bcrypt($value);
    }
}