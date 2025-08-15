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

self.addEventListener('notificationclick', function(event) {
  event.notification.close();
  event.waitUntil(clients.openWindow('/'));
});
