<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'table_id',
        'customer_name',
        'customer_email',
        'rating',
        'comment',
        'service_details',
        'is_approved',
        'is_featured',
        'staff_id',
    ];

    protected $casts = [
        'rating' => 'integer',
        'service_details' => 'array',
        'is_approved' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
} 