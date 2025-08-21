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
     * Relación con perfil de mozo (único por usuario)
     */
    public function waiterProfile()
    {
        return $this->hasOne(WaiterProfile::class);
    }

    /**
     * Relación con perfil de admin (único por usuario)
     */
    public function adminProfile()
    {
        return $this->hasOne(AdminProfile::class);
    }

    /**
     * Obtener el perfil activo según el rol del usuario
     */
    public function getActiveProfile()
    {
        if ($this->isAdmin()) {
            return $this->adminProfile;
        }

        if ($this->isWaiter()) {
            return $this->waiterProfile;
        }

        return null;
    }

    /**
     * Crear o actualizar perfil según el rol
     */
    public function createOrUpdateProfile($data)
    {
        if ($this->isAdmin()) {
            return $this->adminProfile()->updateOrCreate(
                ['user_id' => $this->id],
                $data
            );
        }

        if ($this->isWaiter()) {
            return $this->waiterProfile()->updateOrCreate(
                ['user_id' => $this->id],
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
