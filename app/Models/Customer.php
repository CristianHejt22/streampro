<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'email',
        'country',
        'notes',
    ];

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
