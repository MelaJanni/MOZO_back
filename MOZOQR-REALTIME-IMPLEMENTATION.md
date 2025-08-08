# 🔥 IMPLEMENTACIÓN COMPLETA: Firebase Real-time para MozoQR.com

## 📋 RESUMEN

Esta guía muestra cómo integrar las notificaciones de Firebase en tiempo real en la página pública del menú (mozoqr.com), reemplazando el sistema de polling por actualizaciones en tiempo real cuando un usuario llama al mozo.

## 🚀 ARQUITECTURA DE LA INTEGRACIÓN

### Backend (Ya implementado)
- ✅ **WaiterCallController** - Maneja llamadas de mozo
- ✅ **FirebaseRealtimeService** - Escribe a Firestore 
- ✅ **FirebaseConfigController** - Provee configuración
- ✅ **PublicQrController** - API pública mejorada

### Frontend (A implementar)
- 🔄 **Firebase SDK** - Conexión a Firestore
- 🔄 **Real-time listeners** - Escucha cambios en tiempo real
- 🔄 **UI Components** - Modal de estado de llamada
- 🔄 **Fallback polling** - Si Firebase falla

## 📁 ESTRUCTURA DE ARCHIVOS FRONTEND

```
mozoqr.com/
├── src/
│   ├── services/
│   │   ├── firebase-config.js       # Configuración Firebase
│   │   ├── firebase-realtime.js     # Listeners tiempo real  
│   │   ├── api-client.js           # Cliente HTTP
│   │   └── polling-fallback.js     # Polling de respaldo
│   ├── components/
│   │   ├── WaiterCallModal.js      # Modal de llamada
│   │   ├── CallWaiterButton.js     # Botón llamar mozo
│   │   └── MenuDisplay.js          # Visualización menú
│   └── pages/
│       └── QRTablePage.js          # Página principal QR
├── public/
│   └── firebase-messaging-sw.js    # Service Worker
└── package.json
```

## 🔧 IMPLEMENTACIÓN PASO A PASO

### 1. Instalar Dependencias

```bash
npm install firebase
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
    this.isInitialized = false;
  }

  async initialize() {
    if (this.isInitialized) {
      return true;
    }

    try {
      // Obtener configuración del backend
      const response = await fetch('/api/firebase/config');
      const data = await response.json();
      
      if (data.success) {
        this.config = data.firebase_config;
        this.app = initializeApp(this.config);
        this.db = getFirestore(this.app);
        this.isInitialized = true;
        
        console.log('✅ Firebase initialized successfully');
        return true;
      }
      
      throw new Error('Failed to get Firebase config');
    } catch (error) {
      console.error('❌ Error initializing Firebase:', error);
      return false;
    }
  }

  getDb() {
    if (!this.db) {
      throw new Error('Firebase not initialized. Call initialize() first.');
    }
    return this.db;
  }

  isReady() {
    return this.isInitialized && this.db !== null;
  }
}

export const firebaseManager = new FirebaseManager();
```

### 3. Cliente API (api-client.js)

```javascript
class MozoApiClient {
  constructor() {
    this.baseUrl = window.location.origin;
  }

  async getTableInfo(restaurantSlug, tableCode) {
    const response = await fetch(`${this.baseUrl}/api/qr/${restaurantSlug}/${tableCode}`);
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    return await response.json();
  }

  async callWaiter(tableId, options = {}) {
    const response = await fetch(`${this.baseUrl}/api/tables/${tableId}/call-waiter`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        message: options.message || 'Llamada desde página QR',
        urgency: options.urgency || 'normal',
        client_info: {
          source: 'mozoqr.com',
          timestamp: new Date().toISOString(),
          user_agent: navigator.userAgent
        }
      })
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    return await response.json();
  }

  // Fallback polling para cuando Firebase no funciona
  async getTableStatus(tableId) {
    const response = await fetch(`${this.baseUrl}/api/table/${tableId}/status`);
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    return await response.json();
  }
}

export const apiClient = new MozoApiClient();
```

