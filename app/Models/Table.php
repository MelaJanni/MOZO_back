<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code', 
        'number',
        'business_id',
        'restaurant_id',
        'notifications_enabled',
        'capacity',
        'location',
        'status',
        'active_waiter_id',
        'waiter_assigned_at'
    ];

    protected $casts = [
        'notifications_enabled' => 'boolean',
        'waiter_assigned_at' => 'datetime'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function qrCodes()
    {
        return $this->hasMany(QrCode::class);
    }

    public function qrCode()
    {
        return $this->hasOne(QrCode::class)->latestOfMany();
    }

    public function profiles()
    {
        return $this->belongsToMany(Profile::class);
    }

    public function activeWaiter()
    {
        return $this->belongsTo(User::class, 'active_waiter_id');
    }

    public function waiterCalls()
    {
        return $this->hasMany(WaiterCall::class);
    }

    public function pendingCalls()
    {
        return $this->waiterCalls()->where('status', 'pending');
    }

    public function silences()
    {
        return $this->hasMany(TableSilence::class);
    }

    public function activeSilence()
    {
        return $this->silences()->active();
    }

    public function isSilenced()
    {
        $silence = $this->silences()->active()->first();
        return $silence && $silence->isActive();
    }

    public function canReceiveCalls()
    {
        return $this->notifications_enabled && 
               $this->active_waiter_id && 
               !$this->isSilenced();
    }

    public function assignWaiter(User $waiter)
    {
        $this->update([
            'active_waiter_id' => $waiter->id,
            'waiter_assigned_at' => now()
        ]);
    }

    public function unassignWaiter()
    {
        $previousWaiterId = $this->active_waiter_id;
        
        $this->update([
            'active_waiter_id' => null,
            'waiter_assigned_at' => null
        ]);

        // 🚀 AUTO-COMPLETAR: Verificar si esta mesa está en algún perfil activo
        if ($previousWaiterId) {
            $this->checkForActiveProfileAutoComplete($previousWaiterId);
        }
    }

    /**
     * Verificar si esta mesa debe auto-completarse en perfiles activos
     */
    private function checkForActiveProfileAutoComplete($previousWaiterId)
    {
        // Buscar perfiles activos que contengan esta mesa
        $activeProfiles = Profile::where('is_active', true)
            ->where('business_id', $this->business_id)
            ->whereHas('tables', function ($query) {
                $query->where('tables.id', $this->id);
            })
            ->with(['user', 'tables'])
            ->get();

        foreach ($activeProfiles as $profile) {
            // Skip si es el mismo mozo que se desasignó (evitar re-asignación inmediata)
            if ($profile->user_id === $previousWaiterId) {
                continue;
            }

            // Verificar que el dueño del perfil aún existe y está activo
            if (!$profile->user || !$profile->user->isWaiter()) {
                continue;
            }

            // Auto-asignar la mesa al dueño del perfil activo
            $this->update([
                'active_waiter_id' => $profile->user_id,
                'waiter_assigned_at' => now(),
                'notifications_enabled' => true
            ]);

            // Crear notificación de auto-completar
            $this->createAutoCompleteNotification($profile);
            
            \Log::info("Mesa auto-completada por perfil activo", [
                'table_id' => $this->id,
                'table_number' => $this->number,
                'profile_id' => $profile->id,
                'profile_name' => $profile->name,
                'new_waiter_id' => $profile->user_id,
                'new_waiter_name' => $profile->user->name,
                'previous_waiter_id' => $previousWaiterId
            ]);
            
            break; // Solo asignar al primer perfil activo encontrado
        }
    }

    /**
     * Crear notificación de auto-completar
     */
    private function createAutoCompleteNotification($profile)
    {
        // Aquí puedes implementar el sistema de notificaciones
        // Por ahora solo registramos en logs
        \Log::info("Notificación auto-complete creada", [
            'type' => 'profile_auto_complete',
            'table_id' => $this->id,
            'table_number' => $this->number,
            'profile_name' => $profile->name,
            'waiter_id' => $profile->user_id,
            'waiter_name' => $profile->user->name,
            'message' => "Mesa {$this->number} auto-activada (perfil: {$profile->name})"
        ]);
    }
} 