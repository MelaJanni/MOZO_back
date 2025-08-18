/**
 * Utilidades de diagnóstico para el sistema de notificaciones
 */

export const runSystemDiagnostics = () => {
  console.log('🔍 === DIAGNÓSTICO DEL SISTEMA DE NOTIFICACIONES ===')
  
  // Verificar variables de entorno
  console.log('🔍 Variables de entorno:')
  console.log('  VITE_PUSHER_APP_KEY:', import.meta.env.VITE_PUSHER_APP_KEY ? '✅ Definida' : '❌ No definida')
  console.log('  VITE_PUSHER_APP_CLUSTER:', import.meta.env.VITE_PUSHER_APP_CLUSTER ? '✅ Definida' : '❌ No definida')
  console.log('  VITE_FIREBASE_API_KEY:', import.meta.env.VITE_FIREBASE_API_KEY ? '✅ Definida' : '❌ No definida')
  console.log('  NODE_ENV:', import.meta.env.NODE_ENV)
  console.log('  DEV:', import.meta.env.DEV)
  
  // Verificar localStorage
  console.log('🔍 localStorage:')
  console.log('  token:', localStorage.getItem('token') ? '✅ Presente' : '❌ Ausente')
  console.log('  user:', localStorage.getItem('user') ? '✅ Presente' : '❌ Ausente')
  console.log('  fcm_token:', localStorage.getItem('fcm_token') ? '✅ Presente' : '❌ Ausente')
  
  // Verificar dependencias usando import dinámico
  console.log('🔍 Dependencias:')
  
  // Verificar pusher-js
  import('pusher-js').then(() => {
    console.log('  pusher-js:', '✅ Disponible')
  }).catch(() => {
    console.log('  pusher-js:', '❌ No disponible')
  })
  
  // Verificar laravel-echo
  import('laravel-echo').then(() => {
    console.log('  laravel-echo:', '✅ Disponible')
  }).catch(() => {
    console.log('  laravel-echo:', '❌ No disponible')
  })
  
  // Verificar conectividad de red
  console.log('🔍 Conectividad:')
  console.log('  online:', navigator.onLine ? '✅ Conectado' : '❌ Desconectado')
  
  // Verificar permisos de notificaciones
  if ('Notification' in window) {
    console.log('  Notifications API:', '✅ Disponible')
    console.log('  Permission:', Notification.permission)
  } else {
    console.log('  Notifications API:', '❌ No disponible')
  }
  
  // Verificar Service Workers
  if ('serviceWorker' in navigator) {
    console.log('  Service Workers:', '✅ Disponible')
  } else {
    console.log('  Service Workers:', '❌ No disponible')
  }
  
  console.log('🔍 === FIN DEL DIAGNÓSTICO ===')
}

export const checkPusherConnection = async () => {
  console.log('🔍 === PRUEBA DE CONEXIÓN PUSHER ===')
  
  try {
    const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY
    const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER
    
    if (!pusherKey || !pusherCluster) {
      throw new Error('Variables de entorno de Pusher no configuradas')
    }
    
    console.log('🔍 Configuración Pusher:')
    console.log('  Key:', pusherKey.substring(0, 8) + '...')
    console.log('  Cluster:', pusherCluster)
    
    // Crear una conexión de prueba usando import dinámico
    const { default: Pusher } = await import('pusher-js')
    const pusher = new Pusher(pusherKey, {
      cluster: pusherCluster,
      forceTLS: true
    })
    
    return new Promise((resolve, reject) => {
      const timeout = setTimeout(() => {
        reject(new Error('Timeout en la conexión Pusher'))
      }, 5000)
      
      pusher.connection.bind('connected', () => {
        clearTimeout(timeout)
        console.log('🔍 ✅ Conexión Pusher exitosa')
        pusher.disconnect()
        resolve({ success: true, message: 'Conexión Pusher exitosa' })
      })
      
      pusher.connection.bind('error', (error) => {
        clearTimeout(timeout)
        console.log('🔍 ❌ Error en conexión Pusher:', error)
        reject(new Error(`Error de conexión Pusher: ${error.message}`))
      })
    })
  } catch (error) {
    console.error('🔍 ❌ Error en checkPusherConnection:', error)
    throw error
  }
}

export const checkFirebaseConfig = () => {
  console.log('🔍 === VERIFICACIÓN DE CONFIGURACIÓN FIREBASE ===')
  
  const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID
  }
  
  console.log('🔍 Configuración Firebase:')
  Object.entries(firebaseConfig).forEach(([key, value]) => {
    console.log(`  ${key}:`, value ? '✅ Definida' : '❌ No definida')
  })
  
  const missingKeys = Object.entries(firebaseConfig)
    .filter(([_, value]) => !value)
    .map(([key, _]) => key)
  
  if (missingKeys.length > 0) {
    console.log('🔍 ❌ Claves faltantes:', missingKeys)
    return false
  } else {
    console.log('🔍 ✅ Todas las claves de Firebase están configuradas')
    return true
  }
}

export const generateDiagnosticReport = () => {
  const report = {
    timestamp: new Date().toISOString(),
    environment: {
      nodeEnv: import.meta.env.NODE_ENV,
      dev: import.meta.env.DEV,
      online: navigator.onLine
    },
    variables: {
      pusherKey: !!import.meta.env.VITE_PUSHER_APP_KEY,
      pusherCluster: !!import.meta.env.VITE_PUSHER_APP_CLUSTER,
      firebaseApiKey: !!import.meta.env.VITE_FIREBASE_API_KEY
    },
    localStorage: {
      token: !!localStorage.getItem('token'),
      user: !!localStorage.getItem('user'),
      fcmToken: !!localStorage.getItem('fcm_token')
    },
    capabilities: {
      notifications: 'Notification' in window,
      serviceWorker: 'serviceWorker' in navigator,
      pushManager: 'PushManager' in window
    }
  }
  
  console.log('🔍 === REPORTE DE DIAGNÓSTICO ===')
  console.log(JSON.stringify(report, null, 2))
  console.log('🔍 === FIN DEL REPORTE ===')
  
  return report
} 