### 4. Servicio Real-time (firebase-realtime.js)

```javascript
import { collection, doc, onSnapshot, query, orderBy, limit } from 'firebase/firestore';
import { firebaseManager } from './firebase-config';

class RealtimeService {
  constructor() {
    this.listeners = new Map();
    this.isEnabled = true;
  }

  /**
   * Escuchar llamadas de una mesa específica
   */
  listenToTableCalls(tableId, callback) {
    if (!this.isEnabled || !firebaseManager.isReady()) {
      console.warn('🔄 Firebase not ready, skipping real-time listener');
      return null;
    }

    try {
      const db = firebaseManager.getDb();
      const callsRef = collection(db, `tables/${tableId}/waiter_calls`);
      const q = query(callsRef, orderBy('timestamp', 'desc'), limit(5));
      
      const unsubscribe = onSnapshot(q, 
        (snapshot) => {
          snapshot.docChanges().forEach((change) => {
            const callData = { 
              id: change.doc.id, 
              ...change.doc.data() 
            };
            
            // Solo procesar nuevas llamadas o modificaciones
            if (change.type === 'added' || change.type === 'modified') {
              console.log(`🔔 Firebase event: ${callData.event_type}`, callData);
              callback('call_update', callData);
            }
          });
        },
        (error) => {
          console.error('❌ Error listening to table calls:', error);
          this.handleFirebaseError(error);
          callback('error', { message: error.message, code: error.code });
        }
      );

      this.listeners.set(`table_calls_${tableId}`, unsubscribe);
      console.log(`✅ Listening to table ${tableId} calls`);
      return unsubscribe;

    } catch (error) {
      console.error('❌ Failed to setup table calls listener:', error);
      callback('error', { message: error.message });
      return null;
    }
  }

  /**
   * Escuchar estado de la mesa
   */
  listenToTableStatus(tableId, callback) {
    if (!this.isEnabled || !firebaseManager.isReady()) {
      return null;
    }

    try {
      const db = firebaseManager.getDb();
      const statusRef = doc(db, `tables/${tableId}/status/current`);
      
      const unsubscribe = onSnapshot(statusRef, 
        (doc) => {
          if (doc.exists()) {
            const statusData = doc.data();
            console.log('📊 Table status update:', statusData);
            callback('status_update', statusData);
          }
        },
        (error) => {
          console.error('❌ Error listening to table status:', error);
          callback('error', { message: error.message });
        }
      );

      this.listeners.set(`table_status_${tableId}`, unsubscribe);
      return unsubscribe;

    } catch (error) {
      console.error('❌ Failed to setup table status listener:', error);
      return null;
    }
  }

  /**
   * Manejar errores de Firebase
   */
  handleFirebaseError(error) {
    // Si hay errores de permisos o conexión, deshabilitar temporalmente
    if (error.code === 'permission-denied' || 
        error.code === 'unavailable' ||
        error.message.includes('network')) {
      
      console.warn('🔄 Firebase temporarily disabled due to error:', error.code);
      this.disable();
      
      // Intentar rehabilitar después de 30 segundos
      setTimeout(() => {
        console.log('🔄 Re-enabling Firebase...');
        this.enable();
      }, 30000);
    }
  }

  /**
   * Limpiar todos los listeners
   */
  cleanup() {
    console.log('🧹 Cleaning up Firebase listeners...');
    this.listeners.forEach((unsubscribe, key) => {
      if (typeof unsubscribe === 'function') {
        unsubscribe();
        console.log(`✅ Unsubscribed: ${key}`);
      }
    });
    this.listeners.clear();
  }

  disable() {
    this.isEnabled = false;
    this.cleanup();
  }

  enable() {
    this.isEnabled = true;
  }

  isFirebaseEnabled() {
    return this.isEnabled && firebaseManager.isReady();
  }
}

export const realtimeService = new RealtimeService();
```

### 5. Fallback Polling (polling-fallback.js)

