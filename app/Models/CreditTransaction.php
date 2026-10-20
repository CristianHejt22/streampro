<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'admin_id',
        'type',
        'panel_tier',
        'credits_amount',
        'unit_price',
        'total_price',
        'target_panel_name',
        'notes',
    ];

    protected $casts = [
        'credits_amount' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function isBasic(): bool
    {
        return $this->panel_tier === 'basic';
    }

    public function isPro(): bool
    {
        return $this->panel_tier === 'pro';
    }
}
