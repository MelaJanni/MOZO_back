<?php

namespace App\Models;

/**
 * FcmToken - Alias de DeviceToken para compatibilidad con tests
 * 
 * Este modelo es un alias para mantener compatibilidad con código legacy
 * que usa "FcmToken" en lugar de "DeviceToken".
 * 
 * @deprecated Usar DeviceToken en su lugar
 */
class FcmToken extends DeviceToken
{
    /**
     * Nombre de tabla para tests legacy
     * Los tests esperan 'fcm_tokens' pero la tabla real es 'device_tokens'
     */
    protected $table = 'device_tokens';
    
    // Hereda todo de DeviceToken
    // Solo existe para compatibilidad con tests antiguos
}
