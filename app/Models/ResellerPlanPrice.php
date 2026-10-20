<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerPlanPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'reseller_id',
        'service_plan_id',
        'provider_cost',
        'reseller_pvp',
    ];

    protected $casts = [
        'provider_cost' => 'decimal:2',
        'reseller_pvp' => 'decimal:2',
    ];

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function reseller()
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }

    public function servicePlan()
    {
        return $this->belongsTo(ServicePlan::class, 'service_plan_id');
    }
}
