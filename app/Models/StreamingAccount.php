<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StreamingAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'supplier_id',
        'account_email',
        'account_password',
        'recovery_email',
        'modality',
        'total_profiles',
        'supplier_cost',
        'supplier_expiration_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'supplier_cost' => 'decimal:2',
        'supplier_expiration_date' => 'date',
        'total_profiles' => 'integer',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function profiles()
    {
        return $this->hasMany(AccountProfile::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function availableProfiles()
    {
        return $this->profiles()->where('status', 'available');
    }

    public function getAvailableProfilesCountAttribute(): int
    {
        return $this->profiles()->where('status', 'available')->count();
    }

    public function getOccupiedProfilesCountAttribute(): int
    {
        return $this->profiles()->where('status', 'assigned')->count();
    }

    public function getDaysUntilSupplierExpirationAttribute(): ?int
    {
        if (!$this->supplier_expiration_date) {
            return null;
        }
        return (int) Carbon::now()->startOfDay()->diffInDays(Carbon::parse($this->supplier_expiration_date)->startOfDay(), false);
    }

    public function getEmailAttribute(): ?string
    {
        return $this->account_email;
    }

    public function setAccountPasswordAttribute($value)
    {
        $this->attributes['account_password'] = !empty($value) ? \Illuminate\Support\Facades\Crypt::encryptString($value) : $value;
    }

    public function getAccountPasswordAttribute($value)
    {
        if (empty($value)) {
            return $value;
        }
        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return $value;
        }
    }

    public function getPasswordAttribute(): ?string
    {
        return $this->account_password;
    }
}
