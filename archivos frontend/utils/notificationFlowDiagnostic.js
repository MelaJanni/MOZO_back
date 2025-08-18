/**
 * Diagnóstico Completo del Flujo de Notificaciones
 * 
 * Esta herramienta analiza EXACTAMENTE qué canales están activos y por qué
 * las notificaciones llegan a la barra pero no en tiempo real a la web.
 */

/**
 * Diagnóstico completo del flujo de notificaciones
 */
export const diagnoseNotificationFlow = async () => {
  console.clear()
  console.log('%c🔍 === DIAGNÓSTICO COMPLETO DEL FLUJO DE NOTIFICACIONES ===', 
    'background: linear-gradient(45deg, #FF6B6B, #4ECDC4); color: white; padding: 15px; font-size: 18px; font-weight: bold; border-radius: 10px;')

  const diagnosis = {
    timestamp: new Date().toISOString(),
    channels: {
      websocket: { active: false, details: null },
      fcm: { active: false, details: null },
      pusher: { active: false, details: null }
    },
    flow: {
      backend_to_fcm: 'unknown',
      fcm_to_browser: 'unknown', 
      websocket_to_frontend: 'unknown'
    },
    issues: [],
    recommendations: []
  }

  try {
    // 1. DIAGNÓSTICO WEBSOCKET/PUSHER
    console.log('%c🔌 1. ANALIZANDO WEBSOCKET/PUSHER...', 'background: #2196F3; color: white; padding: 8px; font-weight: bold;')
    
    if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
      const pusher = window.Echo.connector.pusher
      const connectionState = pusher.connection.state
      const socketId = pusher.connection.socket_id
      const channels = Object.keys(pusher.channels.channels || {})
      
      diagnosis.channels.websocket = {
        active: connectionState === 'connected',
        state: connectionState,
        socketId: socketId,
        channels: channels,
        details: {
          pusher_key: pusher.key,
          cluster: pusher.config.cluster,
          auth_endpoint: pusher.config.authEndpoint
        }
      }

      console.log('✅ Echo/Pusher encontrado:')
      console.table({
        'Estado': connectionState,
        'Socket ID': socketId || 'N/A',
        'Canales Activos': channels.length,
        'Pusher Key': pusher.key?.substring(0, 10) + '...',
        'Cluster': pusher.config.cluster
      })

      if (channels.length > 0) {
        console.log('📋 Canales suscritos:', channels)
        
        // Verificar canal de usuario específico
        const user = localStorage.getItem('user')
        if (user) {
          const userData = JSON.parse(user)
          const expectedChannel = `private-App.Models.User.${userData.id}`
          const isSubscribed = channels.includes(expectedChannel)
          
          console.log(`👤 Canal esperado: ${expectedChannel}`)
          console.log(`📡 ¿Suscrito?: ${isSubscribed ? '✅ SÍ' : '❌ NO'}`)
          
          if (!isSubscribed) {
            diagnosis.issues.push(`No suscrito al canal privado del usuario: ${expectedChannel}`)
          }
        }
      } else {
        diagnosis.issues.push('No hay canales WebSocket activos')
      }

    } else {
      diagnosis.channels.websocket = { active: false, details: 'Echo no inicializado' }
      diagnosis.issues.push('Echo/Pusher no está inicializado')
      console.log('❌ Echo/Pusher NO encontrado')
    }

    // 2. DIAGNÓSTICO FCM
    console.log('%c🔥 2. ANALIZANDO FCM...', 'background: #FF9800; color: white; padding: 8px; font-weight: bold;')
    
    let fcmActive = false
    let fcmDetails = {}

    // Verificar si FCM está inicializado
    if (window.fcmMessaging || window.messaging) {
      fcmActive = true
      
      // Verificar token
      const fcmToken = localStorage.getItem('fcm_token')
      fcmDetails = {
        messaging_instance: !!window.fcmMessaging || !!window.messaging,
        token_stored: !!fcmToken,
        token_preview: fcmToken ? fcmToken.substring(0, 20) + '...' : 'N/A',
        vapid_key_configured: !!import.meta.env.VITE_FIREBASE_VAPID_KEY
      }

      console.log('✅ FCM encontrado:')
      console.table(fcmDetails)

      // Verificar si hay listener activo
      try {
        // Intentar ver si realmente hay un listener
        const hasActiveListener = window.fcmListenerActive || false
        console.log(`📻 Listener FCM activo: ${hasActiveListener ? '✅ SÍ' : '❌ NO'}`)
        
        if (!hasActiveListener) {
          diagnosis.issues.push('FCM inicializado pero listener no confirmado como activo')
        }
      } catch (error) {
        console.log('⚠️ No se pudo verificar estado del listener FCM')
      }

    } else {
      console.log('❌ FCM NO encontrado o no inicializado')
      diagnosis.issues.push('FCM no está inicializado')
    }

    diagnosis.channels.fcm = { active: fcmActive, details: fcmDetails }

    // 3. ANÁLISIS DEL FLUJO ACTUAL
    console.log('%c🔄 3. ANALIZANDO FLUJO DE NOTIFICACIONES...', 'background: #9C27B0; color: white; padding: 8px; font-weight: bold;')
    
    // Verificar qué sucede cuando se envía una notificación
    const user = localStorage.getItem('user')
    if (user) {
      const userData = JSON.parse(user)
      console.log(`👤 Usuario: ${userData.name} (ID: ${userData.id})`)
      
      // Verificar tokens de autenticación
      const authToken = localStorage.getItem('token')
      console.log(`🔑 Token de auth: ${authToken ? 'Presente' : 'Ausente'}`)
      
      if (authToken) {
        console.log(`🔑 Token preview: ${authToken.substring(0, 20)}...`)
      }
    }

    // 4. DIAGNÓSTICO DE CONFIGURACIÓN
    console.log('%c⚙️ 4. VERIFICANDO CONFIGURACIÓN...', 'background: #4CAF50; color: white; padding: 8px; font-weight: bold;')
    
    const config = {
      pusher_key: import.meta.env.VITE_PUSHER_APP_KEY,
      pusher_cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
      firebase_api_key: import.meta.env.VITE_FIREBASE_API_KEY,
      firebase_project_id: import.meta.env.VITE_FIREBASE_PROJECT_ID,
      firebase_vapid_key: import.meta.env.VITE_FIREBASE_VAPID_KEY
    }

    const configStatus = Object.entries(config).map(([key, value]) => ({
      variable: key,
      configured: !!value,
      preview: value ? (value.length > 20 ? value.substring(0, 20) + '...' : value) : 'No configurada'
    }))

    console.table(configStatus)

    // 5. DETERMINAR FLUJO ACTUAL
    console.log('%c📊 5. FLUJO ACTUAL DETECTADO:', 'background: #607D8B; color: white; padding: 8px; font-weight: bold;')
    
    if (diagnosis.channels.websocket.active && diagnosis.channels.fcm.active) {
      console.log('🔄 FLUJO DETECTADO: WebSocket + FCM (Dual)')
      console.log('📝 Esto significa:')
      console.log('   • Backend → Pusher WebSocket → Tiempo Real ✅')
      console.log('   • Backend → FCM → Notificación Push ✅')
      console.log('   • Problema probable: Conflicto entre ambos sistemas')
      
      diagnosis.flow.detected = 'dual_websocket_fcm'
      diagnosis.recommendations.push('Verificar si ambos sistemas están procesando la misma notificación')
      
    } else if (diagnosis.channels.websocket.active) {
      console.log('🔄 FLUJO DETECTADO: Solo WebSocket')
      console.log('📝 Esto significa:')
      console.log('   • Backend → Pusher WebSocket → Tiempo Real ✅')
      console.log('   • FCM no está activo ❌')
      
      diagnosis.flow.detected = 'websocket_only'
      
    } else if (diagnosis.channels.fcm.active) {
      console.log('🔄 FLUJO DETECTADO: Solo FCM')
      console.log('📝 Esto significa:')
      console.log('   • Backend → FCM → Notificación Push ✅')
      console.log('   • WebSocket no está activo ❌')
      console.log('   • Las notificaciones NO llegan en "tiempo real" a la web abierta')
      console.log('   • Solo llegan como push notifications cuando la pestaña no está activa')
      
      diagnosis.flow.detected = 'fcm_only'
      diagnosis.issues.push('WebSocket no activo - notificaciones solo por FCM push')
      diagnosis.recommendations.push('Activar WebSocket para notificaciones en tiempo real')
      
    } else {
      console.log('❌ FLUJO DETECTADO: Ningún sistema activo')
      diagnosis.flow.detected = 'none'
      diagnosis.issues.push('Ni WebSocket ni FCM están activos')
    }

    // 6. RECOMENDACIONES ESPECÍFICAS
    console.log('%c💡 6. RECOMENDACIONES:', 'background: #FFC107; color: black; padding: 8px; font-weight: bold;')
    
    if (diagnosis.issues.length > 0) {
      diagnosis.issues.forEach((issue, index) => {
        console.log(`❌ ${index + 1}. ${issue}`)
      })
    }

    if (diagnosis.recommendations.length > 0) {
      diagnosis.recommendations.forEach((rec, index) => {
        console.log(`💡 ${index + 1}. ${rec}`)
      })
    }

    // Agregar recomendaciones específicas
    if (diagnosis.flow.detected === 'fcm_only') {
      console.log('\n🎯 SOLUCIÓN PARA TU PROBLEMA:')
      console.log('Las notificaciones llegan a la barra porque FCM está funcionando,')
      console.log('pero NO llegan en tiempo real a la web porque WebSocket no está activo.')
      console.log('\nPara solucionarlo:')
      console.log('1. Verificar que Echo/Pusher esté conectado')
      console.log('2. Confirmar suscripción al canal privado del usuario')
      console.log('3. Verificar que el backend envíe a ambos canales: WebSocket + FCM')
    }

    console.log('%c━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━', 'color: #4ECDC4; font-weight: bold;')

    return diagnosis

  } catch (error) {
    console.error('❌ Error en diagnóstico:', error)
    diagnosis.issues.push(`Error en diagnóstico: ${error.message}`)
    return diagnosis
  }
}

