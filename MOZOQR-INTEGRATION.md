# 🍽️ INTEGRACIÓN MOZOQR.COM - FIREBASE TIEMPO REAL

## 📋 RESUMEN
Esta documentación es específica para integrar `mozoqr.com` con el sistema de notificaciones Firebase del backend MOZO.

## 🔗 ENDPOINTS DISPONIBLES

### 1. Obtener configuración Firebase
```
GET /api/firebase/config
```

**Respuesta:**
```json
{
  "success": true,
  "firebase_config": {
    "apiKey": "tu-api-key",
    "authDomain": "mozoqr-7d32c.firebaseapp.com",
    "projectId": "mozoqr-7d32c",
    "storageBucket": "mozoqr-7d32c.appspot.com",
    "messagingSenderId": "123456789",
    "appId": "1:123456789:web:abc123def456"
  },
  "realtime_endpoints": {
    "table_calls": "/tables/{table_id}/waiter_calls",
    "table_status": "/tables/{table_id}/status/current"
  }
}
```

### 2. Obtener configuración específica de mesa
```
GET /api/firebase/table/{table_id}/config
```

**Respuesta:**
```json
{
  "success": true,
  "table": {
    "id": "5",
    "number": "5",
    "name": "Mesa Principal",
    "business_id": "1",
    "notifications_enabled": true,
    "has_active_waiter": true
  },
  "firebase_config": {
    "apiKey": "tu-api-key",
    "authDomain": "mozoqr-7d32c.firebaseapp.com",
    "projectId": "mozoqr-7d32c",
    "storageBucket": "mozoqr-7d32c.appspot.com",
    "messagingSenderId": "123456789",
    "appId": "1:123456789:web:abc123def456"
  },
  "firestore_paths": {
    "table_calls": "tables/5/waiter_calls",
    "table_status": "tables/5/status/current"
  }
}
```

### 3. Llamar al mozo (ya existente)
```
POST /api/tables/{table_id}/call-waiter
```

## 🚀 IMPLEMENTACIÓN EN MOZOQR.COM

### 1. Estructura de archivos recomendada
```
src/
├── services/
│   ├── firebase-config.js
│   ├── firebase-realtime.js
│   └── api-client.js
├── components/
│   ├── WaiterCallModal.vue
│   └── CallWaiterButton.vue
└── pages/
    └── QrTable.vue
```

### 2. Configurar Firebase (firebase-config.js)
```javascript
import { initializeApp } from 'firebase/app';
import { getFirestore } from 'firebase/firestore';

class FirebaseManager {
  constructor() {
    this.app = null;
    this.db = null;
    this.config = null;
  }

  async initialize(backendUrl) {
    try {
      // Obtener config del backend
      const response = await fetch(`${backendUrl}/api/firebase/config`);
      const data = await response.json();
      
      if (data.success) {
        this.config = data.firebase_config;
        this.app = initializeApp(this.config);
        this.db = getFirestore(this.app);
        
        console.log('Firebase initialized successfully');
        return true;
      }
    } catch (error) {
      console.error('Error initializing Firebase:', error);
      return false;
    }
  }

  getDb() {
    if (!this.db) {
      throw new Error('Firebase not initialized');
    }
    return this.db;
  }
}

export const firebaseManager = new FirebaseManager();
```

### 3. Cliente API (api-client.js)
```javascript
class MozoApiClient {
  constructor(baseUrl) {
    this.baseUrl = baseUrl;
  }

  async getTableConfig(tableId) {
    const response = await fetch(`${this.baseUrl}/api/firebase/table/${tableId}/config`);
    return await response.json();
  }

  async callWaiter(tableId, message = null) {
    const response = await fetch(`${this.baseUrl}/api/tables/${tableId}/call-waiter`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        message: message || `Llamada desde mesa QR`,
        client_info: {
          source: 'mozoqr.com',
          timestamp: new Date().toISOString()
        }
      })
    });

    return await response.json();
  }
}

export const apiClient = new MozoApiClient(process.env.VUE_APP_BACKEND_URL);
```

