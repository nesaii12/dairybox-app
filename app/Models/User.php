<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'status',
        'restricted_until',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'restricted_until' => 'datetime',
        ];
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user is a customer.
     */
    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    /**
     * Check if the user is a rider.
     */
    public function isRider(): bool
    {
        return $this->role === 'rider';
    }

    /**
     * Check if the customer is currently restricted.
     */
    public function isRestricted(): bool
    {
        return $this->status === 'restricted'
            && $this->restricted_until
            && $this->restricted_until->isFuture();
    }

    /**
     * Get the customer's orders.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /**
     * Get the rider's assigned deliveries.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'rider_id');
    }

    /**
     * Get the customer's cart.
     */
    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class, 'customer_id');
    }

    /**
     * Get the customer's ratings.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class, 'customer_id');
    }

    /**
     * Get the user's notifications.
     */
    public function appNotifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }
}