/**
 * Test específico para verificar listeners
 */
export const testNotificationListeners = () => {
  console.log('%c🧪 TESTING NOTIFICATION LISTENERS...', 'background: #8BC34A; color: white; padding: 8px; font-weight: bold;')
  
  const results = {
    websocket_listener: false,
    fcm_listener: false,
    details: {}
  }

  // Test WebSocket listener
  if (window.Echo) {
    try {
      const user = localStorage.getItem('user')
      if (user) {
        const userData = JSON.parse(user)
        const channelName = `App.Models.User.${userData.id}`
        
        // Simular listener de prueba
        console.log(`🧪 Testing WebSocket listener for channel: ${channelName}`)
        
        // Intentar suscribirse temporalmente para probar
        const testChannel = window.Echo.private(channelName)
        if (testChannel) {
          results.websocket_listener = true
          results.details.websocket_channel = channelName
          console.log('✅ WebSocket listener test passed')
        }
      }
    } catch (error) {
      console.log('❌ WebSocket listener test failed:', error.message)
      results.details.websocket_error = error.message
    }
  }

  // Test FCM listener
  if (window.fcmMessaging || window.messaging) {
    try {
      console.log('🧪 Testing FCM listener...')
      
      // Verificar si el listener está configurado
      if (window.fcmListenerActive) {
        results.fcm_listener = true
        console.log('✅ FCM listener test passed')
      } else {
        console.log('⚠️ FCM instance exists but listener status unknown')
        results.fcm_listener = 'unknown'
      }
      
    } catch (error) {
      console.log('❌ FCM listener test failed:', error.message)
      results.details.fcm_error = error.message
    }
  }

  console.table(results)
  return results
}

