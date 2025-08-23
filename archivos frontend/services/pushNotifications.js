/**
 * Clean Push Notifications Service
 * 
 * This service provides a unified interface for handling push notifications
 * across different platforms (web and mobile) with proper integration
 * to the backend notification system.
 */

import { Capacitor } from '@capacitor/core'
import { PushNotifications } from '@capacitor/push-notifications'
import { useAuthStore } from '@/stores/auth'
import notificationsService from './notifications'
import firebaseService from './firebase'
import { ref } from 'vue'

// Reactive status for FCM configuration
const fcmStatus = ref({ configured: false, tokenExists: false })

/**
 * Register device for push notifications
 * @returns {Object} Registration result with success status and token
 */
const register = async () => {
  console.log('🔔 Starting push notification registration...')
  const platform = Capacitor.getPlatform()

  // Web (Firebase FCM)
  if (platform === 'web') {
    try {
      console.log('🔔 Web platform detected, using Firebase FCM...')

      // Initialize Firebase
      const firebase = await firebaseService.initializeFirebase()
      if (!firebase) {
        return { success: false, error: 'Firebase initialization failed' }
      }

      // Get FCM token
      const token = await firebaseService.getFCMToken()
      console.log('🔔 FCM token obtained for web:', token.substring(0, 20) + '...')

      // Store token locally
      localStorage.setItem('fcm_token', token)

      // Send token to backend if user is authenticated
      const authStore = useAuthStore()
      if (authStore.isAuthenticated) {
        try {
          const response = await notificationsService.storeDeviceToken(token, 'web', authStore.user?.id)
          console.log('✅ Device token stored:', response)

          // Update reactive status
          fcmStatus.value = { configured: true, tokenExists: true }
        } catch (error) {
          console.error('❌ Error storing device token:', error)
        }
      } else {
        console.warn('⚠️ User not authenticated - token not sent to server')
      }

      return { success: true, token }
    } catch (error) {
      console.error('❌ FCM registration error:', error)
      return { success: false, error: error.message }
    }
  }

  // Mobile platforms (Capacitor native)
  try {
    if (!Capacitor.isPluginAvailable('PushNotifications')) {
      console.warn('⚠️ Plugin PushNotifications no disponible para registro (móvil)')
      return { success: false, error: 'PushNotifications plugin not available' }
    }

    // Request permissions first (Android/iOS may auto-grant or require explicit request)
    try {
      const perm = await PushNotifications.requestPermissions()
      console.log('🔔 Push permission result:', perm)
      if (perm && perm.receive === 'denied') {
        return { success: false, error: 'Push permissions denied' }
      }
    } catch (permErr) {
      console.warn('⚠️ Error requesting push permissions:', permErr)
      // continue to try register - some platforms don't require explicit permission
    }

    // Call register to trigger native registration and emit the 'registration' event
    await PushNotifications.register()

    // The actual token will be received in the 'registration' listener added in addListeners
    return { success: true, token: null }
  } catch (error) {
    console.error('❌ Mobile push registration error:', error)
    return { success: false, error: error.message }
  }
}

/**
 * Set up notification listeners for different platforms
 */
