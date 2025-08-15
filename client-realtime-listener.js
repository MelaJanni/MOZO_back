// 🔥 FIREBASE REALTIME LISTENER PARA CLIENTE (PÁGINA QR)
// ================================================================

import { initializeApp } from 'firebase/app';
import { getDatabase, ref, onValue, off } from 'firebase/database';

// ✅ CONFIGURACIÓN FIREBASE
const firebaseConfig = {
    projectId: "mozoqr-7d32c",
    apiKey: "AIzaSyDGJJKNfSSxD6YnXnNjwRb6VUtPSyGN5CM",
    authDomain: "mozoqr-7d32c.firebaseapp.com",
    databaseURL: "https://mozoqr-7d32c-default-rtdb.firebaseio.com",
    storageBucket: "mozoqr-7d32c.appspot.com",
    messagingSenderId: "123456789",
    appId: "1:123456789:web:abcdef"
};

const app = initializeApp(firebaseConfig);
const database = getDatabase(app);

// ✅ CLASE PARA ESCUCHAR ACTUALIZACIONES DEL MOZO
class ClientCallStatusListener {
    constructor(tableId) {
        this.tableId = tableId;
        this.listener = null;
        this.currentCallId = null;
        console.log(`🔥 Cliente escuchando mesa ${tableId}`);
    }

    // 🎧 INICIAR ESCUCHA DE CAMBIOS DE ESTADO
    startListening() {
        const statusRef = ref(database, `tables/${this.tableId}/call_status`);
        
        this.listener = onValue(statusRef, (snapshot) => {
            const data = snapshot.val();
            console.log('🔥 Estado actualizado:', data);
            
            if (data) {
                this.handleStatusUpdate(data);
            }
        });
        
        console.log(`⚡ Escuchando actualizaciones en tiempo real`);
    }

    // 📢 MANEJAR ACTUALIZACIONES DE ESTADO
    handleStatusUpdate(data) {
        const { status, message, waiter_name, call_id } = data;
        
        switch (status) {
            case 'acknowledged':
                this.showClientNotification(
                    '✅ Solicitud Recibida',
                    `${waiter_name} recibió tu solicitud`,
                    'success'
                );
                this.updateButtonStatus('acknowledged', 'Mozo notificado ✅');
                break;
                
            case 'completed':
                this.showClientNotification(
                    '🎉 Servicio Completado',
                    'Tu solicitud ha sido atendida',
                    'completed'
                );
                this.updateButtonStatus('completed', 'Servicio completado 🎉');
                this.resetAfterDelay();
                break;
        }

        // 🔔 NOTIFICACIÓN BROWSER SI ESTÁ EN BACKGROUND
        this.showBrowserNotification(message, status);
    }

    // 🖥️ MOSTRAR NOTIFICACIÓN VISUAL EN LA PÁGINA
    showClientNotification(title, message, type) {
        // Remover notificación anterior
        const existing = document.querySelector('.client-notification');
        if (existing) existing.remove();

        const notification = document.createElement('div');
        notification.className = `client-notification ${type}`;
        notification.innerHTML = `
            <div class="notification-header">
                <h3>${title}</h3>
                <button onclick="this.parentElement.parentElement.remove()">✕</button>
            </div>
            <p>${message}</p>
            <div class="notification-time">${new Date().toLocaleTimeString()}</div>
        `;

        document.body.appendChild(notification);
        
        // Mostrar con animación
        setTimeout(() => notification.classList.add('show'), 10);
        
        // Auto-remove después de 8 segundos
        setTimeout(() => {
            notification.classList.add('hiding');
            setTimeout(() => notification.remove(), 300);
        }, 8000);
    }

    // 🔔 NOTIFICACIÓN DEL NAVEGADOR (cuando en background)
    showBrowserNotification(message, status) {
        if (Notification.permission === 'granted') {
            const icon = status === 'completed' ? '🎉' : '✅';
            const notification = new Notification(`${icon} ${message}`, {
                body: `Mesa actualización - ${new Date().toLocaleTimeString()}`,
                icon: '/favicon.ico',
                tag: `table-${this.tableId}-status`,
                requireInteraction: status === 'completed'
            });
            
            notification.onclick = () => {
                window.focus();
                notification.close();
            };
        }
    }

