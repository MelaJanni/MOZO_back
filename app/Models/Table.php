<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'business_id',
        'notifications_enabled',
        'capacity',
        'location',
        'status',
    ];

    protected $casts = [
        'notifications_enabled' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function qrCodes()
    {
        return $this->hasMany(QrCode::class);
    }

    public function qrCode()
    {
        return $this->hasOne(QrCode::class)->latestOfMany();
    }

    public function profiles()
    {
        return $this->belongsToMany(Profile::class);
    }
} 