const addListeners = async () => {
  console.log('🔔 Setting up push notification listeners...')
  
  // For web platform, use Firebase listeners
  if (Capacitor.getPlatform() === 'web') {
    try {
      console.log('🔔 Setting up Firebase listeners for web...')
      
      // Set up foreground message listener with notification store integration
  await firebaseService.setupForegroundMessageListener((processedNotification) => {
        console.log('🚨 FCM NOTIFICATION RECEIVED IN REAL-TIME:', processedNotification)
        console.log('⏰ Received at:', new Date().toLocaleTimeString())
        
        // Integrate with notifications store for reactive updates
        import('@/stores/notifications').then(({ useNotificationsStore }) => {
          const notificationsStore = useNotificationsStore()
          
          // Create notification structure compatible with store
          // Prefer using a canonical id when available (e.g. callId) so we can dedupe
          const canonicalCallId = processedNotification.data?.callId || processedNotification.data?.call_id || null
          const storeNotification = {
            id: canonicalCallId || `fcm-${Date.now()}`,
            type: processedNotification.data?.type || (canonicalCallId ? 'waiter_call' : 'FCMNotification'),
            data: {
              title: processedNotification.title,
              message: processedNotification.body,
              ...processedNotification.data
            },
            created_at: new Date().toISOString(),
            read_at: null,
            source: 'fcm-realtime'
          }

          // If this FCM corresponds to an UltraFast call already being tracked by
          // the realtime listener, skip adding it to the notifications store to
          // avoid duplicates (Realtime UI already shows the call).
          try {
            if (canonicalCallId && window.ultraFastNotifications && window.ultraFastNotifications.activeCalls) {
              if (window.ultraFastNotifications.activeCalls.has(canonicalCallId)) {
                console.log('🔍 Skipping FCM store add for call already in UltraFast activeCalls:', canonicalCallId)
                return
              }
            }
          } catch (err) {
            console.warn('⚠️ Error checking ultraFastNotifications activeCalls for dedupe:', err)
          }
          
          console.log('📨 Adding notification to store:', storeNotification)
          
          // Add to store - this will trigger reactive watchers
          notificationsStore.addNewNotification(storeNotification)
          
          console.log('✅ Notification added to reactive store!')
        }).catch(error => {
          console.error('❌ Error importing notifications store:', error)
        })
      })
      
      console.log('✅ Firebase listeners configured for web')
      return
    } catch (error) {
      console.error('❌ Error setting up Firebase listeners:', error)
      return
    }
  }
  
  // Para móvil, usar Capacitor Push Notifications
  if (!Capacitor.isPluginAvailable('PushNotifications')) {
    console.log('⚠️ Plugin PushNotifications no disponible para listeners')
    return
  }

  try {
    // Listener para registro exitoso
    PushNotifications.addListener('registration', async (token) => {
      console.log('✅ Push registration success, token:', token.value)
      localStorage.setItem('fcm_token', token.value)

      // Try to send the token to backend. If authStore is not ready (race condition
      // with login), retry a few times with short delays.
      const maxRetries = 5
      let attempt = 0
      const delay = (ms) => new Promise(res => setTimeout(res, ms))

      while (attempt < maxRetries) {
        try {
          const authStore = useAuthStore()

          console.log('🔔 Intento de envío de token - intento', attempt + 1, 'authState:', {
            isAuthenticated: authStore.isAuthenticated,
            userId: authStore.user?.id
          })

          if (authStore.isAuthenticated && authStore.user?.id) {
            const platform = Capacitor.getPlatform()
            console.log('🔔 Enviando token al backend (móvil):', {
              token: token.value.substring(0, 20) + '...',
              platform: platform,
              userId: authStore.user?.id
            })

            const response = await notificationsService.storeDeviceToken(token.value, platform, authStore.user?.id)
            console.log('✅ Device token stored successfully (móvil):', response)
            break
          } else {
            // Not authenticated yet — wait and retry
            attempt++
            console.log('⚠️ Usuario no autenticado aún, reintentando en 500ms...')
            await delay(500)
            continue
          }
        } catch (error) {
          console.error('❌ Error intentando enviar token (móvil):', error)
          // If server returns an error, don't keep retrying forever
          attempt++
          await delay(500)
        }
      }

      if (attempt === maxRetries) {
        console.warn('⚠️ No se pudo enviar token al backend después de varios intentos. Se dejó guardado localmente.')
      }
    })

    // Listener para errores de registro
    PushNotifications.addListener('registrationError', (error) => {
      console.error('❌ Error on registration:', JSON.stringify(error))
    })

    // Listener para notificaciones recibidas en primer plano
    PushNotifications.addListener('pushNotificationReceived', (notification) => {
      const priority = notification.android?.priority || notification.data?.priority || 'unknown'
      const status = notification.data?.status || 'unknown'
      console.log(`🔔 Push received - Priority: ${priority}, Status: ${status}`)
      console.log('🔔 Full notification:', JSON.stringify(notification, null, 2))
      
      // Mostrar debug visual en Android
      showDebugToast(`📨 Push: ${priority}/${status}`)
      
      // Filtrar notificaciones que no deben mostrarse al mozo
      if (shouldFilterNotificationForWaiter(notification)) {
        console.log(`🚫 Notificación filtrada - Priority: ${priority}, Status: ${status}`)
        showDebugToast(`🚫 FILTRADA: ${priority}/${status}`)
        return
      }
      
      console.log(`✅ Notificación permitida - Priority: ${priority}, Status: ${status}`)
      showDebugToast(`✅ PERMITIDA: ${priority}/${status}`)
      
      // Procesar diferentes tipos de notificación del backend
      const processedNotification = processBackendNotification(notification)
      
      // Mostrar notificación local si la app está en primer plano
      if (Capacitor.isPluginAvailable('LocalNotifications')) {
        import('@capacitor/local-notifications').then(({ LocalNotifications }) => {
          LocalNotifications.schedule({
            notifications: [{
              title: processedNotification.title,
              body: processedNotification.body,
              id: new Date().getTime(),
              sound: null, // Usar sonido del sistema
              actionTypeId: 'OPEN_APP',
              extra: {
                ...processedNotification.data,
                receivedAt: new Date().toISOString(),
                source: 'push-foreground'
              }
            }]
          }).catch(error => {
            console.error('❌ Error programando notificación local:', error)
          })
        }).catch(error => {
          console.error('❌ Error importando LocalNotifications:', error)
        })
      } else {
        // Fallback para web
        if ('Notification' in window && Notification.permission === 'granted') {
          new Notification(processedNotification.title, {
            body: processedNotification.body,
            icon: '/favicon.ico',
            tag: 'push-notification',
            data: processedNotification.data
          })
        }
      }
    })

    // Listener para acciones en notificaciones
    PushNotifications.addListener('pushNotificationActionPerformed', (notification) => {
      console.log('🔔 Push action performed:', JSON.stringify(notification, null, 2))
      
      // Aquí puedes manejar navegación basada en el payload de la notificación
      const data = notification.notification.data
      if (data && data.route) {
        console.log('🔔 Navegando a:', data.route)
        // Implementar navegación aquí si es necesario
      }
    })

    console.log('✅ Listeners de notificaciones configurados correctamente')
    
  } catch (error) {
    console.error('❌ Error configurando listeners:', error)
  }
}