```javascript
import { apiClient } from './api-client';

class PollingService {
  constructor() {
    this.intervals = new Map();
    this.isActive = false;
  }

  /**
   * Iniciar polling de estado de mesa
   */
  startTableStatusPolling(tableId, callback, intervalMs = 3000) {
    if (this.intervals.has(`table_${tableId}`)) {
      this.stopTableStatusPolling(tableId);
    }

    console.log(`🔄 Starting polling for table ${tableId}`);
    
    const poll = async () => {
      try {
        const response = await apiClient.getTableStatus(tableId);
        if (response.success) {
          callback('status_update', response.data);
        }
      } catch (error) {
        console.error('❌ Polling error:', error);
        callback('error', { message: error.message });
      }
    };

    // Hacer primera consulta inmediatamente
    poll();

    // Configurar intervalo
    const intervalId = setInterval(poll, intervalMs);
    this.intervals.set(`table_${tableId}`, intervalId);
    this.isActive = true;
  }

  /**
   * Detener polling de una mesa
   */
  stopTableStatusPolling(tableId) {
    const intervalId = this.intervals.get(`table_${tableId}`);
    if (intervalId) {
      clearInterval(intervalId);
      this.intervals.delete(`table_${tableId}`);
      console.log(`✅ Stopped polling for table ${tableId}`);
    }
  }

  /**
   * Limpiar todos los pollings
   */
  cleanup() {
    console.log('🧹 Cleaning up polling intervals...');
    this.intervals.forEach((intervalId, key) => {
      clearInterval(intervalId);
      console.log(`✅ Cleared interval: ${key}`);
    });
    this.intervals.clear();
    this.isActive = false;
  }

  isPolling() {
    return this.isActive && this.intervals.size > 0;
  }
}

export const pollingService = new PollingService();
```

### 6. Modal de Llamada (WaiterCallModal.js)

```javascript
class WaiterCallModal {
  constructor() {
    this.isVisible = false;
    this.currentStep = 1;
    this.autoCloseTimer = null;
    this.createElement();
  }

  createElement() {
    // Crear elementos del modal
    this.overlay = document.createElement('div');
    this.overlay.className = 'waiter-modal-overlay';
    this.overlay.style.display = 'none';

    this.modal = document.createElement('div');
    this.modal.className = 'waiter-modal-content';
    
    this.modal.innerHTML = `
      <div class="modal-header">
        <h3 id="modal-title">Llamando mozo</h3>
      </div>
      
      <div class="modal-body">
        <div class="status-icon">
          <i id="status-icon" class="fas fa-bell fa-spin"></i>
        </div>
        
        <p id="modal-message">Su llamada ha sido enviada. Aguarde por favor...</p>
        
        <div id="progress-steps" class="progress-steps">
          <div class="step active" data-step="1">
            <span>🔔 Llamando</span>
          </div>
          <div class="step" data-step="2">
            <span>👨‍🍳 Confirmado</span>
          </div>
          <div class="step" data-step="3">
            <span>✅ Completado</span>
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button id="close-btn" class="btn-close" style="display: none;">Cerrar</button>
      </div>
    `;

    this.overlay.appendChild(this.modal);
    document.body.appendChild(this.overlay);

    // Event listeners
    this.overlay.addEventListener('click', (e) => {
      if (e.target === this.overlay && this.currentStep === 3) {
        this.hide();
      }
    });

    this.modal.querySelector('#close-btn').addEventListener('click', () => {
      this.hide();
    });
  }

  show() {
    this.isVisible = true;
    this.overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden'; // Prevenir scroll
  }

  hide() {
    this.isVisible = false;
    this.overlay.style.display = 'none';
    document.body.style.overflow = ''; // Restaurar scroll
    
    if (this.autoCloseTimer) {
      clearTimeout(this.autoCloseTimer);
      this.autoCloseTimer = null;
    }
  }

  showCalling() {
    this.currentStep = 1;
    this.updateUI({
      title: 'Llamando mozo',
      message: 'Su llamada ha sido enviada. Aguarde por favor...',
      icon: 'fas fa-bell fa-spin',
      iconColor: '#ff9500'
    });
    this.show();
  }

  showAcknowledged() {
    this.currentStep = 2;
    this.updateUI({
      title: '¡Mozo llamado!',
      message: 'Su mozo confirmó la llamada y viene en camino.',
      icon: 'fas fa-check-circle',
      iconColor: '#007bff'
    });
  }

  showCompleted() {
    this.currentStep = 3;
    this.updateUI({
      title: '¡Atención completada!',
      message: '¡Gracias por elegirnos! Esperamos que haya disfrutado su experiencia.',
      icon: 'fas fa-heart',
      iconColor: '#28a745'
    });
    
    // Mostrar botón cerrar
    this.modal.querySelector('#close-btn').style.display = 'block';
    
    // Auto-cerrar después de 5 segundos
    this.autoCloseTimer = setTimeout(() => {
      this.hide();
    }, 5000);
  }

  showError(errorMessage) {
    this.currentStep = 0;
    this.updateUI({
      title: 'Error',
      message: errorMessage,
      icon: 'fas fa-exclamation-triangle',
      iconColor: '#dc3545'
    });
    this.modal.querySelector('#close-btn').style.display = 'block';
    this.show();
  }

  updateUI({ title, message, icon, iconColor }) {
    this.modal.querySelector('#modal-title').textContent = title;
    this.modal.querySelector('#modal-message').textContent = message;
    
    const iconElement = this.modal.querySelector('#status-icon');
    iconElement.className = icon;
    iconElement.style.color = iconColor;

    // Actualizar pasos de progreso
    this.modal.querySelectorAll('.step').forEach((step, index) => {
      const stepNumber = index + 1;
      if (stepNumber <= this.currentStep) {
        step.classList.add('active');
      } else {
        step.classList.remove('active');
      }
    });
  }
}

export { WaiterCallModal };
```

