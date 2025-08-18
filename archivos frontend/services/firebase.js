/**
 * Firebase Service - Unified Configuration
 * 
 * Handles both FCM (Push Notifications) and Firestore (Real-time data)
 * This is the single source of truth for Firebase configuration
 */

// Firebase configuration from environment variables
const firebaseConfig = {
  apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
  authDomain: `${import.meta.env.VITE_FIREBASE_PROJECT_ID}.firebaseapp.com`,
  projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
  storageBucket: `${import.meta.env.VITE_FIREBASE_PROJECT_ID}.appspot.com`,
  messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
  appId: import.meta.env.VITE_FIREBASE_APP_ID
}

// Global Firebase instances
let app = null
let messaging = null
let db = null

/**
 * Initialize Firebase and Messaging service
 * @returns {Object|null} Firebase app and messaging instances or null if failed
 */
export const initializeFirebase = async () => {
  try {
    // Browser environment check
    if (typeof window === 'undefined') {
      console.log('🔥 Firebase: Not supported in non-browser environment')
      return null
    }

    // Configuration validation
    const missingKeys = Object.entries(firebaseConfig)
      .filter(([_, value]) => !value)
      .map(([key, _]) => key)

    if (missingKeys.length > 0) {
      console.error('🔥 Firebase: Incomplete configuration. Missing keys:', missingKeys)
      return null
    }

    console.log('🔥 Initializing Firebase...')

    // Register Service Worker for background notifications
    if ('serviceWorker' in navigator) {
      try {
        const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js')
        console.log('🔥 Service Worker registered successfully')
      } catch (error) {
        console.error('🔥 Service Worker registration failed:', error)
      }
    }

    // Dynamic imports for better performance
    const { initializeApp } = await import('firebase/app')
    const { getMessaging, isSupported } = await import('firebase/messaging')
    const { getFirestore } = await import('firebase/firestore')

    // Check if Firebase Messaging is supported
    const supported = await isSupported()
    if (!supported) {
      console.log('🔥 Firebase Messaging not supported in this browser')
      return null
    }

    // Initialize Firebase App
    app = initializeApp(firebaseConfig)
    console.log('🔥 Firebase App initialized')

    // Initialize Firebase Messaging
    messaging = getMessaging(app)
    console.log('🔥 Firebase Messaging initialized')

    // Initialize Firestore
    db = getFirestore(app)
    console.log('🔥 Firestore initialized')

    return { app, messaging, db }
  } catch (error) {
    console.error('🔥 Firebase initialization error:', error)
    return null
  }
}

/**
 * Get FCM token for the current device
 * @returns {string} FCM token
 * @throws {Error} If token cannot be obtained
 */
export const getFCMToken = async () => {
  try {
    // Ensure Firebase is initialized
    if (!messaging) {
      const firebase = await initializeFirebase()
      if (!firebase) {
        throw new Error('Firebase initialization failed')
      }
    }

    const { getToken } = await import('firebase/messaging')
    
    // Guardar para entornos donde Notification no existe (WebView Android / SSR)
    if (typeof Notification === 'undefined') {
      console.warn('🔥 Notification API no disponible en este entorno (probablemente WebView). Saltando solicitud de permisos.')
    } else {
      // Check notification permissions
      let permission = Notification.permission
      console.log('🔥 Current notification permission:', permission)
      
      if (permission === 'default') {
        console.log('🔥 Requesting notification permissions...')
        permission = await Notification.requestPermission()
        console.log('🔥 Permission after request:', permission)
      }
      
      if (permission === 'denied') {
        console.error('🔥 Notification permissions DENIED by user')
        console.error('🔥 SOLUTION: Click the 🔒 in the address bar and change Notifications to "Allow"')
        throw new Error('Notification permissions denied. Click the 🔒 in the address bar and change Notifications to "Allow", then reload the page.')
      }
      
      if (permission !== 'granted') {
        throw new Error('Notification permissions not granted')
      }
      
      console.log('✅ Notification permissions granted')
    }

    console.log('🔥 Getting FCM token...')
    
    // Use VAPID key (required for web)
    const vapidKey = import.meta.env.VITE_FIREBASE_VAPID_KEY
    const tokenOptions = {}
    
    if (vapidKey) {
      console.log('🔥 Using VAPID key:', vapidKey.substring(0, 10) + '...')
      tokenOptions.vapidKey = vapidKey
    } else {
      console.warn('🔥 VAPID key not configured - this may fail in production')
      console.warn('🔥 To configure VAPID key, go to Firebase Console > Project Settings > Cloud Messaging')
    }
    
    const token = await getToken(messaging, tokenOptions)
    
    if (!token) {
      throw new Error('Failed to obtain FCM token')
    }

    console.log('🔥 FCM token obtained:', token.substring(0, 20) + '...')
    
    // Store in localStorage for persistence
    localStorage.setItem('fcm_token', token)
    
    return token
  } catch (error) {
    console.error('🔥 FCM token error:', error)
    throw error
  }
}