### 4. Servicio tiempo real (firebase-realtime.js)
```javascript
import { collection, doc, onSnapshot, query, orderBy, limit } from 'firebase/firestore';
import { firebaseManager } from './firebase-config';

class RealtimeService {
  constructor() {
    this.listeners = new Map();
  }

  // Escuchar llamadas de una mesa específica
  listenToTableCalls(tableId, callback) {
    const db = firebaseManager.getDb();
    const callsRef = collection(db, `tables/${tableId}/waiter_calls`);
    const q = query(callsRef, orderBy('timestamp', 'desc'), limit(10));
    
    const unsubscribe = onSnapshot(q, (snapshot) => {
      const calls = [];
      
      snapshot.docChanges().forEach((change) => {
        const callData = { id: change.doc.id, ...change.doc.data() };
        
        if (change.type === 'added' || change.type === 'modified') {
          callback('call_update', callData);
        }
      });
    }, (error) => {
      console.error('Error listening to table calls:', error);
      callback('error', { message: error.message });
    });

    this.listeners.set(`table_calls_${tableId}`, unsubscribe);
    return unsubscribe;
  }

  // Escuchar estado de la mesa
  listenToTableStatus(tableId, callback) {
    const db = firebaseManager.getDb();
    const statusRef = doc(db, `tables/${tableId}/status/current`);
    
    const unsubscribe = onSnapshot(statusRef, (doc) => {
      if (doc.exists()) {
        const statusData = doc.data();
        callback('status_update', statusData);
      }
    }, (error) => {
      console.error('Error listening to table status:', error);
      callback('error', { message: error.message });
    });

    this.listeners.set(`table_status_${tableId}`, unsubscribe);
    return unsubscribe;
  }

  // Limpiar todos los listeners
  cleanup() {
    this.listeners.forEach(unsubscribe => {
      if (typeof unsubscribe === 'function') {
        unsubscribe();
      }
    });
    this.listeners.clear();
  }
}

export const realtimeService = new RealtimeService();
```

### 5. Componente Modal (WaiterCallModal.vue)
```vue
<template>
  <div v-if="show" class="modal-overlay" @click="handleBackdropClick">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3>{{ title }}</h3>
      </div>
      
      <div class="modal-body">
        <div class="status-icon">
          <i :class="statusIcon" :style="{ color: statusColor }"></i>
        </div>
        
        <p class="message">{{ message }}</p>
        
        <div v-if="showProgress" class="progress-steps">
          <div class="step" :class="{ active: currentStep >= 1 }">
            <span>🔔 Llamando</span>
          </div>
          <div class="step" :class="{ active: currentStep >= 2 }">
            <span>👨‍🍳 Confirmado</span>
          </div>
          <div class="step" :class="{ active: currentStep >= 3 }">
            <span>✅ Completado</span>
          </div>
        </div>
      </div>
      
      <div class="modal-footer" v-if="showCloseButton">
        <button @click="close" class="btn-close">Cerrar</button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'WaiterCallModal',
  
  data() {
    return {
      show: false,
      title: '',
      message: '',
      currentStep: 1,
      autoCloseTimer: null
    }
  },
  
  computed: {
    statusIcon() {
      switch(this.currentStep) {
        case 1: return 'fas fa-bell fa-spin';
        case 2: return 'fas fa-check-circle';
        case 3: return 'fas fa-heart';
        default: return 'fas fa-bell';
      }
    },
    
    statusColor() {
      switch(this.currentStep) {
        case 1: return '#ff9500';
        case 2: return '#007bff'; 
        case 3: return '#28a745';
        default: return '#6c757d';
      }
    },
    
    showProgress() {
      return this.currentStep <= 3;
    },
    
    showCloseButton() {
      return this.currentStep === 3;
    }
  },
  
  methods: {
    showCalling() {
      this.show = true;
      this.currentStep = 1;
      this.title = 'Llamando mozo';
      this.message = 'Su llamada ha sido enviada. Aguarde por favor...';
    },
    
    showAcknowledged() {
      this.currentStep = 2;
      this.title = '¡Mozo llamado!';
      this.message = 'Su mozo confirmó la llamada y viene en camino.';
    },
    
    showCompleted() {
      this.currentStep = 3;
      this.title = '¡Atención completada!';
      this.message = '¡Gracias por elegirnos! Esperamos que haya disfrutado su experiencia.';
      
      // Auto-cerrar después de 5 segundos
      this.autoCloseTimer = setTimeout(() => {
        this.close();
      }, 5000);
    },
    
    showError(errorMessage) {
      this.show = true;
      this.currentStep = 0;
      this.title = 'Error';
      this.message = errorMessage;
    },
    
    close() {
      this.show = false;
      if (this.autoCloseTimer) {
        clearTimeout(this.autoCloseTimer);
        this.autoCloseTimer = null;
      }
    },
    
    handleBackdropClick() {
      if (this.currentStep === 3) {
        this.close();
      }
    }
  },
  
  beforeUnmount() {
    if (this.autoCloseTimer) {
      clearTimeout(this.autoCloseTimer);
    }
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  max-width: 400px;
  width: 90%;
  text-align: center;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.status-icon i {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.progress-steps {
  display: flex;
  justify-content: space-between;
  margin-top: 1.5rem;
  padding: 0 1rem;
}

.step {
  opacity: 0.3;
  transition: opacity 0.3s ease;
}

.step.active {
  opacity: 1;
  font-weight: bold;
}
</style>
```

