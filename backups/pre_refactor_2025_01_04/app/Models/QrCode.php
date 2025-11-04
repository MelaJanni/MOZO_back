<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QrCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_id',
        'business_id',
        'code',
        'url',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }
} 