/**
 * Set up foreground message listener
 * @param {Function} callback - Optional callback function to handle incoming messages
 */
export const setupForegroundMessageListener = async (callback) => {
  try {
    // Ensure Firebase is initialized
    if (!messaging) {
      const firebase = await initializeFirebase()
      if (!firebase) return
    }

    const { onMessage } = await import('firebase/messaging')
    
    console.log('🔥 Setting up foreground message listener...')
    
    onMessage(messaging, (payload) => {
      console.log('🔥🚨 FCM MESSAGE RECEIVED (Firebase onMessage):', payload)
      console.log('🔥📱 Detailed payload:', {
        notification: payload.notification,
        data: payload.data,
        from: payload.from,
        messageId: payload.messageId
      })
      console.log('🔥⏰ Timestamp:', new Date().toLocaleTimeString())
      
      // Process the notification according to backend structure
      const processedNotification = processNotificationPayload(payload)
      
      // Execute custom callback if provided
      if (callback && typeof callback === 'function') {
        try {
          callback(processedNotification)
        } catch (error) {
          console.error('🔥❌ Error in onMessage callback:', error)
        }
      }

      // Add to notification store for real-time updates
      try {
        import('@/stores/notifications').then(({ useNotificationsStore }) => {
          const notificationsStore = useNotificationsStore()
          
          const storeNotification = {
            id: `fcm-${Date.now()}`,
            type: processedNotification.data?.type || 'FCMNotification',
            data: {
              title: processedNotification.title,
              message: processedNotification.body,
              ...processedNotification.data
            },
            created_at: new Date().toISOString(),
            read_at: null,
            source: 'firebase-foreground'
          }
          
          console.log('🔥📨 Adding FCM notification to store:', storeNotification)
          notificationsStore.addNewNotification(storeNotification)
        })
      } catch (error) {
        console.error('🔥❌ Error adding FCM notification to store:', error)
      }

      // Show native browser notification if needed
      if (Notification.permission === 'granted') {
        showBrowserNotification(processedNotification)
      }
    })

    console.log('🔥 Foreground message listener configured')
  } catch (error) {
    console.error('🔥 Error setting up message listener:', error)
  }
}

/**
 * Process notification payload according to backend specifications
 * @param {Object} payload - Firebase notification payload
 * @returns {Object} Processed notification
 */
