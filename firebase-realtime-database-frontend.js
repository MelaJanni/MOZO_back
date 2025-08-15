// 🚀 FIREBASE REALTIME DATABASE - ULTRA FAST FRONTEND
// =====================================================

import { initializeApp } from 'firebase/app';
import { getDatabase, ref, onValue, off } from 'firebase/database';

// ✅ 1. CONFIGURACIÓN FIREBASE (MISMO PROYECTO)
const firebaseConfig = {
    projectId: "mozoqr-7d32c",
    apiKey: "AIzaSyDGJJKNfSSxD6YnXnNjwRb6VUtPSyGN5CM",
    authDomain: "mozoqr-7d32c.firebaseapp.com",
    databaseURL: "https://mozoqr-7d32c-default-rtdb.firebaseio.com", // ⚡ CLAVE ULTRA RÁPIDA
    storageBucket: "mozoqr-7d32c.appspot.com",
    messagingSenderId: "123456789",
    appId: "1:123456789:web:abcdef"
};

const app = initializeApp(firebaseConfig);
const database = getDatabase(app); // ⚡ REALTIME DATABASE

// ✅ 2. CLASE ULTRA RÁPIDA PARA NOTIFICACIONES
class UltraFastWaiterNotifications {
    constructor(waiterId) {
        this.waiterId = waiterId;
        this.listeners = new Map();
        this.activeCalls = new Map();
        console.log(`⚡ ULTRA FAST listener inicializado para mozo ${waiterId}`);
    }

    // 🎧 INICIAR ESCUCHA ULTRA RÁPIDA
    startListening() {
        console.log(`⚡ Iniciando ULTRA FAST listener para mozo ${this.waiterId}`);
        
        // ⚡ ESCUCHAR: /waiters/{waiterId}/calls (ULTRA RÁPIDO)
        const callsRef = ref(database, `waiters/${this.waiterId}/calls`);

        const unsubscribe = onValue(callsRef, 
            (snapshot) => this.handleSnapshot(snapshot),
            (error) => this.handleError(error)
        );

        this.listeners.set('main', unsubscribe);
        console.log(`⚡ ULTRA FAST WebSocket conectado`);
    }

    // 📸 MANEJAR CAMBIOS ULTRA RÁPIDOS
    handleSnapshot(snapshot) {
        const data = snapshot.val();
        console.log(`⚡ ULTRA FAST snapshot recibido:`, data);
        
        if (!data) {
            // No hay llamadas - limpiar UI
            this.clearAllCalls();
            return;
        }

        const currentCalls = new Set();

        // Procesar todas las llamadas
        Object.entries(data).forEach(([callId, callData]) => {
            currentCalls.add(callId);
            
            if (!this.activeCalls.has(callId)) {
                // ⚡ NUEVA LLAMADA DETECTADA
                this.onNewCall(callData);
            } else {
                // ⚡ LLAMADA ACTUALIZADA
                const oldCall = this.activeCalls.get(callId);
                if (oldCall.status !== callData.status) {
                    this.onCallUpdated(callData);
                }
            }
            
            this.activeCalls.set(callId, callData);
        });

        // Detectar llamadas eliminadas
        this.activeCalls.forEach((callData, callId) => {
            if (!currentCalls.has(callId)) {
                this.onCallRemoved(callId);
                this.activeCalls.delete(callId);
            }
        });
    }

    // 🆕 NUEVA LLAMADA (ULTRA RÁPIDA)
    onNewCall(callData) {
        console.log('⚡ NUEVA LLAMADA ULTRA RÁPIDA:', callData);
        
        // 🔊 NOTIFICACIÓN INMEDIATA
        this.playUltraFastSound();
        
        // 📳 VIBRACIÓN INMEDIATA
        this.vibrateUltraFast();
        
        // 🖥️ NOTIFICACIÓN VISUAL INMEDIATA
        this.showUltraFastNotification(callData);
        
        // 🎨 UI INMEDIATA
        this.addCallToUI(callData);
        
        // 🔔 BROWSER NOTIFICATION INMEDIATA
        this.showBrowserNotification(callData);
    }

    // 🔄 LLAMADA ACTUALIZADA (ULTRA RÁPIDA)
    onCallUpdated(callData) {
        console.log('⚡ Llamada actualizada ULTRA RÁPIDA:', callData);
        this.updateCallInUI(callData);
        
        if (callData.status === 'acknowledged') {
            this.showStatusUpdate(`Mesa ${callData.table_number} - Atendiendo`);
        }
    }