### 7. Página Principal QR (QRTablePage.js)

```javascript
import { firebaseManager } from '../services/firebase-config.js';
import { realtimeService } from '../services/firebase-realtime.js';
import { pollingService } from '../services/polling-fallback.js';
import { apiClient } from '../services/api-client.js';
import { WaiterCallModal } from '../components/WaiterCallModal.js';

class QRTablePage {
  constructor() {
    this.tableData = null;
    this.modal = new WaiterCallModal();
    this.isLoading = false;
    this.activeCalls = new Map();
    this.useFirebase = false;
    
    this.init();
  }

  async init() {
    try {
      // Extraer parámetros de la URL
      const urlPath = window.location.pathname;
      const [, , restaurantSlug, tableCode] = urlPath.split('/');
      
      if (!restaurantSlug || !tableCode) {
        throw new Error('URL inválida. Parámetros faltantes.');
      }

      // Mostrar loading
      this.showLoading('Cargando información de la mesa...');

      // Obtener información de la mesa
      const response = await apiClient.getTableInfo(restaurantSlug, tableCode);
      
      if (!response.success) {
        throw new Error(response.message || 'Error al cargar información de la mesa');
      }

      this.tableData = response.data;
      
      // Renderizar información de la mesa
      this.renderTableInfo();
      
      // Intentar inicializar Firebase
      const firebaseReady = await firebaseManager.initialize();
      
      if (firebaseReady && this.tableData.firebase_config?.enabled) {
        this.useFirebase = true;
        this.setupFirebaseListeners();
        console.log('✅ Using Firebase real-time updates');
      } else {
        this.useFirebase = false;
        console.log('🔄 Using polling fallback');
      }

      // Configurar botón llamar mozo
      this.setupCallWaiterButton();
      
      this.hideLoading();

    } catch (error) {
      console.error('❌ Error initializing QR page:', error);
      this.showError(error.message);
    }
  }

  renderTableInfo() {
    const container = document.getElementById('table-info');
    if (!container) return;

    const { table, business, restaurant, menu, waiter } = this.tableData;
    
    container.innerHTML = `
      <div class="table-header">
        ${business?.logo ? `<img src="${business.logo}" alt="${business.name}" class="business-logo">` : ''}
        <h1>${business?.name || restaurant.name}</h1>
        <p class="table-info">Mesa ${table.number}${table.name ? ` - ${table.name}` : ''}</p>
        ${business?.address ? `<p class="address">${business.address}</p>` : ''}
        ${waiter ? `<p class="waiter-info">👨‍🍳 Su mozo: <strong>${waiter.name}</strong></p>` : ''}
      </div>

      ${menu ? `
        <div class="menu-section">
          <h2>📄 Nuestro Menú</h2>
          <div class="menu-container">
            <iframe src="${menu.download_url}" 
                    class="menu-pdf"
                    title="${menu.name}">
            </iframe>
            <div class="menu-actions">
              <a href="${menu.download_url}" target="_blank" class="download-btn">
                📱 Abrir menú en nueva pestaña
              </a>
            </div>
          </div>
        </div>
      ` : ''}

      <div class="call-section" id="call-section">
        <!-- El botón se renderizará aquí -->
      </div>
    `;
  }

  setupCallWaiterButton() {
    const callSection = document.getElementById('call-section');
    if (!callSection) return;

    const { table } = this.tableData;
    
    if (table.can_call_waiter) {
      callSection.innerHTML = `
        <button id="call-waiter-btn" class="call-waiter-btn">
          🔔 Llamar Mozo
        </button>
        <p class="call-info">Presione el botón para solicitar atención del mozo</p>
      `;

      document.getElementById('call-waiter-btn').addEventListener('click', () => {
        this.callWaiter();
      });
    } else {
      callSection.innerHTML = `
        <div class="call-disabled">
          <p>⚠️ No es posible llamar al mozo en este momento</p>
          <p class="reason">Esta mesa no tiene un mozo asignado o las notificaciones están desactivadas.</p>
        </div>
      `;
    }
  }

  setupFirebaseListeners() {
    if (!this.useFirebase || !this.tableData) return;

    const { table } = this.tableData;

    // Escuchar llamadas de la mesa
    realtimeService.listenToTableCalls(table.id, (event, data) => {
      if (event === 'call_update') {
        this.handleCallUpdate(data);
      } else if (event === 'error') {
        console.error('❌ Firebase listener error:', data);
        this.fallbackToPolling();
      }
    });

    // Escuchar cambios de estado de la mesa
    realtimeService.listenToTableStatus(table.id, (event, data) => {
      if (event === 'status_update') {
        this.handleStatusUpdate(data);
      }
    });
  }

  fallbackToPolling() {
    console.log('🔄 Falling back to polling...');
    this.useFirebase = false;
    realtimeService.cleanup();
    
    // Iniciar polling si hay llamadas activas
    if (this.activeCalls.size > 0) {
      pollingService.startTableStatusPolling(
        this.tableData.table.id, 
        (event, data) => {
          if (event === 'status_update') {
            this.handlePollingUpdate(data);
          }
        }
      );
    }
  }

  handleCallUpdate(callData) {
    // Actualizar mapa de llamadas activas
    this.activeCalls.set(callData.id, callData);
    
    // Procesar según el tipo de evento
    switch (callData.event_type) {
      case 'created':
        this.modal.showCalling();
        break;
        
      case 'acknowledged':
        this.modal.showAcknowledged();
        break;
        
      case 'completed':
        this.modal.showCompleted();
        // Limpiar llamada después de mostrar completado
        setTimeout(() => {
          this.activeCalls.delete(callData.id);
        }, 10000);
        break;
    }
  }

  handleStatusUpdate(statusData) {
    // Actualizar información de la mesa si es necesario
    if (statusData.table_id == this.tableData.table.id) {
      this.tableData.table.can_call_waiter = statusData.notifications_enabled && 
                                            !!statusData.active_waiter_id;
      this.setupCallWaiterButton();
    }
  }

  handlePollingUpdate(data) {
    // Manejar actualizaciones del polling
    if (data.active_call) {
      const call = data.active_call;
      
      if (call.status === 'pending' && !this.modal.isVisible) {
        this.modal.showCalling();
      } else if (call.status === 'acknowledged') {
        this.modal.showAcknowledged();
      }
    } else {
      // No hay llamadas activas, detener polling
      pollingService.stopTableStatusPolling(this.tableData.table.id);
      if (this.modal.isVisible && this.modal.currentStep < 3) {
        this.modal.showCompleted();
      }
    }
  }

  async callWaiter() {
    if (this.isLoading || !this.tableData.table.can_call_waiter) return;

    this.isLoading = true;
    const button = document.getElementById('call-waiter-btn');
    if (button) {
      button.disabled = true;
      button.textContent = 'Llamando...';
    }

    try {
      const response = await apiClient.callWaiter(this.tableData.table.id, {
        message: 'Solicitud de atención desde página QR',
        urgency: 'normal'
      });

      if (!response.success) {
        throw new Error(response.message || 'Error al llamar al mozo');
      }

      // Si no usamos Firebase, iniciar polling
      if (!this.useFirebase) {
        this.modal.showCalling();
        pollingService.startTableStatusPolling(
          this.tableData.table.id,
          (event, data) => {
            if (event === 'status_update') {
              this.handlePollingUpdate(data);
            }
          }
        );
      }
      // Si usamos Firebase, el modal se mostrará automáticamente via listener

    } catch (error) {
      console.error('❌ Error calling waiter:', error);
      this.modal.showError(error.message || 'Error de conexión. Intente nuevamente.');
    } finally {
      this.isLoading = false;
      if (button) {
        button.disabled = false;
        button.textContent = '🔔 Llamar Mozo';
      }
    }
  }

  showLoading(message) {
    // Implementar indicador de carga
    const existingLoader = document.getElementById('page-loader');
    if (existingLoader) {
      existingLoader.remove();
    }

    const loader = document.createElement('div');
    loader.id = 'page-loader';
    loader.innerHTML = `
      <div class="loader-content">
        <div class="spinner"></div>
        <p>${message}</p>
      </div>
    `;
    document.body.appendChild(loader);
  }

  hideLoading() {
    const loader = document.getElementById('page-loader');
    if (loader) {
      loader.remove();
    }
  }

  showError(message) {
    const container = document.getElementById('table-info') || document.body;
    container.innerHTML = `
      <div class="error-container">
        <h2>❌ Error</h2>
        <p>${message}</p>
        <button onclick="window.location.reload()" class="retry-btn">
          🔄 Intentar nuevamente
        </button>
      </div>
    `;
    this.hideLoading();
  }

  // Cleanup al salir de la página
  cleanup() {
    console.log('🧹 Cleaning up QR Table Page...');
    realtimeService.cleanup();
    pollingService.cleanup();
    this.modal.hide();
  }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
  const qrPage = new QRTablePage();
  
  // Cleanup al salir de la página
  window.addEventListener('beforeunload', () => {
    qrPage.cleanup();
  });
});
```

