import firebaseService from '@/services/firebase'
import notificationsService from '@/services/notifications'

/**
 * Diagnostic helper para notificaciones.
 * - Verifica registro de Service Worker
 * - Verifica permisos de notificaciones
 * - Intenta obtener token FCM y guarda en localStorage
 * - Si hay token de auth, intenta enviar token al backend para comprobar almacenamiento
 */
export async function runNotificationDiagnostics() {
  console.group('[NotificationDiagnostics] Iniciando diagnóstico de notificaciones')
  try {
    // Service Worker
    if ('serviceWorker' in navigator) {
      try {
        const reg = await navigator.serviceWorker.getRegistration('/firebase-messaging-sw.js')
        if (reg) {
          console.log('[NotificationDiagnostics] Service Worker registrado. Scope:', reg.scope)
          console.log('[NotificationDiagnostics] Service Worker active state:', reg.active ? reg.active.state : 'no active')
        } else {
          console.warn('[NotificationDiagnostics] Service Worker /firebase-messaging-sw.js NO registrado')
        }
      } catch (err) {
        console.warn('[NotificationDiagnostics] Error comprobando Service Worker:', err)
      }
    } else {
      console.warn('[NotificationDiagnostics] Service Workers no soportados en este navegador')
    }

    // Permisos
    const permission = (typeof Notification !== 'undefined') ? Notification.permission : 'unsupported'
    console.log('[NotificationDiagnostics] Permission:', permission)

    // Token en localStorage
    const existingToken = localStorage.getItem('fcm_token')
    console.log('[NotificationDiagnostics] fcm_token en localStorage:', existingToken ? `${existingToken.substring(0,20)}...` : 'no encontrado')

    // Intentar obtener token FCM si no está o si permisos son default
    if (permission === 'denied') {
      console.warn('[NotificationDiagnostics] Permisos DENEGADOS - no se intentará obtener token')
    } else {
      try {
        if (permission === 'default') {
          console.log('[NotificationDiagnostics] Solicitando permiso de notificaciones...')
          const p = await Notification.requestPermission()
          console.log('[NotificationDiagnostics] Resultado requestPermission():', p)
          if (p !== 'granted') {
            console.warn('[NotificationDiagnostics] Permiso no concedido; abortando obtención de token')
            console.groupEnd()
            return
          }
        }

        console.log('[NotificationDiagnostics] Inicializando Firebase y solicitando token...')
        const firebaseInit = await firebaseService.initializeFirebase()
        if (!firebaseInit) {
          console.warn('[NotificationDiagnostics] No se pudo inicializar Firebase (revisar variables env y SW)')
        } else {
          try {
            const token = await firebaseService.getFCMToken()
            console.log('[NotificationDiagnostics] FCM token obtenido:', token ? `${token.substring(0,20)}...` : 'no token')
            if (token) localStorage.setItem('fcm_token', token)

            // Si hay token de auth en localStorage, intentar enviar al backend para probar
            const authToken = localStorage.getItem('token')
            if (authToken) {
              try {
                console.log('[NotificationDiagnostics] Intentando enviar token al backend (prueba) con platform=web...')
                await notificationsService.storeDeviceToken(token, 'web')
                console.log('[NotificationDiagnostics] Intento de registro en backend: OK (verificar en DB)')
              } catch (sendErr) {
                console.warn('[NotificationDiagnostics] Error enviando token al backend:', sendErr?.message || sendErr)
              }
            } else {
              console.log('[NotificationDiagnostics] No hay auth token en localStorage; no se enviará token al backend')
            }
          } catch (tokenErr) {
            console.warn('[NotificationDiagnostics] Error obteniendo FCM token:', tokenErr?.message || tokenErr)
          }
        }
      } catch (err) {
        console.warn('[NotificationDiagnostics] Error durante diagnóstico FCM:', err)
      }
    }
  } finally {
    console.groupEnd()
  }
}

export default { runNotificationDiagnostics }
