// 🔥 FIREBASE REAL-TIME FRONTEND - LIMPIO Y SIMPLE
// =====================================================

import { initializeApp } from 'firebase/app';
import { getFirestore, collection, onSnapshot, query, orderBy } from 'firebase/firestore';

// ✅ 1. CONFIGURACIÓN FIREBASE
const firebaseConfig = {
    projectId: "mozoqr-7d32c",
    apiKey: "AIzaSyDGJJKNfSSxD6YnXnNjwRb6VUtPSyGN5CM",
    authDomain: "mozoqr-7d32c.firebaseapp.com",
    storageBucket: "mozoqr-7d32c.appspot.com",
    messagingSenderId: "123456789",
    appId: "1:123456789:web:abcdef"
};

const app = initializeApp(firebaseConfig);
const db = getFirestore(app);

// ✅ 2. CLASE PRINCIPAL PARA NOTIFICACIONES
class WaiterRealtimeNotifications {
    constructor(waiterId) {
        this.waiterId = waiterId;
        this.unsubscribe = null;
        this.activeCalls = new Map();
    }

    // 🎧 INICIAR ESCUCHA DE NOTIFICACIONES
    startListening() {
        console.log(`🔥 Iniciando listener para mozo ${this.waiterId}`);
        
        // Escuchar colección: waiters/{waiterId}/calls
        const callsRef = collection(db, 'waiters', this.waiterId, 'calls');
        const q = query(callsRef, orderBy('timestamp', 'desc'));

        this.unsubscribe = onSnapshot(q, 
            (snapshot) => this.handleSnapshot(snapshot),
            (error) => this.handleError(error)
        );
    }

    // 📸 MANEJAR CAMBIOS EN TIEMPO REAL
    handleSnapshot(snapshot) {
        console.log(`📸 Snapshot recibido: ${snapshot.size} documentos`);
        
        snapshot.docChanges().forEach((change) => {
            const callData = change.doc.data();
            const callId = change.doc.id;
            
            switch(change.type) {
                case 'added':
                    this.onNewCall(callData);
                    break;
                case 'modified':
                    this.onCallUpdated(callData);
                    break;
                case 'removed':
                    this.onCallRemoved(callId);
                    break;
            }
        });
    }

    // 🆕 NUEVA LLAMADA RECIBIDA
    onNewCall(callData) {
        console.log('🔔 NUEVA LLAMADA:', callData);
        
        // Agregar a mapa local
        this.activeCalls.set(callData.id, callData);
        
        // 🔊 NOTIFICACIÓN DE SONIDO
        this.playNotificationSound();
        
        // 📳 VIBRACIÓN (móviles)
        this.vibrate();
        
        // 🖥️ NOTIFICACIÓN VISUAL
        this.showNotification(callData);
        
        // 🎨 ACTUALIZAR UI
        this.addCallToUI(callData);
        
        // 🔔 NOTIFICACIÓN DEL NAVEGADOR
        this.showBrowserNotification(callData);
    }

    // 🔄 LLAMADA ACTUALIZADA
    onCallUpdated(callData) {
        console.log('🔄 Llamada actualizada:', callData);
        this.activeCalls.set(callData.id, callData);
        this.updateCallInUI(callData);
    }

    // ❌ LLAMADA ELIMINADA (completada)
    onCallRemoved(callId) {
        console.log('✅ Llamada completada:', callId);
        this.activeCalls.delete(callId);
        this.removeCallFromUI(callId);
    }

    // 🔊 REPRODUCIR SONIDO
    playNotificationSound() {
        try {
            const audio = new Audio('/sounds/notification.mp3');
            audio.volume = 0.7;
            audio.play().catch(e => console.log('Audio bloqueado por navegador'));
        } catch (e) {
            console.log('Error reproduciendo sonido:', e);
        }
    }

    // 📳 VIBRACIÓN
    vibrate() {
        if (navigator.vibrate) {
            navigator.vibrate([200, 100, 200, 100, 200]);
        }
    }

