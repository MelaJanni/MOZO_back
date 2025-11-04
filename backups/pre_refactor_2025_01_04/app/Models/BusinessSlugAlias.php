<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessSlugAlias extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'slug',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
