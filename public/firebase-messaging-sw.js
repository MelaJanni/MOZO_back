importScripts('https://www.gstatic.com/firebasejs/9.6.10/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.6.10/firebase-messaging-compat.js');

// Configura con tus credenciales de Firebase (rellenar en deploy)
firebase.initializeApp({
  apiKey: 'TU_API_KEY',
  authDomain: 'TU_AUTH_DOMAIN',
  projectId: 'TU_PROJECT_ID',
  messagingSenderId: 'TU_SENDER_ID',
  appId: 'TU_APP_ID'
});

const messaging = firebase.messaging();

// Manejar mensajes en background
messaging.onBackgroundMessage(function(payload) {
  console.log('[firebase-messaging-sw.js] Background message ', payload);
  const notificationTitle = payload.notification?.title || 'Notificación';
  const notificationOptions = {
    body: payload.notification?.body || '',
    icon: payload.notification?.icon || '/logo192.png',
    data: payload.data || {}
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

    const title = notification.title || 'Notificación';
    const options = {
      body: notification.body || '',
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
