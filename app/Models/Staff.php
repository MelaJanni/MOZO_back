<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Staff extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'position',
        'email',
        'phone',
        'hire_date',
        'salary',
        'status',
        'notes',
        'birth_date',
        'height',
        'weight',
        'gender',
        'experience_years',
        'seniority_years',
        'education',
        'employment_type',
        'current_schedule',
        'avatar_path',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'salary' => 'decimal:2',
        'birth_date' => 'date',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
    ];

    protected $appends = ['age', 'avatar_url', 'birthdate_formatted'];

    public function getAgeAttribute()
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }

    public function getAvatarUrlAttribute()
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    /**
     * Devuelve la fecha de nacimiento formateada dd-mm-YYYY.
     */
    public function getBirthdateFormattedAttribute()
    {
        return $this->birth_date ? $this->birth_date->format('d-m-Y') : null;
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
} 