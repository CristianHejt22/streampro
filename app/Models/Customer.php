<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'user_id',
        'customer_code',
        'name',
        'phone',
        'email',
        'password',
        'status',
        'country',
        'notes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->customer_code)) {
                $customer->customer_code = 'CLI-' . rand(1000, 9999);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function activeSales()
    {
        return $this->sales()->whereIn('status', ['active', 'expiring_soon']);
    }

    // Limpia el número telefónico para WhatsApp API
    public function getCleanPhoneAttribute(): ?string
    {
        if (!$this->phone) return null;
        return preg_replace('/[^0-9]/', '', $this->phone);
    }
}