// Función para obtener el estado de las notificaciones
export const getNotificationStatus = async () => {
  if (!Capacitor.isPluginAvailable('PushNotifications')) {
    return { available: false, reason: 'Plugin no disponible' }
  }

  try {
    const permissions = await PushNotifications.checkPermissions()
    const token = localStorage.getItem('fcm_token')
    
    return {
      available: true,
      permissions: permissions.receive,
      hasToken: !!token,
      token: token ? `${token.substring(0, 20)}...` : null,
      platform: Capacitor.getPlatform()
    }
  } catch (error) {
    return { available: false, reason: error.message }
  }
}

// Función para verificar la configuración de Firebase
export const checkFirebaseConfig = () => {
  const requiredVars = ['VITE_FIREBASE_API_KEY', 'VITE_FIREBASE_PROJECT_ID']
  const missing = requiredVars.filter(key => !import.meta.env[key])
  
  return {
    configured: missing.length === 0,
    missing,
    config: {
      apiKey: import.meta.env.VITE_FIREBASE_API_KEY ? 'Configurada' : 'No configurada',
      projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID ? 'Configurada' : 'No configurada'
    }
  }
}

// Crear canal Android de notificaciones tan pronto como se importe el módulo
(async () => {
  try {
    if (Capacitor.isPluginAvailable && Capacitor.isPluginAvailable('LocalNotifications')) {
      const mod = await import('@capacitor/local-notifications')
      const LocalNotifications = mod.LocalNotifications
      try {
        if (LocalNotifications && LocalNotifications.createChannel) {
          await LocalNotifications.createChannel({
            id: 'mozo_waiter',
            name: 'Mozo - Llamadas',
            importance: 5,
            visibility: 1,
            description: 'Notificaciones de llamadas y eventos importantes para mozos'
          })
          console.log('✅ Canal mozo_waiter creado al importar pushNotifications')
        }
      } catch (err) {
        console.warn('⚠️ Error creando canal mozo_waiter al importar:', err)
      }
    }
  } catch (err) {
    // No bloquear import si falla
    console.warn('⚠️ No se pudo crear canal mozo_waiter al importar pushNotifications:', err)
  }
})()

