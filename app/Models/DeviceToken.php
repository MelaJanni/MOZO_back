<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
    'platform',
    'channel',
    'device_type',
    'device_name',
    'last_used_at',
    'expires_at',
    ];

    protected $casts = [
    'last_used_at' => 'datetime',
    'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}