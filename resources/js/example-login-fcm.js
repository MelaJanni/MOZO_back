// Ejemplo: login que obtiene token FCM y lo envía en el payload de login
// Requiere: Firebase inicializado en src/firebase/config.js y axios disponible

import axios from 'axios';
import { getToken } from 'firebase/messaging';
import { messaging } from '@/firebase/config.js';

export async function loginWithFcm(email, password) {
  let fcmToken = null;
  try {
    fcmToken = await getToken(messaging, { vapidKey: 'TU_VAPID_KEY' });
    console.log('FCM token obtenido antes del login:', fcmToken);
  } catch (err) {
    console.warn('No se pudo obtener token FCM antes del login', err);
  }

  const payload = { email, password };
  if (fcmToken) {
    payload.fcm_token = fcmToken;
    payload.platform = 'web';
  }

  const res = await axios.post('/api/login', payload);
  // Aquí guarda res.data.access_token en tu store/localStorage según corresponda
  return res.data;
}
