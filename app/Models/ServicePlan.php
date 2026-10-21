<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'name',
        'modality',
        'duration_days',
        'price',
        'reseller_price',
        'cost_estimate',
        'screens_count',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'reseller_price' => 'decimal:2',
        'cost_estimate' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function getRetailPriceAttribute(): float
    {
        return (float) $this->price;
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
