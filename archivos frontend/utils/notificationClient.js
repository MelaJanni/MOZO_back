/*
 * notificationClient.js
 * Helper pequeño para integrar FCM en el flujo de login/logout.
 * - loginWithFCM(credentials): obtiene token FCM (si puede) y envía en el payload de login
 * - registerTokenAfterLogin(token, platform): registra token en backend (POST /device-token)
 * - logoutAndUnregister(): intenta borrar device token en backend antes de logout
 *
 * Uso:
 * import { loginWithFCM, logoutAndUnregister, registerTokenAfterLogin } from '@/utils/notificationClient'
 * await loginWithFCM({ email, password })
 */

import apiService from '@/services/api'
import notificationsService from '@/services/notifications'
import firebaseService from '@/services/firebase'
import { Capacitor } from '@capacitor/core'

const detectPlatform = () => {
  try {
    const p = Capacitor.getPlatform()
    // Normalize to web/android/ios
    if (p === 'android') return 'android'
    if (p === 'ios') return 'ios'
    return 'web'
  } catch (e) {
    return 'web'
  }
}

export async function getFCMTokenSafe() {
  try {
    const firebase = await firebaseService.initializeFirebase()
    if (!firebase) return null
    const token = await firebaseService.getFCMToken()
    return token
  } catch (error) {
    console.warn('notificationClient: no se pudo obtener FCM token:', error?.message || error)
    return null
  }
}

export async function loginWithFCM(credentials = {}, options = {}) {
  // options: { platformOverride }
  const platform = options.platformOverride || detectPlatform()

  // Try to get token before login (best UX: server receives token in same request)
  let fcmToken = null
  try {
    fcmToken = await getFCMTokenSafe()
  } catch (e) {
    // ignore
  }

  const payload = { ...credentials }
  if (fcmToken) {
    payload.fcm_token = fcmToken
    payload.platform = platform
  }

  // Use apiService.login to perform login (axios instance with interceptors)
  const response = await apiService.login(payload)

  // After login, axios interceptor usually stores the access token in localStorage.
  // If we didn't send the fcm_token, try to register it now using the stored auth.
  if (!fcmToken) {
    try {
      const token = await getFCMTokenSafe()
      if (token) {
        await registerTokenAfterLogin(token, platform)
      }
    } catch (e) {
      // noop
    }
  }

  return response
}

export async function registerTokenAfterLogin(token, platform = null) {
  try {
    const plat = platform || detectPlatform()
    // notificationsService.storeDeviceToken uses apiService under the hood and will include auth header
    await notificationsService.storeDeviceToken(token, plat)
    localStorage.setItem('fcm_token', token)
    return true
  } catch (error) {
    console.warn('notificationClient: error registrando token despues de login:', error?.message || error)
    return false
  }
}

export async function logoutAndUnregister() {
  try {
    const fcmToken = localStorage.getItem('fcm_token')
    if (fcmToken) {
      // apiService.delete expects payload in data for DELETE; notificationsService.deleteDeviceToken wraps that
      try {
        await notificationsService.deleteDeviceToken({ token: fcmToken })
      } catch (err) {
        // backend may need token id instead of token string; try fallback delete via apiService
        try {
          await apiService.delete('device-token', { data: { token: fcmToken } })
        } catch (e) {
          console.warn('notificationClient: fallback delete failed', e)
        }
      }
      localStorage.removeItem('fcm_token')
    }

    // Call backend logout endpoint
    try {
      await apiService.logout()
    } catch (e) {
      // ignore backend logout failure
    }

    // Clear local auth data
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    return true
  } catch (error) {
    console.warn('notificationClient: error en logoutAndUnregister', error)
    return false
  }
}

export default {
  getFCMTokenSafe,
  loginWithFCM,
  registerTokenAfterLogin,
  logoutAndUnregister
}
