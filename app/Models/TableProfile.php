<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TableProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'user_id', 'name', 'description'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tables()
    {
        return $this->belongsToMany(Table::class, 'table_profile_table');
    }
}
