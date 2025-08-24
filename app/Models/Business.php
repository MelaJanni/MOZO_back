<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Menu;
use App\Models\QrCode;
use App\Models\BusinessSlugAlias;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'invitation_code',
        'description',
        'logo',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    // ========================================
    // RELACIONES MULTI-ROL
    // ========================================

    /** Administradores del negocio */
    public function admins()
    {
        return $this->belongsToMany(User::class, 'business_admins')
                    ->withPivot(['permission_level', 'permissions', 'is_active', 'is_primary', 'joined_at'])
                    ->withTimestamps();
    }

    /** Admin primario (único actual) */
    public function primaryAdmin()
    {
        return $this->admins()
            ->wherePivot('is_active', true)
            ->wherePivot('is_primary', true)
            ->first();
    }

    /** Mozos del negocio */
    public function waiters()
    {
        return $this->belongsToMany(User::class, 'business_waiters')
                    ->withPivot(['employment_status', 'employment_type', 'hourly_rate', 'work_schedule', 'hired_at', 'last_shift_at'])
                    ->withTimestamps();
    }

    /** Todos los usuarios (admins y mozos) */
    public function allUsers()
    {
        $admins = $this->admins()->where('is_active', true)->get();
        $waiters = $this->waiters()->where('employment_status', 'active')->get();
        return $admins->merge($waiters)->unique('id');
    }

    /** Mesas del negocio */
    public function tables()
    {
        return $this->hasMany(Table::class);
    }

    /** Menús del negocio */
    public function menus()
    {
        return $this->hasMany(Menu::class);
    }

    /** Alias históricos de slugs */
    public function slugAliases()
    {
        return $this->hasMany(BusinessSlugAlias::class);
    }

    /** Códigos QR del negocio */
    public function qrCodes()
    {
        return $this->hasMany(QrCode::class);
    }

    /** Roles activos de usuarios en este negocio */
    public function userActiveRoles()
    {
        return $this->hasMany(UserActiveRole::class);
    }

    // ========================================
    // MÉTODOS DE UTILIDAD
    // ========================================

    public function getSlugAttribute(): string
    {
        return Str::slug($this->name);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($business) {
            if (!$business->invitation_code) {
                $business->invitation_code = self::generateInvitationCode();
            }
        });

        // Si cambia el nombre, guardamos el slug anterior como alias para no romper URLs de QR existentes
        static::updating(function ($business) {
            if ($business->isDirty('name')) {
                $originalName = $business->getOriginal('name');
                if ($originalName) {
                    $oldSlug = Str::slug($originalName);
                    // Evitar duplicar alias si ya existe
                    if (!BusinessSlugAlias::where('business_id', $business->id)->where('slug', $oldSlug)->exists()) {
                        BusinessSlugAlias::create([
                            'business_id' => $business->id,
                            'slug' => $oldSlug,
                        ]);
                    }
                }
            }
        });

        // Tras actualizar, si cambió el nombre, reescribir URLs de QRs al nuevo slug
        static::updated(function ($business) {
            if ($business->wasChanged('name')) {
                $newSlug = Str::slug($business->name);
                $baseUrl = config('app.frontend_url', 'https://mozoqr.com');
                QrCode::where('business_id', $business->id)->get()->each(function ($qr) use ($newSlug, $baseUrl) {
                    $code = $qr->code;
                    if ($code) {
                        $qr->url = rtrim($baseUrl, '/') . "/qr/{$newSlug}/{$code}";
                        $qr->save();
                    }
                });
            }
        });
    }

    public static function generateInvitationCode(): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
        } while (self::where('invitation_code', $code)->exists());
        return $code;
    }

    public function regenerateInvitationCode(): bool
    {
        $this->invitation_code = self::generateInvitationCode();
        return $this->save();
    }

    public function isAdministratedBy(User $user): bool
    {
        return $this->admins()
                   ->where('business_admins.user_id', $user->id)
                   ->where('business_admins.is_active', true)
                   ->exists();
    }

    public function hasWaiter(User $user): bool
    {
        return $this->waiters()
                   ->where('user_id', $user->id)
                   ->where('employment_status', 'active')
                   ->exists();
    }

    /**
     * Agregar o reemplazar administrador único.
     * - Si no hay admin: lo agrega.
     * - Si ya existe el mismo: actualiza datos pivot.
     * - Si existe otro distinto: opcionalmente reemplaza (por defecto true) dentro de una transacción.
     */
    public function addAdmin(User $user, string $permissionLevel = 'manager', array $permissions = [], bool $replaceIfExists = true): bool
    {
        return DB::transaction(function () use ($user, $permissionLevel, $permissions, $replaceIfExists) {
            $permissionsJson = empty($permissions) ? null : json_encode($permissions);
            $current = $this->primaryAdmin();
            if ($current && $current->id === $user->id) {
                $this->admins()->updateExistingPivot($user->id, [
                    'permission_level' => $permissionLevel,
                    'permissions' => $permissionsJson,
                    'is_active' => true,
                    'is_primary' => true,
                ]);
                return true;
            }
            if ($current && $current->id !== $user->id) {
                if (!$replaceIfExists) {
                    return false;
                }
                $this->admins()->detach($current->id);
            }
            $this->admins()->syncWithoutDetaching([
                $user->id => [
                    'permission_level' => $permissionLevel,
                    'permissions' => $permissionsJson,
                    'is_active' => true,
                    'is_primary' => true,
                    'joined_at' => now(),
                ]
            ]);
            return true;
        });
    }

    /** Agregar un mozo */
    public function addWaiter(User $user, string $employmentType = 'tiempo completo', ?float $hourlyRate = null): bool
    {
        $this->waiters()->syncWithoutDetaching([
            $user->id => [
                'employment_status' => 'active',
                'employment_type' => $employmentType,
                'hourly_rate' => $hourlyRate,
                'hired_at' => now()
            ]
        ]);
        return true;
    }
}