    // 🔄 ACTUALIZAR ESTADO DEL BOTÓN
    updateButtonStatus(status, text) {
        const button = document.getElementById('callWaiterBtn');
        const statusMessage = document.getElementById('statusMessage');
        
        if (button) {
            button.disabled = true;
            button.textContent = text;
            button.className = `call-waiter-btn ${status}`;
        }
        
        if (statusMessage) {
            statusMessage.style.display = 'block';
            statusMessage.className = `status-message status-${status}`;
            statusMessage.textContent = text;
        }
    }

    // 🔄 RESETEAR DESPUÉS DE COMPLETADO
    resetAfterDelay() {
        setTimeout(() => {
            const button = document.getElementById('callWaiterBtn');
            const statusMessage = document.getElementById('statusMessage');
            
            if (button) {
                button.disabled = false;
                button.textContent = '📞 Llamar Mozo';
                button.className = 'call-waiter-btn';
            }
            
            if (statusMessage) {
                statusMessage.style.display = 'none';
            }
            
            // Limpiar datos de Firebase
            this.clearStatusData();
            
        }, 5000); // 5 segundos después de completado
    }

    // 🧹 LIMPIAR DATOS DE ESTADO
    clearStatusData() {
        // Solo notificar, no eliminar (lo hace el backend automáticamente)
        console.log('🧹 Estado limpiado, listo para nueva solicitud');
    }

    // 🛑 DETENER ESCUCHA
    stopListening() {
        if (this.listener) {
            const statusRef = ref(database, `tables/${this.tableId}/call_status`);
            off(statusRef, 'value', this.listener);
            this.listener = null;
            console.log('🛑 Listener detenido');
        }
    }
}

// ✅ FUNCIÓN GLOBAL DE INICIALIZACIÓN
function initializeClientStatusListener(tableId) {
    // Solicitar permisos de notificación
    if (Notification.permission === 'default') {
        Notification.requestPermission();
    }
    
    const listener = new ClientCallStatusListener(tableId);
    listener.startListening();
    
    // Cleanup al salir
    window.addEventListener('beforeunload', () => {
        listener.stopListening();
    });
    
    return listener;
}

// ✅ ESTILOS CSS PARA NOTIFICACIONES
const clientNotificationStyles = `
.client-notification {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%) translateY(-100px);
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    z-index: 10000;
    max-width: 400px;
    width: 90%;
    opacity: 0;
    transition: all 0.3s ease;
}

.client-notification.show {
    transform: translateX(-50%) translateY(0);
    opacity: 1;
}

.client-notification.hiding {
    transform: translateX(-50%) translateY(-100px);
    opacity: 0;
}

.client-notification.success {
    border-left: 5px solid #4CAF50;
    background: linear-gradient(135deg, #f8fff8, #e8f5e8);
}

.client-notification.completed {
    border-left: 5px solid #FF9800;
    background: linear-gradient(135deg, #fff8e1, #ffecb3);
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.notification-header h3 {
    margin: 0;
    color: #333;
    font-size: 18px;
}

.notification-header button {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #999;
}

.notification-time {
    font-size: 12px;
    color: #666;
    margin-top: 8px;
    text-align: right;
}

.call-waiter-btn.acknowledged {
    background: linear-gradient(135deg, #4CAF50, #45a049);
    color: white;
}

.call-waiter-btn.completed {
    background: linear-gradient(135deg, #FF9800, #f57c00);
    color: white;
}

.status-message.status-acknowledged {
    background: #e8f5e8;
    color: #2e7d32;
    border-left: 4px solid #4CAF50;
}

.status-message.status-completed {
    background: #fff8e1;
    color: #f57c00;
    border-left: 4px solid #FF9800;
}
`;

// Inyectar estilos
const styleSheet = document.createElement('style');
styleSheet.textContent = clientNotificationStyles;
document.head.appendChild(styleSheet);

// ✅ EXPORT PARA USO GLOBAL
window.initializeClientStatusListener = initializeClientStatusListener;
export { initializeClientStatusListener, ClientCallStatusListener };

// 🚀 INSTRUCCIONES DE USO:
/*

## PARA LA PÁGINA QR DEL CLIENTE:

### 1. Incluir el archivo:
```html
<script type="module" src="client-realtime-listener.js"></script>
```

### 2. Inicializar después de llamar al mozo:
```javascript
// Después de hacer la llamada exitosa
const tableId = TABLE_ID; // Tu variable de mesa
const clientListener = initializeClientStatusListener(tableId);
```

### 3. El cliente verá automáticamente:
- ✅ "Solicitud Recibida" cuando el mozo la acepta
- 🎉 "Servicio Completado" cuando se complete
- Notificaciones browser si está en otra pestaña

¡TIEMPO REAL SIN POLLING! ⚡
*/