export const initializePushNotifications = async () => {
  console.log('🔔 Inicializando sistema de notificaciones push...')
  
  const platform = Capacitor.getPlatform()
  console.log('🔔 Plataforma detectada:', platform)
  // Ensure listeners are attached before attempting to register. This prevents
  // missing the 'registration' event if the native layer returns the token immediately.
  await addListeners()
  const registerResult = await register()

  // Create Android notification channel (if available) so backend FCM notifications
  // and local notifications are shown with the proper importance on Android.
  if (Capacitor.isPluginAvailable && Capacitor.isPluginAvailable('LocalNotifications')) {
    import('@capacitor/local-notifications').then(({ LocalNotifications }) => {
      try {
        if (LocalNotifications && LocalNotifications.createChannel) {
          LocalNotifications.createChannel({
            id: 'mozo_waiter',
            name: 'Mozo - Llamadas',
            importance: 5, // max importance
            visibility: 1,
            description: 'Notificaciones de llamadas y eventos importantes para mozos'
          }).then(() => {
            console.log('✅ Canal de notificaciones Android creado: mozo_waiter')
          }).catch(err => {
            console.warn('⚠️ Error creando canal LocalNotifications:', err)
          })
        }
      } catch (err) {
        console.warn('⚠️ Excepción creando canal LocalNotifications:', err)
      }
    }).catch(err => {
      console.warn('⚠️ No se pudo importar LocalNotifications para crear canal:', err)
    })
  }
  
  const status = await getNotificationStatus()
  const firebaseConfig = checkFirebaseConfig()
  const firebaseStatus = firebaseService.getFirebaseStatus()
  
  console.log('🔔 Estado final de notificaciones:', {
    platform,
    registration: registerResult,
    status,
    firebase: firebaseConfig,
    firebaseStatus
  })
  
  return {
    platform,
    registration: registerResult,
    status,
    firebase: firebaseConfig,
    firebaseStatus
  }
}

// Función para procesar diferentes tipos de notificaciones del backend
// Función para mostrar debug visual en Android
const showDebugToast = (message) => {
  try {
    // Crear toast visual temporal
    const toast = document.createElement('div')
    toast.style.cssText = `
      position: fixed;
      top: 50px;
      left: 50%;
      transform: translateX(-50%);
      background: rgba(0,0,0,0.9);
      color: white;
      padding: 12px 20px;
      border-radius: 8px;
      z-index: 10000;
      font-size: 14px;
      font-weight: bold;
      box-shadow: 0 4px 12px rgba(0,0,0,0.5);
      max-width: 90%;
      text-align: center;
    `
    toast.textContent = message
    document.body.appendChild(toast)
    
    // Auto remover después de 3 segundos
    setTimeout(() => {
      if (toast.parentElement) {
        toast.remove()
      }
    }, 3000)
  } catch (error) {
    // Fallback silencioso - no hacer nada si falla
    console.warn('Toast debug failed:', error)
  }
}

// Función para filtrar notificaciones que no deben mostrarse al mozo
const shouldFilterNotificationForWaiter = (notification) => {
  try {
    // Verificar si hay datos en la notificación
    if (!notification.data) return false
    
    const notificationData = typeof notification.data === 'string' 
      ? JSON.parse(notification.data) 
      : notification.data
    
    // SOLO filtrar actualizaciones de estado (acknowledged, completed)
    // Estas son para actualizar UI del QR, NO para mostrar al mozo en Android
    if (notificationData.status === 'acknowledged' || notificationData.status === 'completed') {
      console.log('🚫 Filtrando actualización de estado:', notificationData.status)
      return true
    }
    
    // Filtrar notificaciones con prioridad NORMAL (usualmente son confirmaciones/actualizaciones)
    // Las notificaciones importantes para el mozo deben ser HIGH
    if (notification.android && notification.android.priority === 'normal') {
      console.log('🚫 Filtrando notificación de prioridad normal (posible confirmación)')
      return true
    }
    
    // También verificar en los datos de la notificación
    if (notificationData.priority === 'normal' && notificationData.type !== 'new_call') {
      console.log('🚫 Filtrando notificación normal que no es llamada nueva')
      return true
    }
    
    // Permitir SOLO las notificaciones HIGH priority o new_call
    return false
  } catch (error) {
    console.warn('⚠️ Error filtering notification:', error)
    return false
  }
}

