<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shortcut extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'url',
        'icon',
        'category',
        'color',
        'badge',
        'is_external',
        'order_index',
    ];

    protected $casts = [
        'is_external' => 'boolean',
        'order_index' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
