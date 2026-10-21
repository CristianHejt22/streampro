<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'customer_id',
        'user_id',
        'service_id',
        'service_plan_id',
        'months_duration',
        'amount',
        'payment_method',
        'receipt_path',
        'customer_notes',
        'admin_notes',
        'status', // pending, approved, rejected
        'review_requested_to_provider',
        'provider_id',
        'provider_review_notes',
        'provider_review_requested_at',
        'provider_response_notes',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'months_duration' => 'integer',
        'review_requested_to_provider' => 'boolean',
        'provider_review_requested_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function plan()
    {
        return $this->belongsTo(ServicePlan::class, 'service_plan_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isReviewRequestedToProvider(): bool
    {
        return (bool) $this->review_requested_to_provider;
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'approved' => ['label' => 'Renovada', 'class' => 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30', 'icon' => 'check-circle'],
            'rejected' => ['label' => 'Rechazada', 'class' => 'bg-rose-500/20 text-rose-400 border border-rose-500/30', 'icon' => 'x-circle'],
            default => ['label' => 'Pendiente de Aprobación', 'class' => 'bg-amber-500/20 text-amber-400 border border-amber-500/30 animate-pulse', 'icon' => 'clock'],
        };
    }
}