const processBackendNotification = (notification) => {
  console.log('🔄 Procesando notificación del backend...')
  
  // Estructura base
  let processedNotification = {
    title: notification.title || 'Nueva notificación',
    body: notification.body || 'Tienes una nueva notificación',
    data: notification.data || {}
  }

  // Verificar si la notificación tiene datos estructurados del backend
  if (notification.data) {
    const notificationData = typeof notification.data === 'string' 
      ? JSON.parse(notification.data) 
      : notification.data

    console.log('🔄 Datos de notificación:', notificationData)

    // Procesar según el tipo de notificación del nuevo backend
    switch (notificationData.type) {
      case 'staff_request': {
        const event = notificationData.event_type || 'created'
        if (event === 'created') {
          processedNotification = {
            title: '🧑‍🍳 Nueva solicitud de personal',
            body: `${notificationData.title || notificationData.name || 'Candidato'} quiere unirse como ${notificationData.position || 'mozo'}`,
            data: {
              ...notificationData,
              icon: '🧑‍🍳',
              priority: 'high',
              route: '/admin/staff/requests',
              channels: ['Database', 'Realtime', 'FCM']
            }
          }
        } else if (event === 'confirmed') {
          processedNotification = {
            title: '✅ Solicitud aprobada',
            body: `Tu solicitud para ${notificationData.position || 'mozo'} fue aprobada`,
            data: {
              ...notificationData,
              icon: '✅',
              priority: 'high',
              route: '/staff/requests',
              channels: ['Database', 'Realtime', 'FCM']
            }
          }
        } else if (event === 'rejected') {
          processedNotification = {
            title: '❌ Solicitud rechazada',
            body: `Tu solicitud para ${notificationData.position || 'mozo'} fue rechazada`,
            data: {
              ...notificationData,
              icon: '❌',
              priority: 'normal',
              route: '/staff/requests',
              channels: ['Database', 'Realtime', 'FCM']
            }
          }
        }
        break
      }

      case 'staff_invitation': {
        processedNotification = {
          title: '📩 Invitación a un negocio',
          body: 'Has recibido una invitación para trabajar. Revisa y acepta desde la app.',
          data: {
            ...notificationData,
            icon: '📩',
            priority: 'high',
            route: '/staff/invitations',
            channels: ['Email', 'WhatsApp', 'FCM']
          }
        }
        break
      }
      case 'TestNotification':
        processedNotification = {
          title: 'Notificación de Prueba',
          body: 'Esta es una notificación de prueba enviada a todos los usuarios del negocio',
          data: {
            ...notificationData,
            icon: '🧪',
            priority: 'normal',
            channels: ['Database', 'Broadcast', 'FCM']
          }
        }
        break

      case 'UserSpecificNotification':
        processedNotification = {
          title: notificationData.title || 'Notificación Personalizada',
          body: notificationData.message || 'Tienes una nueva notificación personalizada',
          data: {
            ...notificationData,
            icon: '👤',
            priority: 'high',
            channels: ['Database', 'Broadcast', 'FCM']
          }
        }
        break

      case 'WebSocketTestNotification':
        processedNotification = {
          title: '⚡ Prueba WebSocket + FCM',
          body: 'Esta notificación llega por WebSocket (tiempo real) y FCM (push)',
          data: {
            ...notificationData,
            icon: '⚡',
            priority: 'high',
            test_mode: 'websocket_fcm'
          }
        }
        break

      case 'WebSocketOnlyNotification':
        processedNotification = {
          title: '🔌 Prueba Solo WebSocket',
          body: 'Esta notificación SOLO llega por WebSocket en tiempo real',
          data: {
            ...notificationData,
            icon: '🔌',
            priority: 'normal',
            test_mode: 'websocket_only'
          }
        }
        break

      default:
        // Para notificaciones que ya tienen title y body en el nivel superior
        if (notification.title && notification.body) {
          processedNotification = {
            title: notification.title,
            body: notification.body,
            data: {
              ...notificationData,
              icon: '📱',
              priority: 'normal'
            }
          }
        }
        break
    }
  }

  console.log('✅ Notificación procesada:', processedNotification)
  return processedNotification
}

export { fcmStatus }