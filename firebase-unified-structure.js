// 🔥 ESTRUCTURA UNIFICADA FIREBASE - MANTIENE TODAS LAS FUNCIONALIDADES
// ==================================================================

/**
 * ESTRUCTURA PROPUESTA - UNA SOLA FUENTE DE VERDAD
 */

const unifiedFirebaseStructure = {
  // 🎯 CORE: Una sola estructura para cada llamada
  "active_calls": {
    "{call_id}": {
      // Información básica
      "id": "189",
      "table_id": "1", 
      "table_number": 1,
      "waiter_id": "2",
      "waiter_name": "Waiter 1 McDonalds",
      
      // Estados y mensajes
      "status": "acknowledged", // pending | acknowledged | completed
      "message": "Cliente solicita atención",
      "urgency": "normal", // low | normal | high
      
      // Timestamps
      "called_at": 1755305586000,
      "acknowledged_at": 1755305586000,
      "completed_at": null,
      
      // Metadatos útiles
      "response_time_seconds": 45,
      "source": "qr_page", // qr_page | manual | app
      "business_id": "1",
      
      // Información de mesa
      "table": {
        "id": "1",
        "number": 1,
        "name": "Mesa Principal",
        "notifications_enabled": true
      },
      
      // Estado del mozo
      "waiter": {
        "id": "2", 
        "name": "Waiter 1 McDonalds",
        "is_online": true,
        "last_seen": 1755305586000
      }
    }
  },
  
  // 🎯 ÍNDICES RÁPIDOS - Para queries eficientes
  "waiters": {
    "{waiter_id}": {
      "active_calls": ["{call_id1}", "{call_id2}"], // Solo IDs, data en active_calls
      "stats": {
        "pending_count": 2,
        "total_today": 15,
        "avg_response_time": 65
      }
    }
  },
  
  "tables": {
    "{table_id}": {
      "current_call": "{call_id}", // null si no hay llamada activa
      "last_call": "{call_id}",
      "stats": {
        "calls_today": 3,
        "last_call_at": 1755305586000
      }
    }
  },
  
  // 🎯 ÍNDICES POR BUSINESS - Para administradores
  "businesses": {
    "{business_id}": {
      "active_calls": ["{call_id1}", "{call_id2}"],
      "stats": {
        "total_pending": 5,
        "total_today": 47
      }
    }
  }
};

/**
 * VENTAJAS DE LA ESTRUCTURA UNIFICADA:
 * 
 * 1. ✅ UNA SOLA FUENTE DE VERDAD
 *    - No más inconsistencias entre estructuras
 *    - Un solo lugar para updates
 * 
 * 2. ✅ MANTIENE TODAS LAS FUNCIONALIDADES
 *    - Clientes: Escuchan /active_calls/{call_id}
 *    - Mozos: Escuchan /waiters/{waiter_id}/active_calls + /active_calls/*
 *    - Admins: Escuchan /businesses/{business_id}/active_calls + /active_calls/*
 * 
 * 3. ✅ QUERIES MÁS EFICIENTES
 *    - Índices rápidos por waiter/table/business
 *    - Datos completos solo en active_calls
 * 
 * 4. ✅ ESCALABILIDAD
 *    - Fácil agregar nuevos campos
 *    - Estructura clara y mantenible
 * 
 * 5. ✅ ESTADÍSTICAS EN TIEMPO REAL
 *    - Contadores automáticos
 *    - Métricas de rendimiento
 */

/**
 * LISTENERS DEL FRONTEND - MISMA FUNCIONALIDAD
 */

// 👥 CLIENTE - Mesa escucha su llamada actual
// firebase.database().ref(`tables/${tableId}/current_call`).on('value', (snapshot) => {
//   const callId = snapshot.val();
//   if (callId) {
//     // Escuchar detalles de la llamada
//     firebase.database().ref(`active_calls/${callId}`).on('value', (callSnapshot) => {
//       const callData = callSnapshot.val();
//       updateTableStatus(callData);
//     });
//   }
// });

// 👨‍🍳 MOZO - Escucha todas sus llamadas activas
// firebase.database().ref(`waiters/${waiterId}/active_calls`).on('value', (snapshot) => {
//   const callIds = snapshot.val() || [];
//   
//   // Escuchar cada llamada individual
//   callIds.forEach(callId => {
//     firebase.database().ref(`active_calls/${callId}`).on('value', (callSnapshot) => {
//       const callData = callSnapshot.val();
//       updateWaiterDashboard(callData);
//     });
//   });
// });

// 👔 ADMIN - Vista completa del negocio
// firebase.database().ref(`businesses/${businessId}/active_calls`).on('value', (snapshot) => {
//   const callIds = snapshot.val() || [];
//   // Similar al mozo pero con todas las llamadas del negocio
// });

export { unifiedFirebaseStructure };