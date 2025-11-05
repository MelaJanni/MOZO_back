<?php

namespace App\Models;

/**
 * StaffRequest - Alias de Staff para compatibilidad con tests
 * 
 * Este modelo es un alias para mantener compatibilidad con código legacy
 * que usa "StaffRequest" en lugar de "Staff".
 * 
 * El modelo Staff maneja tanto solicitudes (status=pending) 
 * como staff confirmado (status=confirmed).
 * 
 * @deprecated Usar Staff en su lugar
 */
class StaffRequest extends Staff
{
    // Hereda todo de Staff
    // Solo existe para compatibilidad con tests antiguos
    
    /**
     * Scope: solo solicitudes pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
