<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'balance',
        'reseller_plan',
        'referral_code',
        'brand_name',
        'brand_logo',
        'brand_favicon',
        'brand_primary_color',
        'brand_accent_color',
        'custom_login_bg',
        'support_whatsapp',
        'support_telegram',
        'mp_public_key',
        'mp_access_token',
        'bank_cbu',
        'bank_cvu',
        'bank_alias',
        'bank_holder_name',
        'bank_dni',
        'binance_id',
        'bank_instructions',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mp_access_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'balance' => 'decimal:2',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isReseller(): bool
    {
        return $this->role === 'reseller';
    }

    public function hasBalance(float $amount): bool
    {
        return (float)$this->balance >= $amount;
    }

    public function deductBalance(float $amount): bool
    {
        if ($this->hasBalance($amount)) {
            $this->decrement('balance', $amount);
            return true;
        }
        return false;
    }

    public function addBalance(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function streamingAccounts()
    {
        return $this->hasMany(StreamingAccount::class, 'created_by');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    public function shortcuts()
    {
        return $this->hasMany(Shortcut::class);
    }

    public function warranties()
    {
        return $this->hasMany(Warranty::class);
    }
}
