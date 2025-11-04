<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserActiveRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'active_role',
        'switched_at',
    ];

    protected $casts = [
        'switched_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}