<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'file_path',
        'is_default',
        'display_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
} 