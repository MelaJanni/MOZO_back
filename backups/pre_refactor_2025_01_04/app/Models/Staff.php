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
        'user_id',
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
        'invitation_token',
        'invitation_sent_at',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'salary' => 'decimal:2',
        'birth_date' => 'date',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'invitation_sent_at' => 'datetime',
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

    public function getBirthdateFormattedAttribute()
    {
        return $this->birth_date ? $this->birth_date->format('d-m-Y') : null;
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Verificar si el staff está conectado a un usuario
     */
    public function isConnectedToUser(): bool
    {
        return !is_null($this->user_id);
    }

    /**
     * Obtener datos del perfil del usuario conectado
     */
    public function getUserProfileData()
    {
        if (!$this->user_id) {
            return null;
        }

        return $this->user->profile;
    }
} 