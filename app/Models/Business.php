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
        'invitation_code',
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

    /**
     * Generar código de invitación único al crear el negocio
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($business) {
            if (!$business->invitation_code) {
                $business->invitation_code = self::generateInvitationCode();
            }
        });
    }

    /**
     * Generar código de invitación único
     */
    public static function generateInvitationCode(): string
    {
        do {
            $code = 'BIZ' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (self::where('invitation_code', $code)->exists());

        return $code;
    }

    /**
     * Regenerar código de invitación
     */
    public function regenerateInvitationCode(): bool
    {
        $this->invitation_code = self::generateInvitationCode();
        return $this->save();
    }
} 