// =====================================================
// CONFIGURACIÓN FRONTEND PARA FIRESTORE OPTIMIZADO
// =====================================================

// 1. Configuración de Firebase en el frontend
const firebaseConfig = {
  projectId: "mozoqr-7d32c",
  // ... resto de configuración
};

// 2. Listeners optimizados para QR pages
function setupOptimizedListeners(tableId) {
  const db = getFirestore();
  
  // 🚀 LISTENER PRINCIPAL - Solo para la mesa específica
  const tableCallsRef = collection(db, `tables/${tableId}/waiter_calls`);
  
  // Listener con filtros optimizados
  const q = query(
    tableCallsRef,
    where('status', 'in', ['pending', 'in_progress']),
    orderBy('timestamp', 'desc'),
    limit(5) // Solo las últimas 5 llamadas
  );
  
  return onSnapshot(q, (snapshot) => {
    snapshot.docChanges().forEach((change) => {
      const callData = change.doc.data();
      
      if (change.type === 'added') {
        showNewCallNotification(callData);
        updateUINewCall(callData);
      }
      
      if (change.type === 'modified') {
        updateCallStatus(callData);
      }
      
      if (change.type === 'removed') {
        removeCallFromUI(callData.id);
      }
    });
  }, (error) => {
    console.error("Error en listener optimizado:", error);
    // Fallback a polling cada 2 segundos
    setupPollingFallback(tableId);
  });
}

// 3. Polling de fallback ultra-rápido
function setupPollingFallback(tableId) {
  const pollInterval = setInterval(async () => {
    try {
      const response = await fetch(`/api/tables/${tableId}/calls/active`);
      const calls = await response.json();
      updateUIWithCalls(calls);
    } catch (error) {
      console.error("Polling fallback error:", error);
    }
  }, 2000); // 2 segundos
  
  return pollInterval;
}

// 4. Notificaciones instantáneas
function showNewCallNotification(callData) {
  // Sonido inmediato
  playNotificationSound();
  
  // Vibración en móviles
  if (navigator.vibrate) {
    navigator.vibrate([200, 100, 200]);
  }
  
  // Notificación visual
  showToast({
    title: `Mesa ${callData.table_number}`,
    message: callData.message,
    type: 'urgent',
    duration: 0 // No auto-hide para llamadas urgentes
  });
  
  // Actualizar badge/contador
  updateNotificationBadge();
}

// 5. Configuración de Service Worker para notificaciones
// sw.js
self.addEventListener('push', function(event) {
  if (event.data) {
    const data = event.data.json();
    
    const options = {
      body: `Mesa ${data.table_number}: ${data.message}`,
      icon: '/icons/mozo-icon-192.png',
      badge: '/icons/badge-72.png',
      vibrate: [200, 100, 200, 100, 200],
      tag: `waiter-call-${data.id}`,
      requireInteraction: true, // No se oculta automáticamente
      actions: [
        {
          action: 'accept',
          title: 'Atender',
          icon: '/icons/accept.png'
        },
        {
          action: 'dismiss',
          title: 'Marcar como vista',
          icon: '/icons/dismiss.png'
        }
      ],
      data: {
        callId: data.id,
        tableId: data.table_id,
        url: `/waiter/calls/${data.id}`
      }
    };
    
    event.waitUntil(
      self.registration.showNotification('Nueva llamada de mesa', options)
    );
  }
});

// 6. Manejo de acciones de notificación
self.addEventListener('notificationclick', function(event) {
  event.notification.close();
  
  if (event.action === 'accept') {
    // Abrir y aceptar llamada
    event.waitUntil(
      clients.openWindow(`/waiter/calls/${event.notification.data.callId}/accept`)
    );
  } else if (event.action === 'dismiss') {
    // Marcar como vista
    fetch(`/api/calls/${event.notification.data.callId}/mark-seen`, {
      method: 'POST'
    });
  } else {
    // Click en la notificación - abrir app
    event.waitUntil(
      clients.openWindow(event.notification.data.url)
    );
  }
});

// 7. Configuración para páginas QR
function initQRPage(tableId) {
  // Configurar listener optimizado
  const unsubscribe = setupOptimizedListeners(tableId);
  
  // Limpiar al salir de la página
  window.addEventListener('beforeunload', () => {
    unsubscribe();
  });
  
  // Heartbeat para mantener conexión activa
  setInterval(() => {
    fetch(`/api/tables/${tableId}/heartbeat`, { method: 'POST' });
  }, 30000); // Cada 30 segundos
}

// 8. Utilidades de performance
function measureFirestoreLatency() {
  const start = performance.now();
  
  return getDoc(doc(getFirestore(), 'latency_test', 'ping'))
    .then(() => {
      const latency = performance.now() - start;
      console.log(`Firestore latency: ${latency.toFixed(2)}ms`);
      return latency;
    });
}

// 9. Configuración de caché offline
function setupOfflineSupport() {
  enableNetwork(getFirestore()).catch(() => {
    console.warn("Firestore offline - activando modo local");
    showOfflineBanner();
  });
}

// EXPORT para usar en tu aplicación
export {
  setupOptimizedListeners,
  initQRPage,
  measureFirestoreLatency,
  setupOfflineSupport
};