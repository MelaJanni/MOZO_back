<?php

use Illuminate\Support\Facades\Broadcast;

// Canal personal de usuario (notificaciones privadas)
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal para mozos (recibir llamadas y actualizaciones)
Broadcast::channel('waiter.{waiterId}', function ($user, $waiterId) {
    return (int) $user->id === (int) $waiterId && $user->role === 'waiter';
});

// Canal para mesas (público - no requiere autenticación)
Broadcast::channel('table.{tableId}', function () {
    return true; // Público para clientes en mesas
});

// Canal para negocios (solo admins y staff)
Broadcast::channel('business.{businessId}', function ($user, $businessId) {
    return (int) $user->business_id === (int) $businessId && 
           in_array($user->role, ['admin', 'waiter']);
});
