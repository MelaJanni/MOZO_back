importScripts('https://www.gstatic.com/firebasejs/9.6.10/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.6.10/firebase-messaging-compat.js');

// Configura con tus credenciales de Firebase (rellenar en deploy)
firebase.initializeApp({
  apiKey: 'AIzaSyCecfSTfyxk3D2X4XsLaSGkckvf-OvhFZA',
  authDomain: 'mozoqr.com',
  projectId: 'mozoqr-7d32c',
  messagingSenderId: '175482362472',
  appId: '1:175482362472:android:535ff3b2282ad3b8b6b9dd'
});

const messaging = firebase.messaging();

// Manejar mensajes en background
messaging.onBackgroundMessage(function(payload) {
  console.log('[firebase-messaging-sw.js] Background message ', payload);
  
  const data = payload.data || {};
  const notification = payload.notification || {};
  
  // Detectar notificaciones UNIFIED
  if (data.type === 'unified' || data.source === 'unified') {
    const notificationTitle = data.title || notification.title || 'Nueva llamada UNIFIED';
    const notificationOptions = {
      body: data.message || notification.body || `Mesa ${data.table_number || 'N/A'} solicita mozo`,
      icon: '/logo192.png',
      badge: '/badge-72x72.png',
      tag: 'unified-notification',
      requireInteraction: true,
      data: data
    };
    
    console.log('[firebase-messaging-sw.js] Showing UNIFIED notification', notificationOptions);
    return self.registration.showNotification(notificationTitle, notificationOptions);
  }
  
  // Manejo de otras notificaciones
  const notificationTitle = notification.title || data.title || 'Notificación';
  const notificationOptions = {
    body: notification.body || data.message || '',
    icon: notification.icon || '/logo192.png',
    data: data
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});

// Fallback: manejar push events directos (webpush) que no pasen por onBackgroundMessage
self.addEventListener('push', function(event) {
  try {
    const payload = event.data ? event.data.json() : {};
    console.log('[firebase-messaging-sw.js] Push event received', payload);

    // payload puede venir estructurado como {notification: {title, body, icon}, data: {...}} o como message
    const notification = payload.notification || (payload.message && payload.message.notification) || {};
    const data = payload.data || (payload.message && payload.message.data) || {};

    // Detectar notificaciones UNIFIED en push events
    if (data.type === 'unified' || data.source === 'unified') {
      const title = data.title || notification.title || 'Nueva llamada UNIFIED';
      const options = {
        body: data.message || notification.body || `Mesa ${data.table_number || 'N/A'} solicita mozo`,
        icon: '/logo192.png',
        badge: '/badge-72x72.png',
        tag: 'unified-notification',
        requireInteraction: true,
        data: data
      };
      
      return event.waitUntil(self.registration.showNotification(title, options));
    }

    const title = notification.title || data.title || 'Notificación';
    const options = {
      body: notification.body || data.message || '',
      icon: notification.icon || '/logo192.png',
      badge: notification.badge || '/badge-72x72.png',
      data: data
    };

    event.waitUntil(self.registration.showNotification(title, options));
  } catch (err) {
    console.error('[firebase-messaging-sw.js] Error parsing push event', err);
  }
});

self.addEventListener('notificationclick', function(event) {
  event.notification.close();
  event.waitUntil(clients.openWindow('/'));
});
