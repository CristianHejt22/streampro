<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerServiceCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'reseller_id',
        'service_id',
        'commission_pct',
    ];

    protected $casts = [
        'commission_pct' => 'decimal:2',
    ];

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function reseller()
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
