<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'category',
        'color_hex',
        'icon_url',
        'max_profiles_default',
        'description',
        'default_rules',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isIndividual(): bool
    {
        return !is_null($this->user_id);
    }

    public function plans()
    {
        return $this->hasMany(ServicePlan::class);
    }

    public function streamingAccounts()
    {
        return $this->hasMany(StreamingAccount::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    // Estadísticas de stock en tiempo real
    public function getAvailableProfilesCountAttribute(): int
    {
        return AccountProfile::whereHas('streamingAccount', function ($q) {
            $q->where('service_id', $this->id)->where('status', 'active');
        })->where('status', 'available')->count();
    }

    public function getAvailableFullAccountsCountAttribute(): int
    {
        return $this->streamingAccounts()
            ->where('modality', 'full_account')
            ->where('status', 'active')
            ->whereDoesntHave('sales', function ($q) {
                $q->whereIn('status', ['active', 'expiring_soon']);
            })->count();
    }

    public function getAvailableAccountsCountAttribute(): int
    {
        return $this->available_full_accounts_count;
    }
}
