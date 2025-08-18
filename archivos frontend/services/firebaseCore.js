// Central Firebase App + Realtime Database singleton (para adapters realtime)
import { initializeApp, getApps, getApp } from 'firebase/app'
import { getDatabase } from 'firebase/database'

const firebaseConfig = {
  projectId: 'mozoqr-7d32c',
  apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
  authDomain: 'mozoqr-7d32c.firebaseapp.com',
  databaseURL: 'https://mozoqr-7d32c-default-rtdb.firebaseio.com',
  storageBucket: 'mozoqr-7d32c.appspot.com',
  messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
  appId: import.meta.env.VITE_FIREBASE_APP_ID
}

let _app
let _db

export function getFirebaseApp() {
  if (!_app) {
    if (getApps().length) {
      _app = getApp()
    } else {
      _app = initializeApp(firebaseConfig)
    }
  }
  return _app
}

export function getRealtimeDB() {
  if (!_db) {
    _db = getDatabase(getFirebaseApp())
  }
  return _db
}

export default { getFirebaseApp, getRealtimeDB }
