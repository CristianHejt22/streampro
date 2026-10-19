<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warranty extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'user_id',
        'issue_type',
        'description',
        'old_streaming_account_id',
        'old_account_profile_id',
        'new_streaming_account_id',
        'new_account_profile_id',
        'status',
        'resolution_notes',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function oldAccount()
    {
        return $this->belongsTo(StreamingAccount::class, 'old_streaming_account_id');
    }

    public function oldProfile()
    {
        return $this->belongsTo(AccountProfile::class, 'old_account_profile_id');
    }

    public function newAccount()
    {
        return $this->belongsTo(StreamingAccount::class, 'new_streaming_account_id');
    }

    public function newProfile()
    {
        return $this->belongsTo(AccountProfile::class, 'new_account_profile_id');
    }
}