### 6. Página principal QR (QrTable.vue)
```vue
<template>
  <div class="qr-table-page">
    <div class="table-header">
      <h1>Mesa {{ table?.number }}</h1>
      <p v-if="table?.name">{{ table.name }}</p>
    </div>

    <div class="call-section">
      <button 
        @click="callWaiter" 
        :disabled="isLoading || !canCallWaiter"
        class="call-waiter-btn"
      >
        <i class="fas fa-bell"></i>
        {{ buttonText }}
      </button>
      
      <p v-if="!canCallWaiter" class="warning-text">
        {{ warningMessage }}
      </p>
    </div>

    <WaiterCallModal ref="modal" />
  </div>
</template>

<script>
import { firebaseManager } from '../services/firebase-config';
import { realtimeService } from '../services/firebase-realtime';
import { apiClient } from '../services/api-client';
import WaiterCallModal from '../components/WaiterCallModal.vue';

export default {
  name: 'QrTable',
  
  components: {
    WaiterCallModal
  },
  
  data() {
    return {
      tableId: null,
      table: null,
      isLoading: false,
      isInitialized: false,
      activeCalls: new Map()
    }
  },
  
  computed: {
    canCallWaiter() {
      return this.table?.notifications_enabled && 
             this.table?.has_active_waiter && 
             !this.hasActivePendingCall;
    },
    
    hasActivePendingCall() {
      return Array.from(this.activeCalls.values())
        .some(call => call.status === 'pending' || call.status === 'acknowledged');
    },
    
    buttonText() {
      if (this.isLoading) return 'Llamando...';
      if (!this.canCallWaiter) return 'No disponible';
      return 'Llamar Mozo';
    },
    
    warningMessage() {
      if (!this.table?.notifications_enabled) {
        return 'Las notificaciones están deshabilitadas para esta mesa.';
      }
      if (!this.table?.has_active_waiter) {
        return 'Esta mesa no tiene un mozo asignado actualmente.';
      }
      if (this.hasActivePendingCall) {
        return 'Ya hay una llamada activa. Aguarde por favor.';
      }
      return '';
    }
  },
  
  async created() {
    this.tableId = this.$route.params.tableId;
    await this.initialize();
  },
  
  async beforeUnmount() {
    realtimeService.cleanup();
  },
  
  methods: {
    async initialize() {
      try {
        // 1. Inicializar Firebase
        const firebaseReady = await firebaseManager.initialize(process.env.VUE_APP_BACKEND_URL);
        if (!firebaseReady) {
          throw new Error('No se pudo conectar con Firebase');
        }
        
        // 2. Obtener configuración de la mesa
        const tableConfig = await apiClient.getTableConfig(this.tableId);
        if (!tableConfig.success) {
          throw new Error(tableConfig.message || 'Mesa no encontrada');
        }
        
        this.table = tableConfig.table;
        
        // 3. Configurar listeners de tiempo real
        this.setupRealtimeListeners();
        
        this.isInitialized = true;
        
      } catch (error) {
        console.error('Error inicializando:', error);
        this.$refs.modal.showError(error.message);
      }
    },
    
    setupRealtimeListeners() {
      // Escuchar llamadas de la mesa
      realtimeService.listenToTableCalls(this.tableId, (event, data) => {
        if (event === 'call_update') {
          this.handleCallUpdate(data);
        } else if (event === 'error') {
          console.error('Error en listener de llamadas:', data);
        }
      });
      
      // Escuchar cambios de estado de la mesa
      realtimeService.listenToTableStatus(this.tableId, (event, data) => {
        if (event === 'status_update') {
          this.handleStatusUpdate(data);
        }
      });
    },
    
    handleCallUpdate(callData) {
      // Actualizar mapa de llamadas activas
      this.activeCalls.set(callData.id, callData);
      
      // Manejar diferentes tipos de evento
      switch (callData.event_type) {
        case 'created':
          this.$refs.modal.showCalling();
          break;
          
        case 'acknowledged':
          this.$refs.modal.showAcknowledged();
          break;
          
        case 'completed':
          this.$refs.modal.showCompleted();
          // Limpiar llamada del mapa después de un tiempo
          setTimeout(() => {
            this.activeCalls.delete(callData.id);
          }, 10000);
          break;
      }
    },
    
    handleStatusUpdate(statusData) {
      if (statusData.table_id == this.tableId) {
        // Actualizar información de la mesa
        this.table = {
          ...this.table,
          notifications_enabled: statusData.notifications_enabled,
          active_waiter_id: statusData.active_waiter_id,
          has_active_waiter: !!statusData.active_waiter_id
        };
      }
    },
    
    async callWaiter() {
      if (!this.canCallWaiter || this.isLoading) return;
      
      this.isLoading = true;
      
      try {
        const response = await apiClient.callWaiter(this.tableId);
        
        if (!response.success) {
          this.$refs.modal.showError(response.message);
        }
        // El modal se mostrará automáticamente via el listener de Firestore
        
      } catch (error) {
        console.error('Error llamando mozo:', error);
        this.$refs.modal.showError('Error de conexión. Intente nuevamente.');
      } finally {
        this.isLoading = false;
      }
    }
  }
}
</script>

<style scoped>
.qr-table-page {
  min-height: 100vh;
  padding: 2rem;
  text-align: center;
}

.call-waiter-btn {
  background: #28a745;
  color: white;
  border: none;
  padding: 1rem 2rem;
  font-size: 1.2rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.call-waiter-btn:hover:not(:disabled) {
  background: #218838;
  transform: translateY(-2px);
}

.call-waiter-btn:disabled {
  background: #6c757d;
  cursor: not-allowed;
}

.warning-text {
  color: #dc3545;
  margin-top: 1rem;
  font-style: italic;
}
</style>
```

