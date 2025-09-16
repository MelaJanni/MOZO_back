<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'google_avatar',
        'is_system_super_admin',
        'is_lifetime_paid',
        'membership_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_system_super_admin' => 'boolean',
        'is_lifetime_paid' => 'boolean',
        'membership_expires_at' => 'datetime',
    ];

    // ========================================
    // RELACIONES MULTI-ROL Y MULTI-NEGOCIO
    // ========================================

    /**
     * Negocios donde el usuario es ADMINISTRADOR
     */
    public function businessesAsAdmin()
    {
        return $this->belongsToMany(Business::class, 'business_admins')
                    ->withPivot(['permission_level', 'permissions', 'is_active', 'joined_at'])
                    ->withTimestamps();
    }

    /**
     * Negocios donde el usuario es MOZO
     */
    public function businessesAsWaiter()
    {
        return $this->belongsToMany(Business::class, 'business_waiters')
                    ->withPivot(['employment_status', 'employment_type', 'hourly_rate', 'work_schedule', 'hired_at', 'last_shift_at'])
                    ->withTimestamps();
    }

    /**
     * Roles activos del usuario por negocio
     */
    public function activeRoles()
    {
        return $this->hasMany(UserActiveRole::class);
    }

    /**
     * Perfil de mozo (único y global)
     */
    public function waiterProfile()
    {
        return $this->hasOne(WaiterProfile::class);
    }

    /**
     * Perfil de admin (único y global)
     */
    public function adminProfile()
    {
        return $this->hasOne(AdminProfile::class);
    }

    /**
     * Tokens de dispositivos
     */
    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    // ========================================
    // MÉTODOS DE VERIFICACIÓN DE ROLES
    // ========================================

    /**
     * Verifica si el usuario es admin en algún negocio
     */
    public function isAdmin(?int $businessId = null): bool
    {
        $query = $this->businessesAsAdmin()->where('business_admins.is_active', true);
        if ($businessId) {
            $query->where('business_admins.business_id', $businessId);
        }
        return $query->exists();
    }

    /**
     * Verifica si el usuario es mozo en algún negocio
     */
    public function isWaiter(?int $businessId = null): bool
    {
        $query = $this->businessesAsWaiter()->where('business_waiters.employment_status', 'active');
        if ($businessId) {
            $query->where('business_waiters.business_id', $businessId);
        }
        return $query->exists();
    }

    /**
     * Verifica si puede trabajar en ambos roles en un negocio
     */
    public function canSwitchRoles(int $businessId): bool
    {
        return $this->isAdmin($businessId) && $this->isWaiter($businessId);
    }

    /**
     * Obtiene el rol activo en un negocio específico
     */
    public function getActiveRole(int $businessId): ?string
    {
        $activeRole = $this->activeRoles()
                          ->where('business_id', $businessId)
                          ->first();
        
        return $activeRole ? $activeRole->active_role : null;
    }

    /**
     * Cambia el rol activo en un negocio
     */
    public function switchRole(int $businessId, string $role): bool
    {
        if (!in_array($role, ['admin', 'waiter'])) {
            return false;
        }

        if (!$this->canSwitchRoles($businessId)) {
            return false;
        }

        $this->activeRoles()->updateOrCreate(
            ['business_id' => $businessId],
            [
                'active_role' => $role,
                'switched_at' => now()
            ]
        );

        return true;
    }

    /**
     * Obtiene todos los negocios del usuario (como admin o mozo)
     */
    public function getAllBusinesses()
    {
        $adminBusinesses = $this->businessesAsAdmin()->where('business_admins.is_active', true)->get();
        $waiterBusinesses = $this->businessesAsWaiter()->where('business_waiters.employment_status', 'active')->get();
        
        return $adminBusinesses->merge($waiterBusinesses)->unique('id');
    }


    /**
     * Accessor unificado: $user->profile devuelve AdminProfile o WaiterProfile (el que exista)
     * Nota: Se implementa como accessor para evitar conflicto con expectativa de relación Eloquent.
     */
    public function getProfileAttribute()
    {
        if ($this->relationLoaded('adminProfile') && $this->adminProfile) {
            return $this->adminProfile;
        }
        if ($this->relationLoaded('waiterProfile') && $this->waiterProfile) {
            return $this->waiterProfile;
        }
        // Lazy load en orden de prioridad
        if ($this->adminProfile) {
            return $this->adminProfile;
        }
        if ($this->waiterProfile) {
            return $this->waiterProfile;
        }
        return null;
    }

    /**
     * Obtiene el perfil según el rol activo en un negocio
     */
    public function getActiveProfile(?int $businessId = null)
    {
        if ($businessId) {
            $activeRole = $this->getActiveRole($businessId);
            if ($activeRole === 'admin' && $this->adminProfile) {
                return $this->adminProfile;
            }
            if ($activeRole === 'waiter' && $this->waiterProfile) {
                return $this->waiterProfile;
            }
        }

        // Fallback: devolver el perfil que existe
        if ($this->adminProfile) {
            return $this->adminProfile;
        }
        if ($this->waiterProfile) {
            return $this->waiterProfile;
        }

        return null;
    }

    // ========================================
    // MÉTODOS LEGACY (compatibilidad)
    // ========================================

    public function routeNotificationForFcm(): array
    {
        return $this->deviceTokens()->pluck('token')->toArray();
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    // ========================================
    // MEMBRESÍA
    // ========================================
    public function hasActiveMembership(): bool
    {
        if ($this->is_lifetime_paid ?? false) {
            return true;
        }
        // Fuente de verdad: Subscription activa o en trial
        $sub = $this->subscriptions()
            ->whereIn('status', ['active', 'in_trial'])
            ->orderByDesc('id')
            ->first();
        if (!$sub) {
            return false;
        }
        // Si está en trial, está activo
        if ($sub->status === 'in_trial') {
            return optional($sub->trial_ends_at)?->isFuture() ?? true;
        }
        // Activo: respetar current_period_end + grace_days
        $end = $sub->current_period_end;
        if (!$end) return false;
        $grace = (int) (config('billing.grace_days', 0));
        return now()->lessThanOrEqualTo(optional($end)->copy()->addDays($grace));
    }

    public function membershipDaysRemaining(): ?int
    {
        if ($this->is_lifetime_paid ?? false) {
            return PHP_INT_MAX; // Representa ilimitado
        }
        $sub = $this->subscriptions()
            ->whereIn('status', ['active', 'in_trial'])
            ->orderByDesc('id')
            ->first();
        if (!$sub) return null;
        $target = $sub->status === 'in_trial' ? $sub->trial_ends_at : $sub->current_period_end;
        if (!$target) return null;
        $grace = (int) (config('billing.grace_days', 0));
        $limit = optional($target)->copy()->addDays($grace);
        return now()->diffInDays($limit, false);
    }

    // ========================================
    // BILLING
    // ========================================
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->subscriptions()
            ->whereIn('status', ['active', 'in_trial'])
            ->orderByDesc('id')
            ->first();
    }

    // ========================================
    // FILAMENT ACCESS
    // ========================================
    public function canAccessPanel(Panel $panel): bool
    {
        // Solo usuarios dedicados del sistema pueden entrar al panel (no mozos ni admins de negocios)
        // Temporal: permitir acceso a usuarios con roles específicos
        return (bool)($this->is_system_super_admin ?? false)
            || $this->hasRole(['super_admin', 'admin'])
            || in_array($this->email, ['admin@mozoqr.com', 'test@admin.com']);
    }
}