/**
 * Simular notificación para probar canales
 */
export const simulateNotificationTest = () => {
  console.log('%c🎭 SIMULANDO NOTIFICACIÓN DE PRUEBA...', 'background: #E91E63; color: white; padding: 8px; font-weight: bold;')
  
  const testNotification = {
    id: `test-${Date.now()}`,
    title: '🧪 Notificación de Prueba',
    body: 'Esta es una simulación para probar los listeners',
    data: {
      type: 'TestNotification',
      timestamp: new Date().toISOString(),
      test_mode: true
    }
  }

  // Test WebSocket
  if (window.Echo) {
    try {
      console.log('🧪 Simulando llegada por WebSocket...')
      
      // Simular evento WebSocket
      window.dispatchEvent(new CustomEvent('websocket-notification-test', {
        detail: testNotification
      }))
      
      console.log('✅ Evento WebSocket simulado')
    } catch (error) {
      console.log('❌ Error simulando WebSocket:', error.message)
    }
  }

  // Test FCM (simular onMessage)
  if (window.fcmMessaging || window.messaging) {
    try {
      console.log('🧪 Simulando llegada por FCM...')
      
      // Simular evento FCM
      window.dispatchEvent(new CustomEvent('fcm-notification-test', {
        detail: {
          notification: testNotification,
          data: testNotification.data
        }
      }))
      
      console.log('✅ Evento FCM simulado')
    } catch (error) {
      console.log('❌ Error simulando FCM:', error.message)
    }
  }

  return testNotification
}

export default {
  diagnoseNotificationFlow,
  testNotificationListeners,
  simulateNotificationTest
}