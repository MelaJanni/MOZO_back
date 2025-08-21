<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'avatar',
        'display_name',
        'business_name',
        'position',
        'corporate_email',
        'corporate_phone',
        'office_extension',
        'business_description',
        'business_website',
        'social_media',
        'is_primary_admin',
        'permissions',
        'last_active_at',
        'notify_new_orders',
        'notify_staff_requests',
        'notify_reviews',
        'notify_payments',
    ];

    protected $casts = [
        'social_media' => 'array',
        'permissions' => 'array',
        'is_primary_admin' => 'boolean',
        'last_active_at' => 'datetime',
        'notify_new_orders' => 'boolean',
        'notify_staff_requests' => 'boolean',
        'notify_reviews' => 'boolean',
        'notify_payments' => 'boolean',
    ];

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el negocio
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
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
        
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->display_name) . '&color=DC2626&background=FEE2E2';
    }

    /**
     * Obtener el email corporativo o el email del usuario
     */
    public function getCorporateEmailAttribute($value): string
    {
        return $value ?: $this->user->email;
    }

    /**
     * Verificar si el perfil está completo
     */
    public function isComplete(): bool
    {
        return !empty($this->business_name) && 
               !empty($this->position) && 
               !empty($this->corporate_phone);
    }

    /**
     * Actualizar la última actividad
     */
    public function updateLastActive(): void
    {
        $this->update(['last_active_at' => now()]);
    }
}