    // ❌ LLAMADA ELIMINADA (ULTRA RÁPIDA)
    onCallRemoved(callId) {
        console.log('⚡ Llamada completada ULTRA RÁPIDA:', callId);
        this.removeCallFromUI(callId);
        this.showStatusUpdate('Servicio completado');
    }

    // 🔊 SONIDO ULTRA RÁPIDO
    playUltraFastSound() {
        try {
            // Usar Web Audio API para menor latencia
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
            oscillator.frequency.setValueAtTime(400, audioContext.currentTime + 0.1);
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        } catch (e) {
            // Fallback a audio file
            const audio = new Audio('/sounds/notification.mp3');
            audio.volume = 0.7;
            audio.play().catch(e => console.log('Audio bloqueado'));
        }
    }

    // 📳 VIBRACIÓN ULTRA RÁPIDA
    vibrateUltraFast() {
        if (navigator.vibrate) {
            navigator.vibrate([100, 50, 100, 50, 200]); // Patrón rápido
        }
    }

    // 🖥️ NOTIFICACIÓN VISUAL ULTRA RÁPIDA
    showUltraFastNotification(callData) {
        const notification = document.createElement('div');
        notification.className = 'ultra-fast-notification';
        notification.innerHTML = `
            <div class="notification-header">
                <h3>⚡ Mesa ${callData.table_number}</h3>
                <span class="urgency-${callData.urgency}">${callData.urgency}</span>
                <span class="ultra-fast-badge">ULTRA FAST</span>
            </div>
            <div class="notification-body">
                <p>${callData.message}</p>
                <small>⚡ ${new Date(callData.called_at).toLocaleTimeString()}</small>
            </div>
            <div class="notification-actions">
                <button onclick="ultraFastNotifications.acknowledgeCall('${callData.id}')" 
                        class="btn-acknowledge-fast">⚡ Atender</button>
                <button onclick="this.parentElement.parentElement.remove()" 
                        class="btn-dismiss">✕</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animación ultra rápida
        requestAnimationFrame(() => {
            notification.classList.add('show');
        });
        
        // Auto-remove después de 8 segundos
        setTimeout(() => {
            if (notification.parentElement) {
                notification.classList.add('hiding');
                setTimeout(() => notification.remove(), 200);
            }
        }, 8000);
    }

    // 🔔 NOTIFICACIÓN BROWSER ULTRA RÁPIDA
    showBrowserNotification(callData) {
        if (Notification.permission === 'granted') {
            const notification = new Notification(`⚡ Mesa ${callData.table_number}`, {
                body: `${callData.message} (ULTRA FAST)`,
                icon: '/icons/waiter-icon.png',
                tag: `ultra-fast-call-${callData.id}`,
                requireInteraction: true,
                badge: '/icons/badge.png'
            });
            
            notification.onclick = () => {
                window.focus();
                notification.close();
                // Scroll to call in UI
                const callElement = document.getElementById(`call-${callData.id}`);
                if (callElement) {
                    callElement.scrollIntoView({ behavior: 'smooth' });
                    callElement.classList.add('highlight');
                }
            };
        }
    }

    // 🎨 AGREGAR LLAMADA A UI (ULTRA RÁPIDA)
    addCallToUI(callData) {
        const container = document.getElementById('calls-container') || document.body;
        
        const callElement = document.createElement('div');
        callElement.id = `call-${callData.id}`;
        callElement.className = `ultra-fast-call-item urgency-${callData.urgency}`;
        callElement.innerHTML = `
            <div class="call-header">
                <h4>⚡ Mesa ${callData.table_number}</h4>
                <span class="call-time">${new Date(callData.called_at).toLocaleTimeString()}</span>
                <span class="ultra-fast-badge">ULTRA FAST</span>
            </div>
            <div class="call-message">${callData.message}</div>
            <div class="call-actions">
                <button onclick="ultraFastNotifications.acknowledgeCall('${callData.id}')" 
                        class="btn-acknowledge-ultra">⚡ Atender</button>
                <button onclick="ultraFastNotifications.completeCall('${callData.id}')" 
                        class="btn-complete-ultra">✅ Completar</button>
            </div>
        `;
        
        container.prepend(callElement);
        
        // Animación ultra rápida
        requestAnimationFrame(() => {
            callElement.classList.add('show');
        });
    }

    // 🔄 ACTUALIZAR LLAMADA EN UI
    updateCallInUI(callData) {
        const element = document.getElementById(`call-${callData.id}`);
        if (element) {
            element.className = `ultra-fast-call-item urgency-${callData.urgency} status-${callData.status}`;
            
            // Update status indicator
            let statusIndicator = element.querySelector('.status-indicator');
            if (!statusIndicator) {
                statusIndicator = document.createElement('div');
                statusIndicator.className = 'status-indicator';
                element.querySelector('.call-header').appendChild(statusIndicator);
            }
            statusIndicator.textContent = callData.status;
        }
    }

    // ❌ ELIMINAR LLAMADA DE UI
    removeCallFromUI(callId) {
        const element = document.getElementById(`call-${callId}`);
        if (element) {
            element.classList.add('removing');
            setTimeout(() => element.remove(), 150); // Ultra rápido
        }
    }

    // 📢 MOSTRAR UPDATE DE STATUS
    showStatusUpdate(message) {
        const statusDiv = document.createElement('div');
        statusDiv.className = 'status-update';
        statusDiv.textContent = `⚡ ${message}`;
        document.body.appendChild(statusDiv);
        
        setTimeout(() => statusDiv.classList.add('show'), 10);
        setTimeout(() => {
            statusDiv.classList.remove('show');
            setTimeout(() => statusDiv.remove(), 200);
        }, 2000);
    }

    // 🧹 LIMPIAR TODAS LAS LLAMADAS
    clearAllCalls() {
        const container = document.getElementById('calls-container');
        if (container) {
            container.innerHTML = '<p class="no-calls">⚡ No hay llamadas pendientes</p>';
        }
        this.activeCalls.clear();
    }

    // ✅ RECONOCER LLAMADA (ULTRA RÁPIDO)
    async acknowledgeCall(callId) {
        try {
            const response = await fetch(`/api/waiter/calls/${callId}/acknowledge`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                    'Content-Type': 'application/json'
                }
            });
            
            if (response.ok) {
                console.log('⚡ Llamada reconocida ULTRA RÁPIDO');
            }
        } catch (error) {
            console.error('Error reconociendo llamada:', error);
        }
    }

    // ✅ COMPLETAR LLAMADA (ULTRA RÁPIDO)
    async completeCall(callId) {
        try {
            const response = await fetch(`/api/waiter/calls/${callId}/complete`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                    'Content-Type': 'application/json'
                }
            });
            
            if (response.ok) {
                console.log('⚡ Llamada completada ULTRA RÁPIDO');
            }
        } catch (error) {
            console.error('Error completando llamada:', error);
        }
    }

    // 🛑 DETENER ESCUCHA
    stopListening() {
        this.listeners.forEach((unsubscribe, key) => {
            const callsRef = ref(database, `waiters/${this.waiterId}/calls`);
            off(callsRef);
        });
        this.listeners.clear();
        console.log('🛑 ULTRA FAST listener detenido');
    }

    // ❌ MANEJAR ERRORES
    handleError(error) {
        console.error('🚨 Error en ULTRA FAST listener:', error);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'ultra-fast-error';
        errorDiv.innerHTML = `
            <p>❌ Error de conexión ULTRA FAST</p>
            <p>Reintentando automáticamente...</p>
            <button onclick="this.remove()">Cerrar</button>
        `;
        document.body.appendChild(errorDiv);
        
        // Auto-retry después de 2 segundos
        setTimeout(() => {
            this.startListening();
            errorDiv.remove();
        }, 2000);
    }
}

// ✅ 3. INICIALIZACIÓN GLOBAL
let ultraFastNotifications = null;

// Función para inicializar desde tu app
function initializeUltraFastWaiterNotifications(waiterId) {
    // Solicitar permisos de notificación
    if (Notification.permission === 'default') {
        Notification.requestPermission();
    }
    
    // Crear instancia ULTRA RÁPIDA
    ultraFastNotifications = new UltraFastWaiterNotifications(waiterId);
    ultraFastNotifications.startListening();
    
    console.log('⚡ ULTRA FAST notifications inicializadas');
    
    return ultraFastNotifications;
}

// Cleanup al salir
window.addEventListener('beforeunload', () => {
    if (ultraFastNotifications) {
        ultraFastNotifications.stopListening();
    }
});

// ✅ 4. ESTILOS CSS ULTRA RÁPIDOS
const ultraFastStyles = `
.ultra-fast-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: linear-gradient(135deg, #ff6b6b, #ee5a24);
    color: white;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 8px 25px rgba(238, 90, 36, 0.3);
    z-index: 10000;
    max-width: 350px;
    transform: translateX(400px);
    opacity: 0;
    transition: all 0.2s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.ultra-fast-notification.show {
    transform: translateX(0);
    opacity: 1;
}

.ultra-fast-notification.hiding {
    transform: translateX(400px);
    opacity: 0;
}

.ultra-fast-call-item {
    border: 2px solid #ff6b6b;
    border-radius: 12px;
    padding: 16px;
    margin: 8px 0;
    background: linear-gradient(135deg, #fff, #f8f9fa);
    opacity: 0;
    transform: translateY(30px) scale(0.95);
    transition: all 0.2s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    box-shadow: 0 4px 15px rgba(255, 107, 107, 0.1);
}

.ultra-fast-call-item.show {
    opacity: 1;
    transform: translateY(0) scale(1);
}

.ultra-fast-call-item.removing {
    opacity: 0;
    transform: translateX(-100%) scale(0.8);
}

.ultra-fast-call-item.urgency-high {
    border-color: #e74c3c;
    background: linear-gradient(135deg, #fff5f5, #fdedec);
    animation: pulse-ultra 1s infinite;
}

.ultra-fast-badge {
    background: linear-gradient(45deg, #00d2ff, #3a7bd5);
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: bold;
    text-transform: uppercase;
}

.btn-acknowledge-ultra, .btn-acknowledge-fast {
    background: linear-gradient(135deg, #00d2ff, #3a7bd5);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 25px;
    cursor: pointer;
    margin-right: 10px;
    font-weight: bold;
    transition: all 0.2s ease;
}

.btn-acknowledge-ultra:hover, .btn-acknowledge-fast:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 210, 255, 0.3);
}

.btn-complete-ultra {
    background: linear-gradient(135deg, #55a3ff, #3a7bd5);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 25px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.2s ease;
}

.btn-complete-ultra:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(85, 163, 255, 0.3);
}

.ultra-fast-error {
    position: fixed;
    top: 20px;
    left: 20px;
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    padding: 16px;
    border-radius: 12px;
    z-index: 10000;
    box-shadow: 0 8px 25px rgba(231, 76, 60, 0.3);
}

.status-update {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: linear-gradient(135deg, #2ecc71, #27ae60);
    color: white;
    padding: 12px 24px;
    border-radius: 25px;
    z-index: 9999;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s ease;
}

.status-update.show {
    transform: translateY(0);
    opacity: 1;
}

.status-indicator {
    background: #3a7bd5;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
    text-transform: uppercase;
}

.no-calls {
    text-align: center;
    color: #7f8c8d;
    font-style: italic;
    padding: 40px;
}

.highlight {
    animation: highlight-flash 2s ease;
}

@keyframes pulse-ultra {
    0%, 100% { 
        box-shadow: 0 4px 15px rgba(255, 107, 107, 0.1);
    }
    50% { 
        box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
        transform: scale(1.02);
    }
}

@keyframes highlight-flash {
    0%, 100% { background-color: transparent; }
    50% { background-color: rgba(255, 235, 59, 0.3); }
}
`;

// Inyectar estilos ULTRA RÁPIDOS
const ultraFastStyleSheet = document.createElement('style');
ultraFastStyleSheet.textContent = ultraFastStyles;
document.head.appendChild(ultraFastStyleSheet);

// ✅ 5. EXPORT PARA USO GLOBAL
window.initializeUltraFastWaiterNotifications = initializeUltraFastWaiterNotifications;
window.ultraFastNotifications = ultraFastNotifications;

export { initializeUltraFastWaiterNotifications, UltraFastWaiterNotifications };

// 🚀 INSTRUCCIONES DE USO:
/*

## INSTRUCCIONES PARA TU FRONTEND:

### 1. Incluir el archivo:
```html
<script type="module" src="firebase-realtime-database-frontend.js"></script>
```

### 2. Inicializar en tu dashboard:
```javascript
// Cuando el mozo hace login
const waiterId = getCurrentWaiterId(); // Tu función
initializeUltraFastWaiterNotifications(waiterId);
```

### 3. HTML mínimo necesario:
```html
<div id="calls-container"></div>
```

### 4. Verificar configuración Firebase:
- databaseURL: "https://mozoqr-7d32c-default-rtdb.firebaseio.com"
- Debe estar habilitado Realtime Database en Firebase Console

### 5. Permisos de notificación:
El script automáticamente pide permisos de notificación del navegador.

¡ULTRA RÁPIDO y listo para usar! ⚡

*/