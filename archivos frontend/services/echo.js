import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import apiService from './api'
import authService from './auth'

let echoInstance = null

export const initializeEcho = () => {
  if (echoInstance) {
    echoInstance.disconnect()
  }

  window.Pusher = Pusher

  echoInstance = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY || 'your-pusher-key',
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
    forceTLS: true,
    authEndpoint: `https://mozoqr.com/api/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${authService.getToken()}`,
        Accept: 'application/json',
      },
    },
  })

  return echoInstance
}

export const getEcho = () => {
  if (!echoInstance) {
    throw new Error('Echo no ha sido inicializado. Llama a initializeEcho() primero.')
  }
  return echoInstance
}

export const disconnectEcho = () => {
  if (echoInstance) {
    echoInstance.disconnect()
    echoInstance = null
  }
} 