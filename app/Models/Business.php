<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'join_code',
        'code',
        'address',
        'phone',
        'email',
        'logo',
        'working_hours',
        'notification_preferences',
    ];

    protected $casts = [
        'working_hours' => 'array',
        'notification_preferences' => 'array',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function tables()
    {
        return $this->hasMany(Table::class);
    }

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }
    
    public function staff()
    {
        return $this->hasMany(Staff::class);
    }
    
    public function archivedStaff()
    {
        return $this->hasMany(ArchivedStaff::class);
    }
    
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function qrCodes()
    {
        return $this->hasMany(QrCode::class);
    }

    public function getSlugAttribute(): string
    {
        return Str::slug($this->name);
    }
} 