### 7. Variables de entorno (.env)
```env
VUE_APP_BACKEND_URL=https://tu-backend-url.com
```

## 🔧 CONFIGURACIÓN BACKEND (.env)

Asegúrate de tener estas variables en el `.env` del backend:

```env
# Firebase Frontend Config
FIREBASE_API_KEY=tu-api-key-frontend
FIREBASE_AUTH_DOMAIN=mozoqr-7d32c.firebaseapp.com
FIREBASE_STORAGE_BUCKET=mozoqr-7d32c.appspot.com
FIREBASE_MESSAGING_SENDER_ID=123456789
FIREBASE_APP_ID=1:123456789:web:abc123def456

# Firebase Backend Config (ya existentes)
FIREBASE_PROJECT_ID=mozoqr-7d32c
FIREBASE_SERVER_KEY=tu-server-key
FIREBASE_SERVICE_ACCOUNT_PATH=storage/app/firebase/firebase.json
```

## 🚀 FLUJO COMPLETO

1. **Usuario entra a mozoqr.com/table/5**
2. **Frontend obtiene config** → `GET /api/firebase/table/5/config`
3. **Frontend inicializa Firebase** → Conecta a Firestore
4. **Frontend configura listeners** → Escucha `tables/5/waiter_calls`
5. **Usuario presiona "Llamar Mozo"** → `POST /api/tables/5/call-waiter`
6. **Backend crea llamada** → Escribe en Firestore + envía FCM
7. **Frontend recibe evento** → Muestra modal "Llamando mozo..."
8. **Mozo confirma** → Backend actualiza Firestore
9. **Frontend recibe confirmación** → Modal cambia a "¡Mozo llamado!"
10. **Mozo completa** → Backend marca completado
11. **Frontend recibe completado** → Modal "¡Atención completada!"

## ✅ CHECKLIST DE IMPLEMENTACIÓN

- [ ] Configurar variables de entorno Firebase
- [ ] Instalar Firebase SDK en frontend
- [ ] Implementar FirebaseManager
- [ ] Crear ApiClient para llamadas al backend
- [ ] Implementar RealtimeService
- [ ] Crear WaiterCallModal component
- [ ] Implementar página QrTable
- [ ] Probar flujo completo
- [ ] Manejar casos de error
- [ ] Optimizar performance