### 8. Estilos CSS (styles.css)

```css
/* Variables CSS */
:root {
  --primary-color: #4CAF50;
  --secondary-color: #ff9500;
  --error-color: #dc3545;
  --success-color: #28a745;
  --info-color: #007bff;
  --text-dark: #333;
  --text-light: #666;
  --border-color: #e0e0e0;
  --shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Reset y base */
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  line-height: 1.6;
  color: var(--text-dark);
  background-color: #f8f9fa;
}

.container {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
}

/* Header de la mesa */
.table-header {
  text-align: center;
  padding: 2rem 0;
  background: white;
  border-radius: 12px;
  box-shadow: var(--shadow);
  margin-bottom: 2rem;
}

.business-logo {
  width: 100px;
  height: 100px;
  object-fit: contain;
  border-radius: 50%;
  margin-bottom: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.table-header h1 {
  font-size: 2.5rem;
  margin-bottom: 0.5rem;
  color: var(--primary-color);
}

.table-info {
  font-size: 1.2rem;
  color: var(--text-light);
  margin-bottom: 0.5rem;
}

.address {
  color: var(--text-light);
  font-size: 0.9rem;
}

.waiter-info {
  margin-top: 1rem;
  padding: 0.8rem;
  background: #e8f5e8;
  border-radius: 8px;
  color: var(--primary-color);
  font-weight: 500;
}

/* Sección del menú */
.menu-section {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: var(--shadow);
}

.menu-section h2 {
  margin-bottom: 1.5rem;
  color: var(--text-dark);
  text-align: center;
}

.menu-container {
  position: relative;
}

.menu-pdf {
  width: 100%;
  height: 600px;
  border: none;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.menu-actions {
  text-align: center;
  margin-top: 1rem;
}

.download-btn {
  display: inline-block;
  padding: 0.8rem 1.5rem;
  background: var(--info-color);
  color: white;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.3s ease;
}

.download-btn:hover {
  background: #0056b3;
  transform: translateY(-2px);
}

/* Sección de llamada */
.call-section {
  text-align: center;
  padding: 2rem;
  background: white;
  border-radius: 12px;
  box-shadow: var(--shadow);
}

.call-waiter-btn {
  background: var(--primary-color);
  color: white;
  border: none;
  padding: 1.2rem 3rem;
  font-size: 1.3rem;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
  margin-bottom: 1rem;
}

.call-waiter-btn:hover:not(:disabled) {
  background: #45a049;
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
}

.call-waiter-btn:disabled {
  background: #ccc;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.call-info {
  color: var(--text-light);
  font-size: 0.9rem;
  margin-top: 0.5rem;
}

.call-disabled {
  padding: 2rem;
  text-align: center;
  color: var(--text-light);
}

.call-disabled .reason {
  font-size: 0.9rem;
  margin-top: 0.5rem;
}

/* Modal de llamada */
.waiter-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(3px);
}

.waiter-modal-content {
  background: white;
  border-radius: 16px;
  padding: 2.5rem;
  max-width: 450px;
  width: 90%;
  text-align: center;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
  transform: scale(0.9);
  animation: modalAppear 0.3s ease forwards;
}

@keyframes modalAppear {
  to {
    transform: scale(1);
  }
}

.modal-header h3 {
  margin-bottom: 1.5rem;
  font-size: 1.8rem;
  color: var(--text-dark);
}

.status-icon i {
  font-size: 4rem;
  margin-bottom: 1.5rem;
  display: block;
}

.modal-body p {
  font-size: 1.1rem;
  color: var(--text-light);
  margin-bottom: 2rem;
  line-height: 1.5;
}

.progress-steps {
  display: flex;
  justify-content: space-between;
  margin: 2rem 0;
  padding: 0 1rem;
}

.step {
  flex: 1;
  opacity: 0.3;
  transition: opacity 0.3s ease;
  font-size: 0.9rem;
  text-align: center;
}

.step.active {
  opacity: 1;
  font-weight: bold;
}

.btn-close {
  background: var(--primary-color);
  color: white;
  border: none;
  padding: 0.8rem 2rem;
  font-size: 1rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-close:hover {
  background: #45a049;
}

/* Loading spinner */
#page-loader {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(255, 255, 255, 0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}

.loader-content {
  text-align: center;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Error container */
.error-container {
  text-align: center;
  padding: 3rem 2rem;
  background: white;
  border-radius: 12px;
  box-shadow: var(--shadow);
}

.error-container h2 {
  color: var(--error-color);
  margin-bottom: 1rem;
}

.retry-btn {
  background: var(--primary-color);
  color: white;
  border: none;
  padding: 0.8rem 2rem;
  border-radius: 8px;
  cursor: pointer;
  font-size: 1rem;
  margin-top: 1rem;
  transition: background 0.3s ease;
}

.retry-btn:hover {
  background: #45a049;
}

/* Responsive design */
@media (max-width: 768px) {
  .container {
    padding: 10px;
  }
  
  .table-header {
    padding: 1.5rem;
  }
  
  .table-header h1 {
    font-size: 2rem;
  }
  
  .menu-pdf {
    height: 400px;
  }
  
  .call-waiter-btn {
    padding: 1rem 2rem;
    font-size: 1.1rem;
    width: 100%;
  }
  
  .waiter-modal-content {
    padding: 2rem;
    width: 95%;
  }
  
  .progress-steps {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .step {
    text-align: center;
  }
}

/* Animaciones adicionales */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

.fa-spin {
  animation: fa-spin 2s infinite linear;
}

@keyframes fa-spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(359deg);
  }
}

/* Estados de conexión */
.connection-status {
  position: fixed;
  top: 10px;
  right: 10px;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: bold;
  z-index: 999;
}

.connection-status.firebase {
  background: #e8f5e8;
  color: var(--success-color);
}

.connection-status.polling {
  background: #fff3cd;
  color: #856404;
}

.connection-status.error {
  background: #f8d7da;
  color: var(--error-color);
}
```

