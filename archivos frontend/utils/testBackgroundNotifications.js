/**
 * Utilidad para probar notificaciones en background (app cerrada)
 */

import apiService from '@/services/api'

/**
 * Envía una notificación de prueba que debe llegar cuando la app está cerrada
 */
export const sendTestBackgroundNotification = async () => {
  try {
    console.log('🧪 Enviando notificación de prueba para app cerrada...')
    
    const response = await apiService.post('/notifications/test-background', {
      title: '🔔 Prueba App Cerrada',
      message: 'Esta notificación debe aparecer cuando la app está cerrada',
      data: {
        type: 'background_test',
        timestamp: new Date().toISOString(),
        test_mode: true
      }
    })
    
    console.log('✅ Notificación de prueba enviada:', response.data)
    return response.data
    
  } catch (error) {
    console.error('❌ Error enviando notificación de prueba:', error)
    throw error
  }
}

/**
 * Programa una notificación de prueba con delay
 */
export const scheduleTestNotification = async (delaySeconds = 10) => {
  try {
    console.log(`🕐 Programando notificación de prueba en ${delaySeconds} segundos...`)
    
    const response = await apiService.post('/notifications/schedule-test', {
      delay: delaySeconds,
      title: '⏰ Notificación Programada',
      message: `Esta notificación fue programada hace ${delaySeconds} segundos`,
      data: {
        type: 'scheduled_test',
        scheduled_at: new Date().toISOString(),
        delay_seconds: delaySeconds
      }
    })
    
    console.log('✅ Notificación programada:', response.data)
    return response.data
    
  } catch (error) {
    console.error('❌ Error programando notificación:', error)
    throw error
  }
}

/**
 * Obtiene el token FCM actual para diagnóstico
 */
export const getCurrentFCMToken = () => {
  const token = localStorage.getItem('fcm_token')
  if (token) {
    console.log('🔑 Token FCM actual:', token.substring(0, 20) + '...')
    return token
  } else {
    console.warn('⚠️ No hay token FCM guardado')
    return null
  }
}

/**
 * Instrucciones para probar notificaciones con app cerrada
 */
export const getTestInstructions = () => {
  return {
    steps: [
      '1. Asegúrate de estar autenticado en la app',
      '2. Ejecuta sendTestBackgroundNotification() o scheduleTestNotification()',
      '3. Cierra completamente la aplicación (no solo minimizar)',
      '4. Espera unos segundos',
      '5. Deberías ver la notificación en la barra de notificaciones',
      '6. Al tocar la notificación, la app debe abrirse'
    ],
    note: 'Para Android, asegúrate de que los permisos de notificaciones estén activados y que la app no esté en modo ahorro de batería.'
  }
}