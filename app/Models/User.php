<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'active_business_id',
        'google_id',
        'google_avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function businesses()
    {
        return $this->belongsToMany(Business::class, 'business_user');
    }

    public function activeBusiness()
    {
        return $this->belongsTo(Business::class, 'active_business_id');
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function staffRecords()
    {
        return $this->hasMany(Staff::class);
    }

    public function workExperiences()
    {
        return $this->hasMany(WorkExperience::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isWaiter()
    {
        return $this->role === 'waiter';
    }

    public function getBusinessIdAttribute()
    {
        return $this->active_business_id;
    }
    
    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function routeNotificationForFcm(): array
    {
        return $this->deviceTokens()->pluck('token')->toArray();
    }

    /**
     * Relación con perfiles de mozo (puede tener múltiples en diferentes negocios)
     */
    public function waiterProfiles()
    {
        return $this->hasMany(WaiterProfile::class);
    }

    /**
     * Relación con perfiles de admin (puede tener múltiples en diferentes negocios)
     */
    public function adminProfiles()
    {
        return $this->hasMany(AdminProfile::class);
    }

    /**
     * Obtener el perfil de mozo para un negocio específico
     */
    public function waiterProfileForBusiness($businessId)
    {
        return $this->waiterProfiles()->where('business_id', $businessId)->first();
    }

    /**
     * Obtener el perfil de admin para un negocio específico
     */
    public function adminProfileForBusiness($businessId)
    {
        return $this->adminProfiles()->where('business_id', $businessId)->first();
    }

    /**
     * Obtener el perfil activo según el rol y negocio actual
     */
    public function getActiveProfile()
    {
        if (!$this->active_business_id) {
            return null;
        }

        if ($this->isAdmin()) {
            return $this->adminProfileForBusiness($this->active_business_id);
        }

        if ($this->isWaiter()) {
            return $this->waiterProfileForBusiness($this->active_business_id);
        }

        return null;
    }

    /**
     * Crear o actualizar perfil según el rol
     */
    public function createOrUpdateProfile($businessId, $data)
    {
        if ($this->isAdmin()) {
            return $this->adminProfiles()->updateOrCreate(
                ['business_id' => $businessId],
                $data
            );
        }

        if ($this->isWaiter()) {
            return $this->waiterProfiles()->updateOrCreate(
                ['business_id' => $businessId],
                $data
            );
        }

        return null;
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
