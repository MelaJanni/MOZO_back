/**
 * Utilidades de prueba para el sistema de notificaciones
 */

import firebaseService from '@/services/firebase'
import notificationsService from '@/services/notifications'
import { useAuthStore } from '@/stores/auth'

/**
 * Simula una notificación local para pruebas
 */
export const testLocalNotification = () => {
  console.log('🧪 Probando notificación local...')
  
  if ('Notification' in window) {
    if (Notification.permission === 'granted') {
      new Notification('Prueba Local', {
        body: 'Esta es una notificación de prueba local',
        icon: '/favicon.ico',
        tag: 'test-notification'
      })
      return { success: true, message: 'Notificación local enviada' }
    } else if (Notification.permission === 'default') {
      Notification.requestPermission().then(permission => {
        if (permission === 'granted') {
          new Notification('Prueba Local', {
            body: 'Esta es una notificación de prueba local',
            icon: '/favicon.ico',
            tag: 'test-notification'
          })
        }
      })
      return { success: true, message: 'Solicitando permisos...' }
    } else {
      return { success: false, message: 'Permisos de notificación denegados' }
    }
  } else {
    return { success: false, message: 'Notificaciones no soportadas en este navegador' }
  }
}

/**
 * Prueba el sistema completo de Firebase FCM
 */
export const testFirebaseNotification = async () => {
  console.log('🧪 Probando sistema Firebase FCM...')
  
  try {
    // Verificar inicialización de Firebase
    const firebase = await firebaseService.initializeFirebase()
    if (!firebase) {
      throw new Error('Firebase no se pudo inicializar')
    }

    // Obtener token FCM
    const token = await firebaseService.getFCMToken()
    if (!token) {
      throw new Error('No se pudo obtener token FCM')
    }

    console.log('🧪 Token FCM obtenido:', token.substring(0, 20) + '...')

    // Verificar que el usuario esté autenticado
    const authStore = useAuthStore()
    if (!authStore.isAuthenticated) {
      throw new Error('Usuario no autenticado')
    }

    // Guardar token en el backend
    await notificationsService.storeDeviceToken(token, 'web', authStore.user?.id)
    console.log('🧪 Token guardado en el backend')

    return {
      success: true,
      message: 'Sistema Firebase FCM configurado correctamente',
      token: token.substring(0, 20) + '...',
      user: authStore.user?.name || 'Usuario autenticado'
    }

  } catch (error) {
    console.error('🧪 Error en prueba Firebase:', error)
    return {
      success: false,
      message: error.message || 'Error desconocido',
      error: error
    }
  }
}

/**
 * Verifica el estado completo del sistema de notificaciones
 */
export const checkNotificationSystemStatus = () => {
  console.log('🧪 Verificando estado del sistema de notificaciones...')
  
  const status = {
    timestamp: new Date().toISOString(),
    browser: {
      userAgent: navigator.userAgent,
      platform: navigator.platform,
      online: navigator.onLine
    },
    notifications: {
      supported: 'Notification' in window,
      permission: 'Notification' in window ? Notification.permission : 'not-supported',
      serviceWorker: 'serviceWorker' in navigator
    },
    firebase: firebaseService.getFirebaseStatus(),
    localStorage: {
      token: !!localStorage.getItem('token'),
      user: !!localStorage.getItem('user'),
      fcmToken: !!localStorage.getItem('fcm_token')
    },
    environment: {
      dev: import.meta.env.DEV,
      mode: import.meta.env.MODE,
      firebaseConfigured: !!(import.meta.env.VITE_FIREBASE_API_KEY && import.meta.env.VITE_FIREBASE_PROJECT_ID)
    }
  }

  console.log('🧪 Estado del sistema:', status)
  return status
}

/**
 * Simula diferentes tipos de notificaciones que puede enviar el backend
 */
export const simulateBackendNotifications = () => {
  console.log('🧪 Simulando notificaciones del backend...')
  
  const notifications = [
    {
      type: 'TestNotification',
      title: 'Notificación de Prueba',
      body: 'Esta es una notificación de prueba enviada a todos los usuarios del negocio',
      data: {
        type: 'TestNotification',
        channels: ['Database', 'Broadcast', 'FCM'],
        icon: '🧪'
      }
    },
    {
      type: 'UserSpecificNotification',
      title: 'Notificación Personalizada',
      body: 'Esta es una notificación específica para tu usuario',
      data: {
        type: 'UserSpecificNotification',
        channels: ['Database', 'Broadcast', 'FCM'],
        icon: '👤'
      }
    },
    {
      type: 'OrderNotification',
      title: 'Nueva Orden',
      body: 'Tienes una nueva orden pendiente en la mesa #5',
      data: {
        type: 'OrderNotification',
        table_id: 5,
        order_id: 123,
        icon: '🍽️'
      }
    }
  ]

  notifications.forEach((notification, index) => {
    setTimeout(() => {
      if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(notification.title, {
          body: notification.body,
          icon: '/favicon.ico',
          tag: `test-${notification.type.toLowerCase()}`,
          data: notification.data
        })
      }
    }, index * 2000) // Escalonar notificaciones cada 2 segundos
  })

  return {
    success: true,
    message: `${notifications.length} notificaciones simuladas programadas`,
    notifications: notifications.length
  }
}

export default {
  testLocalNotification,
  testFirebaseNotification,
  checkNotificationSystemStatus,
  simulateBackendNotifications
}