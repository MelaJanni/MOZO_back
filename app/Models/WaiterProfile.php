<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaiterProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'avatar',
        'display_name',
        'bio',
        'phone',
        'birth_date',
        'height',
        'weight',
        'gender',
        'experience_years',
        'employment_type',
        'current_schedule',
        'current_location',
        'latitude',
        'longitude',
        'availability_hours',
        'skills',
        'is_active',
        'is_available',
        'rating',
        'total_reviews',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'height' => 'decimal:2',
        'weight' => 'integer',
        'experience_years' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'availability_hours' => 'array',
        'skills' => 'array',
        'is_active' => 'boolean',
        'is_available' => 'boolean',
    // Explicit string casts for enums stored as strings in DB
    'employment_type' => 'string',
    'current_schedule' => 'string',
        'rating' => 'decimal:2',
        'total_reviews' => 'integer',
    ];

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    /**
     * Obtener el nombre a mostrar (display_name o nombre del usuario)
     */
    public function getDisplayNameAttribute($value): string
    {
        return $value ?: $this->user->name;
    }

    /**
     * Obtener el avatar o uno por defecto
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->display_name) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Verificar si el perfil está completo
     */
    public function isComplete(): bool
    {
        return !empty($this->birth_date) && 
               !empty($this->height) && 
               !empty($this->weight) && 
               !empty($this->gender) && 
               !empty($this->employment_type) && 
               !empty($this->current_schedule);
    }
}
