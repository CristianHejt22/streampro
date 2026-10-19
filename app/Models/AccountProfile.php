<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'streaming_account_id',
        'profile_number',
        'profile_name',
        'pin',
        'status',
        'notes',
    ];

    public function streamingAccount()
    {
        return $this->belongsTo(StreamingAccount::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function activeSale()
    {
        return $this->hasOne(Sale::class)->whereIn('status', ['active', 'expiring_soon'])->latest();
    }
}