    // 🖥️ NOTIFICACIÓN VISUAL
    showNotification(callData) {
        const notification = document.createElement('div');
        notification.className = 'realtime-notification';
        notification.innerHTML = `
            <div class="notification-header">
                <h3>🔔 Mesa ${callData.table_number}</h3>
                <span class="urgency-${callData.urgency}">${callData.urgency}</span>
            </div>
            <div class="notification-body">
                <p>${callData.message}</p>
                <small>${new Date(callData.called_at).toLocaleTimeString()}</small>
            </div>
            <div class="notification-actions">
                <button onclick="waiterNotifications.acknowledgeCall('${callData.id}')" 
                        class="btn-acknowledge">Atender</button>
                <button onclick="this.parentElement.parentElement.remove()" 
                        class="btn-dismiss">Cerrar</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove después de 10 segundos
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 10000);
    }

    // 🔔 NOTIFICACIÓN DEL NAVEGADOR
    showBrowserNotification(callData) {
        if (Notification.permission === 'granted') {
            const notification = new Notification(`Mesa ${callData.table_number}`, {
                body: callData.message,
                icon: '/icons/waiter-icon.png',
                tag: `call-${callData.id}`,
                requireInteraction: true
            });
            
            notification.onclick = () => {
                window.focus();
                notification.close();
            };
        }
    }

    // 🎨 AGREGAR LLAMADA A LA UI
    addCallToUI(callData) {
        const container = document.getElementById('calls-container');
        if (!container) return;
        
        const callElement = document.createElement('div');
        callElement.id = `call-${callData.id}`;
        callElement.className = `call-item urgency-${callData.urgency}`;
        callElement.innerHTML = `
            <div class="call-header">
                <h4>Mesa ${callData.table_number}</h4>
                <span class="call-time">${new Date(callData.called_at).toLocaleTimeString()}</span>
            </div>
            <div class="call-message">${callData.message}</div>
            <div class="call-actions">
                <button onclick="waiterNotifications.acknowledgeCall('${callData.id}')" 
                        class="btn-acknowledge">✅ Atender</button>
                <button onclick="waiterNotifications.completeCall('${callData.id}')" 
                        class="btn-complete">✅ Completar</button>
            </div>
        `;
        
        container.prepend(callElement);
        
        // Animación de entrada
        setTimeout(() => callElement.classList.add('show'), 100);
    }

    // 🔄 ACTUALIZAR LLAMADA EN UI
    updateCallInUI(callData) {
        const element = document.getElementById(`call-${callData.id}`);
        if (element) {
            element.className = `call-item urgency-${callData.urgency} status-${callData.status}`;
        }
    }

    // ❌ ELIMINAR LLAMADA DE UI
    removeCallFromUI(callId) {
        const element = document.getElementById(`call-${callId}`);
        if (element) {
            element.classList.add('removing');
            setTimeout(() => element.remove(), 300);
        }
    }

    // ✅ RECONOCER LLAMADA
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
                console.log('✅ Llamada reconocida');
            }
        } catch (error) {
            console.error('Error reconociendo llamada:', error);
        }
    }

    // ✅ COMPLETAR LLAMADA
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
                console.log('✅ Llamada completada');
            }
        } catch (error) {
            console.error('Error completando llamada:', error);
        }
    }

    // 🛑 DETENER ESCUCHA
    stopListening() {
        if (this.unsubscribe) {
            this.unsubscribe();
            this.unsubscribe = null;
            console.log('🛑 Listener detenido');
        }
    }

    // ❌ MANEJAR ERRORES
    handleError(error) {
        console.error('🚨 Error en Firebase listener:', error);
        
        // Mostrar error en UI
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-notification';
        errorDiv.innerHTML = `
            <p>❌ Error de conexión en tiempo real</p>
            <button onclick="this.remove()">Cerrar</button>
        `;
        document.body.appendChild(errorDiv);
    }
}

// ✅ 3. INICIALIZACIÓN AUTOMÁTICA
let waiterNotifications = null;

// Función para inicializar desde tu app
function initializeWaiterNotifications(waiterId) {
    // Solicitar permisos de notificación
    if (Notification.permission === 'default') {
        Notification.requestPermission();
    }
    
    // Crear instancia
    waiterNotifications = new WaiterRealtimeNotifications(waiterId);
    waiterNotifications.startListening();
    
    console.log('🔥 Notificaciones en tiempo real inicializadas');
    
    return waiterNotifications;
}

// Cleanup al salir
window.addEventListener('beforeunload', () => {
    if (waiterNotifications) {
        waiterNotifications.stopListening();
    }
});

// ✅ 4. ESTILOS CSS
const styles = `
.realtime-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border: 2px solid #ff6b6b;
    border-radius: 8px;
    padding: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    z-index: 10000;
    max-width: 300px;
    animation: slideIn 0.3s ease;
}

.call-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 12px;
    margin: 8px 0;
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s ease;
}

.call-item.show {
    opacity: 1;
    transform: translateY(0);
}

.call-item.urgency-high {
    border-color: #ff6b6b;
    background: #fff5f5;
}

.call-item.removing {
    opacity: 0;
    transform: translateX(-100%);
}

.btn-acknowledge {
    background: #4ecdc4;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 8px;
}

.btn-complete {
    background: #45b7d1;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.error-notification {
    position: fixed;
    top: 20px;
    left: 20px;
    background: #ff6b6b;
    color: white;
    padding: 16px;
    border-radius: 8px;
    z-index: 10000;
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
`;

// Inyectar estilos
const styleSheet = document.createElement('style');
styleSheet.textContent = styles;
document.head.appendChild(styleSheet);

// ✅ 5. EXPORTAR PARA USO GLOBAL
window.initializeWaiterNotifications = initializeWaiterNotifications;
window.waiterNotifications = waiterNotifications;

export { initializeWaiterNotifications, WaiterRealtimeNotifications };