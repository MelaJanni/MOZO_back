<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'business_id',
        'is_active',
        // Datos personales del mozo
        'phone',
        'address',
        'bio',
        'profile_picture',
        'date_of_birth',
        'gender',
        'height',
        'weight',
        // Datos laborales
        'experience_years',
        'employment_type',
        'current_schedule',
        'skills',
        // Ubicación
        'latitude',
        'longitude'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'date_of_birth' => 'date',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'skills' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];

    protected $appends = ['age', 'profile_picture_url'];

    /**
     * Obtener edad calculada
     */
    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Obtener URL completa de la foto de perfil
     */
    public function getProfilePictureUrlAttribute()
    {
        return $this->profile_picture ? asset('storage/' . $this->profile_picture) : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function tables()
    {
        return $this->belongsToMany(Table::class)->withTimestamps();
    }

    /**
     * Scope para perfiles del usuario autenticado
     */
    public function scopeForCurrentUser($query)
    {
        return $query->where('user_id', Auth::id());
    }

    /**
     * Obtener mesas disponibles para activar
     */
    public function getAvailableTables()
    {
        return $this->tables()->whereNull('active_waiter_id')->get();
    }

    /**
     * Obtener mesas ocupadas (conflictos)
     */
    public function getConflictingTables()
    {
        return $this->tables()
            ->whereNotNull('active_waiter_id')
            ->where('active_waiter_id', '!=', $this->user_id)
            ->with('activeWaiter')
            ->get();
    }

    /**
     * Obtener mesas ya asignadas al dueño del perfil
     */
    public function getOwnTables()
    {
        return $this->tables()
            ->where('active_waiter_id', $this->user_id)
            ->get();
    }

    /**
     * Activar perfil completo
     */
    public function activate()
    {
        $available = $this->getAvailableTables();
        $conflicts = $this->getConflictingTables();
        $own = $this->getOwnTables();

        // Activar mesas disponibles
        foreach ($available as $table) {
            $table->update([
                'active_waiter_id' => $this->user_id,
                'waiter_assigned_at' => now(),
                'notifications_enabled' => true
            ]);
        }

        // Marcar perfil como activo
        $this->update(['is_active' => true]);

        return [
            'success' => true,
            'activated_tables' => $available->count(),
            'conflicting_tables' => $conflicts,
            'own_tables' => $own->count(),
            'total_tables' => $this->tables->count()
        ];
    }
} 