const processNotificationPayload = (payload) => {
  console.log('🔄 Processing notification payload...')
  
  // Base structure
  let processedNotification = {
    title: payload.notification?.title || 'Nueva notificación',
    body: payload.notification?.body || 'Tienes una nueva notificación',
    data: payload.data || {}
  }

  // Process according to notification type from backend
  if (payload.data?.type) {
    const notificationType = payload.data.type
    
    console.log('🔄 Notification type:', notificationType)

    // Process according to backend notification types
    switch (notificationType) {
      case 'TestNotification':
        processedNotification = {
          title: '🧪 Test Notification',
          body: 'This is a test notification sent to all business users',
          data: {
            ...payload.data,
            icon: '🧪',
            priority: 'normal',
            channels: ['Database', 'Broadcast', 'FCM']
          }
        }
        break

      case 'UserSpecificNotification':
        processedNotification = {
          title: payload.data.title || '👤 Personalized Notification',
          body: payload.data.message || 'You have a new personalized notification',
          data: {
            ...payload.data,
            icon: '👤',
            priority: 'high',
            channels: ['Database', 'Broadcast', 'FCM']
          }
        }
        break

      case 'WebSocketTestNotification':
        processedNotification = {
          title: '⚡ WebSocket + FCM Test',
          body: 'This notification arrives via WebSocket (real-time) and FCM (push)',
          data: {
            ...payload.data,
            icon: '⚡',
            priority: 'high',
            test_mode: 'websocket_fcm'
          }
        }
        break

      case 'WebSocketOnlyNotification':
        processedNotification = {
          title: '🔌 WebSocket Only Test',
          body: 'This notification ONLY arrives via WebSocket in real-time',
          data: {
            ...payload.data,
            icon: '🔌',
            priority: 'normal',
            test_mode: 'websocket_only'
          }
        }
        break

      default:
        // Keep original title and body if already present
        if (payload.notification) {
          processedNotification = {
            title: payload.notification.title,
            body: payload.notification.body,
            data: {
              ...payload.data,
              icon: '📱',
              priority: 'normal'
            }
          }
        }
        break
    }
  }

  console.log('✅ Notification processed:', processedNotification)
  return processedNotification
}

/**
 * Show browser notification
 * @param {Object} notification - Processed notification object
 */
const showBrowserNotification = (notification) => {
  try {
    const { title, body, data } = notification
    
    if (title || body) {
      const browserNotification = new Notification(title || 'Nueva notificación', {
        body: body || 'Tienes una nueva notificación',
        icon: '/favicon.ico',
        tag: `fcm-notification-${Date.now()}`,
        data: data,
        requireInteraction: true
      })
      
      console.log('🔥🔔 Browser notification displayed:', browserNotification)
    }
  } catch (error) {
    console.error('🔥❌ Error showing browser notification:', error)
  }
}

/**
 * Delete current FCM token
 */
export const deleteFCMToken = async () => {
  try {
    if (!messaging) {
      console.log('🔥 Firebase Messaging not initialized')
      return
    }

    const { deleteToken } = await import('firebase/messaging')
    
    await deleteToken(messaging)
    localStorage.removeItem('fcm_token')
    
    console.log('🔥 FCM token deleted successfully')
  } catch (error) {
    console.error('🔥 FCM token deletion error:', error)
    throw error
  }
}

/**
 * Get Firestore instance
 * @returns {Object} Firestore database instance
 */
export const getFirestore = () => {
  if (!db) {
    console.error('🔥 Firestore not initialized. Call initializeFirebase() first.')
    return null
  }
  return db
}

/**
 * Get Firebase status and configuration
 * @returns {Object} Firebase status information
 */
export const getFirebaseStatus = () => {
  const status = {
    configured: false,
    initialized: false,
    messagingAvailable: false,
    firestoreAvailable: false,
    tokenExists: false,
  notificationPermission: (typeof Notification !== 'undefined') ? Notification.permission : 'unavailable'
  }

  try {
    // Check configuration completeness
    const missingKeys = Object.entries(firebaseConfig)
      .filter(([_, value]) => !value)
      .map(([key, _]) => key)
    
    status.configured = missingKeys.length === 0
    status.initialized = !!app && !!messaging && !!db
    status.messagingAvailable = !!messaging
    status.firestoreAvailable = !!db
    status.tokenExists = !!localStorage.getItem('fcm_token')
    
    if ('Notification' in window) {
      status.notificationPermission = Notification.permission
    }

    return status
  } catch (error) {
    console.error('🔥 Error getting Firebase status:', error)
    return status
  }
}

// Export clean Firebase service
export default {
  initializeFirebase,
  getFCMToken,
  setupForegroundMessageListener,
  deleteFCMToken,
  getFirestore,
  getFirebaseStatus,
  config: firebaseConfig
}