### 9. HTML Principal

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MozoQR - Mesa</title>
    
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Estilos -->
    <link rel="stylesheet" href="/css/styles.css">
    
    <!-- Meta tags para compartir -->
    <meta property="og:title" content="MozoQR - Llamar Mozo">
    <meta property="og:description" content="Escanea el QR y llama a tu mozo al instante">
    <meta property="og:type" content="website">
    
    <!-- PWA Meta tags -->
    <meta name="theme-color" content="#4CAF50">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
</head>
<body>
    <div class="container">
        <!-- El contenido se cargará dinámicamente -->
        <div id="table-info">
            <!-- Contenido de la mesa se renderizará aquí -->
        </div>
    </div>

    <!-- Scripts -->
    <script type="module" src="/js/pages/QRTablePage.js"></script>
</body>
</html>
```

## 🧪 TESTING Y VALIDACIÓN

### 1. Probar Firebase Real-time

```javascript
// En consola del navegador
console.log('Firebase enabled:', realtimeService.isFirebaseEnabled());

// Simular llamada de mozo desde backend
// POST /api/tables/5/call-waiter
```

### 2. Probar Fallback Polling

```javascript
// Deshabilitar Firebase temporalmente
realtimeService.disable();

// Llamar al mozo - debería usar polling
```

### 3. Probar Estados del Modal

```javascript
// En consola
const modal = new WaiterCallModal();
modal.showCalling();        // Estado: Llamando
modal.showAcknowledged();   // Estado: Confirmado  
modal.showCompleted();      // Estado: Completado
```

## 🚀 DESPLIEGUE

1. **Configurar variables de entorno** en el backend
2. **Subir archivos** a mozoqr.com
3. **Configurar Firebase** en el proyecto
4. **Probar URLs QR** completas
5. **Monitorear logs** de Firebase y errores

## ✅ CHECKLIST FINAL

- ✅ Backend Firebase integration implementado
- ✅ PublicQrController mejorado
- ✅ Firebase configuration endpoint
- ✅ Real-time service con fallback
- ✅ Modal de llamada interactivo
- ✅ Responsive design
- ✅ Error handling robusto
- ✅ Testing guidelines
- ✅ Documentación completa

Con esta implementación, el sistema funcionará de la siguiente manera:

1. **Cliente escanea QR** → Se abre `mozoqr.com/QR/restaurant/table123`
2. **Frontend carga información** → API `/api/qr/{restaurant}/{table123}`
3. **Se inicializa Firebase** → Conexión en tiempo real
4. **Cliente presiona "Llamar Mozo"** → POST `/api/tables/5/call-waiter`
5. **Backend escribe a Firestore** → Evento en tiempo real
6. **Frontend recibe evento** → Modal se actualiza automáticamente
7. **Mozo confirma** → Nuevo evento → Modal cambia a "En camino"
8. **Servicio completado** → Modal muestra "Completado" y se cierra

El sistema tiene **fallback automático** a polling si Firebase falla, garantizando que siempre funcione.