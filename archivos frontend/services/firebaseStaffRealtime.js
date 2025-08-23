// 🚀 FIREBASE REALTIME - STAFF NOTIFICATIONS (Admin/User)
// Minimal listener to react in real-time to staff requests and invitations

import { initializeApp } from 'firebase/app'
import { Capacitor } from '@capacitor/core'
import {
  getDatabase,
  ref,
  onValue,
  off
} from 'firebase/database'

// Reuse same config used elsewhere
const firebaseConfig = {
  projectId: 'mozoqr-7d32c',
  apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
  authDomain: 'mozoqr-7d32c.firebaseapp.com',
  databaseURL: 'https://mozoqr-7d32c-default-rtdb.firebaseio.com',
  storageBucket: 'mozoqr-7d32c.appspot.com',
  messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
  appId: import.meta.env.VITE_FIREBASE_APP_ID
}

const app = initializeApp(firebaseConfig)
const db = getDatabase(app)

let adminUnsub = null
let userUnsub = null

// Helper: show local/browser notification
const showLocalNotification = async (title, body, data = {}) => {
  try {
    if (Capacitor && Capacitor.isPluginAvailable && Capacitor.isPluginAvailable('LocalNotifications')) {
      const { LocalNotifications } = await import('@capacitor/local-notifications')
      await LocalNotifications.schedule({
        notifications: [{ id: Date.now(), title, body, extra: data, actionTypeId: 'OPEN_APP' }]
      })
      return
    }
  } catch (e) {
    // ignore and fallback to browser API
  }

  if ('Notification' in window) {
    if (Notification.permission === 'default') {
      try { await Notification.requestPermission() } catch (_) {}
    }
    if (Notification.permission === 'granted') {
      new Notification(title, { body, tag: 'staff-realtime', data })
    }
  }
}

// Admin: listen to latest activity for a business and show realtime hints
export const startStaffRealtimeForAdmin = (businessId) => {
  stopStaffRealtimeForAdmin()
  if (!businessId) return

  const businessRef = ref(db, `businesses_staff/${businessId}`)
  let lastRequestId = null

  adminUnsub = onValue(businessRef, (snap) => {
    const val = snap.val()
    if (!val || !val.recent_activity) return
    const { last_request_id, last_request_status } = val.recent_activity
    if (!last_request_id) return

    // Notify on new pending request
    if (lastRequestId !== last_request_id && last_request_status === 'pending') {
      showLocalNotification('🧑‍🍳 Nueva solicitud de personal', 'Hay una nueva solicitud pendiente', {
        type: 'staff_request',
        business_id: String(businessId),
        request_id: String(last_request_id),
        route: '/admin/staff/requests'
      })
      try { window.dispatchEvent(new CustomEvent('staffRequestUpdated', { detail: { businessId, last_request_id, last_request_status } })) } catch (_) {}
    }
    lastRequestId = last_request_id
  }, (err) => {
    console.error('🚨 Staff realtime (admin) error:', err)
  })
}

export const stopStaffRealtimeForAdmin = () => {
  if (adminUnsub) {
    try { adminUnsub() } catch (_) {}
    adminUnsub = null
  }
}

// User: listen to user-specific staff status changes
export const startStaffRealtimeForUser = (userId) => {
  stopStaffRealtimeForUser()
  if (!userId) return

  const userRef = ref(db, `users_staff/${userId}`)
  let lastStatus = null

  userUnsub = onValue(userRef, (snap) => {
    const val = snap.val()
    const current = val && val.current_request
    if (!current) return

    if (current.status && current.status !== lastStatus) {
      if (current.status === 'confirmed') {
        showLocalNotification('✅ Solicitud aprobada', 'Tu solicitud fue aprobada', {
          type: 'staff_request', event_type: 'confirmed', user_id: String(userId), route: '/staff/requests'
        })
      } else if (current.status === 'rejected') {
        showLocalNotification('❌ Solicitud rechazada', 'Tu solicitud fue rechazada', {
          type: 'staff_request', event_type: 'rejected', user_id: String(userId), route: '/staff/requests'
        })
      } else if (current.status === 'invited') {
        showLocalNotification('📩 Invitación recibida', 'Tienes una invitación para trabajar', {
          type: 'staff_invitation', user_id: String(userId), route: '/staff/invitations'
        })
      }
      try { window.dispatchEvent(new CustomEvent('staffUserStatusChanged', { detail: { userId, current } })) } catch (_) {}
      lastStatus = current.status
    }
  }, (err) => {
    console.error('🚨 Staff realtime (user) error:', err)
  })
}

export const stopStaffRealtimeForUser = () => {
  if (userUnsub) {
    try { userUnsub() } catch (_) {}
    userUnsub = null
  }
}

export default {
  startStaffRealtimeForAdmin,
  stopStaffRealtimeForAdmin,
  startStaffRealtimeForUser,
  stopStaffRealtimeForUser
}
