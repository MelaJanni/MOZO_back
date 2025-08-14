// =====================================================
// INTEGRACIÓN OPTIMIZADA BACKEND-FRONTEND
// =====================================================

class MozoRealtimeClient {
    constructor(config = {}) {
        this.config = {
            baseUrl: config.baseUrl || '/api',
            firebaseConfig: config.firebaseConfig,
            enableFirestore: config.enableFirestore !== false,
            enablePolling: config.enablePolling !== false,
            pollingInterval: config.pollingInterval || 2000,
            debug: config.debug || false
        };
        
        this.listeners = new Map();
        this.isConnected = false;
        this.firestoreDb = null;
        
        this.init();
    }

    async init() {
        if (this.config.enableFirestore && this.config.firebaseConfig) {
            await this.initFirestore();
        }
        
        if (this.config.debug) {
            console.log('🚀 MozoRealtimeClient initialized', {
                firestore: !!this.firestoreDb,
                polling: this.config.enablePolling
            });
        }
    }

    async initFirestore() {
        try {
            // Inicializar Firebase
            const { initializeApp } = await import('firebase/app');
            const { getFirestore, connectFirestoreEmulator } = await import('firebase/firestore');
            
            const app = initializeApp(this.config.firebaseConfig);
            this.firestoreDb = getFirestore(app);
            
            this.isConnected = true;
            console.log('✅ Firestore conectado');
        } catch (error) {
            console.warn('⚠️ Error conectando Firestore, usando polling fallback:', error);
            this.firestoreDb = null;
        }
    }

    // =====================================================
    // MÉTODO PRINCIPAL - CREAR LLAMADA INSTANTÁNEA
    // =====================================================
    async createCall(tableId, message = null, urgency = 'normal') {
        const startTime = performance.now();
        
        try {
            const response = await fetch(`${this.config.baseUrl}/realtime/calls/create`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    table_id: tableId,
                    message: message,
                    urgency: urgency
                })
            });

            const data = await response.json();
            const totalTime = performance.now() - startTime;
            
            if (data.success) {
                console.log(`✅ Llamada creada en ${totalTime.toFixed(2)}ms:`, data);
                
                // Iniciar listeners para esta llamada
                if (data.data.call_id) {
                    this.startCallListener(tableId, data.data.call_id);
                }
                
                return {
                    success: true,
                    callId: data.data.call_id,
                    performance: {
                        frontendTime: totalTime,
                        backendTime: data.data.performance
                    }
                };
            } else {
                throw new Error(data.message || 'Error creando llamada');
            }
            
        } catch (error) {
            console.error('❌ Error creando llamada:', error);
            return { success: false, error: error.message };
        }
    }

    // =====================================================
    // LISTENERS OPTIMIZADOS
    // =====================================================
    startCallListener(tableId, callId) {
        // Firestore listener (prioritario)
        if (this.firestoreDb) {
            this.startFirestoreListener(tableId, callId);
        }
        
        // Polling fallback
        if (this.config.enablePolling) {
            this.startPollingListener(tableId);
        }
    }

    async startFirestoreListener(tableId, callId) {
        try {
            const { doc, onSnapshot } = await import('firebase/firestore');
            
            // Listener del documento específico de la llamada
            const callRef = doc(this.firestoreDb, `tables/${tableId}/waiter_calls/${callId}`);
            
            const unsubscribe = onSnapshot(callRef, (snapshot) => {
                if (snapshot.exists()) {
                    const data = snapshot.data();
                    this.handleCallUpdate(data);
                }
            }, (error) => {
                console.error('❌ Error en Firestore listener:', error);
                // Activar polling como fallback
                this.startPollingListener(tableId);
            });
            
            this.listeners.set(`firestore-${callId}`, unsubscribe);
            console.log(`🔥 Firestore listener activo para llamada ${callId}`);
            
        } catch (error) {
            console.error('❌ Error iniciando Firestore listener:', error);
            this.startPollingListener(tableId);
        }
    }

    startPollingListener(tableId) {
        if (this.listeners.has(`polling-${tableId}`)) {
            return; // Ya existe
        }

        const pollInterval = setInterval(async () => {
            try {
                const response = await fetch(`${this.config.baseUrl}/table/${tableId}/call-status`);
                const data = await response.json();
                
                if (data.success && data.call) {
                    this.handleCallUpdate(data.call);
                }
            } catch (error) {
                console.error('❌ Error en polling:', error);
            }
        }, this.config.pollingInterval);
        
        this.listeners.set(`polling-${tableId}`, pollInterval);
        console.log(`🔄 Polling activo para mesa ${tableId}`);
    }

    // =====================================================
    // MANEJO DE ACTUALIZACIONES
    // =====================================================
    handleCallUpdate(callData) {
        const event = new CustomEvent('mozo-call-update', {
            detail: callData
        });
        
        // UI Updates
        this.updateCallUI(callData);
        
        // Notificaciones
        if (callData.event_type === 'created') {
            this.showNotification('Nueva llamada', `Mesa ${callData.table_number}`);
        } else if (callData.event_type === 'acknowledged') {
            this.showNotification('Llamada atendida', `Mesa ${callData.table_number} - ${callData.waiter_name}`);
        } else if (callData.event_type === 'completed') {
            this.showNotification('Servicio completado', `Mesa ${callData.table_number}`);
            this.clearCallFromUI(callData.id);
        }
        
        // Dispatch event para custom handlers
        document.dispatchEvent(event);
    }

    updateCallUI(callData) {
        const callElement = document.getElementById(`call-${callData.id}`);
        
        if (!callElement && callData.status === 'pending') {
            // Crear nuevo elemento de llamada
            this.createCallElement(callData);
        } else if (callElement) {
            // Actualizar elemento existente
            this.updateCallElement(callElement, callData);
        }
    }

    createCallElement(callData) {
        const container = document.getElementById('calls-container') || document.body;
        
        const callDiv = document.createElement('div');
        callDiv.id = `call-${callData.id}`;
        callDiv.className = `call-item status-${callData.status} urgency-${callData.urgency || 'normal'}`;
        
        callDiv.innerHTML = `
            <div class="call-header">
                <h3>Mesa ${callData.table_number}</h3>
                <span class="call-status">${callData.status}</span>
                <span class="call-time">${new Date(callData.timestamp).toLocaleTimeString()}</span>
            </div>
            <div class="call-message">${callData.message}</div>
            <div class="call-actions">
                ${callData.status === 'pending' ? `
                    <button onclick="mozoClient.acknowledgeCall('${callData.id}')" class="btn-acknowledge">
                        Atender
                    </button>
                ` : ''}
                ${callData.status === 'acknowledged' ? `
                    <button onclick="mozoClient.completeCall('${callData.id}')" class="btn-complete">
                        Completar
                    </button>
                ` : ''}
            </div>
        `;
        
        container.appendChild(callDiv);
        
        // Animación de entrada
        setTimeout(() => callDiv.classList.add('animate-in'), 10);
    }

    updateCallElement(element, callData) {
        element.className = `call-item status-${callData.status} urgency-${callData.urgency || 'normal'}`;
        
        const statusSpan = element.querySelector('.call-status');
        if (statusSpan) statusSpan.textContent = callData.status;
        
        const actionsDiv = element.querySelector('.call-actions');
        if (actionsDiv) {
            actionsDiv.innerHTML = `
                ${callData.status === 'pending' ? `
                    <button onclick="mozoClient.acknowledgeCall('${callData.id}')" class="btn-acknowledge">
                        Atender
                    </button>
                ` : ''}
                ${callData.status === 'acknowledged' ? `
                    <button onclick="mozoClient.completeCall('${callData.id}')" class="btn-complete">
                        Completar
                    </button>
                ` : ''}
            `;
        }
    }

    clearCallFromUI(callId) {
        const element = document.getElementById(`call-${callId}`);
        if (element) {
            element.classList.add('animate-out');
            setTimeout(() => element.remove(), 300);
        }
    }

    // =====================================================
    // ACCIONES DE LLAMADAS
    // =====================================================
    async acknowledgeCall(callId) {
        try {
            const response = await fetch(`${this.config.baseUrl}/realtime/calls/${callId}/acknowledge`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            return data.success;
        } catch (error) {
            console.error('❌ Error acknowledging call:', error);
            return false;
        }
    }

    async completeCall(callId) {
        try {
            const response = await fetch(`${this.config.baseUrl}/realtime/calls/${callId}/complete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            return data.success;
        } catch (error) {
            console.error('❌ Error completing call:', error);
            return false;
        }
    }

    // =====================================================
    // UTILIDADES
    // =====================================================
    showNotification(title, message) {
        // Notificación nativa del browser
        if (Notification.permission === 'granted') {
            new Notification(title, {
                body: message,
                icon: '/favicon.ico'
            });
        }
        
        // Notificación visual en la UI
        this.showUINotification(title, message);
    }

    showUINotification(title, message) {
        const notification = document.createElement('div');
        notification.className = 'mozo-notification';
        notification.innerHTML = `
            <div class="notification-title">${title}</div>
            <div class="notification-message">${message}</div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => notification.classList.add('show'), 10);
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    async testLatency() {
        try {
            const response = await fetch(`${this.config.baseUrl}/realtime/latency-test`);
            const data = await response.json();
            console.log('🚀 Latency Test Results:', data);
            return data;
        } catch (error) {
            console.error('❌ Error testing latency:', error);
            return null;
        }
    }

    // Cleanup
    destroy() {
        this.listeners.forEach((listener, key) => {
            if (typeof listener === 'function') {
                listener(); // Firestore unsubscribe
            } else {
                clearInterval(listener); // Polling interval
            }
        });
        this.listeners.clear();
    }
}

// =====================================================
// INICIALIZACIÓN PARA QR PAGES
// =====================================================
function initMozoQRPage(tableId, firebaseConfig) {
    // Crear cliente
    window.mozoClient = new MozoRealtimeClient({
        firebaseConfig: firebaseConfig,
        debug: true
    });
    
    // Función global para crear llamadas desde botones
    window.createWaiterCall = function(message = null, urgency = 'normal') {
        return mozoClient.createCall(tableId, message, urgency);
    };
    
    // Event listeners para actualizaciones
    document.addEventListener('mozo-call-update', (event) => {
        console.log('📢 Call update received:', event.detail);
        // Custom handling aquí
    });
    
    // Test latency on load
    setTimeout(() => {
        mozoClient.testLatency();
    }, 1000);
    
    return mozoClient;
}

// =====================================================
// ESTILOS CSS INCLUIDOS
// =====================================================
const styles = `
    .call-item {
        border: 2px solid #ddd;
        border-radius: 8px;
        padding: 16px;
        margin: 8px 0;
        transition: all 0.3s ease;
        opacity: 0;
        transform: translateY(20px);
    }
    
    .call-item.animate-in {
        opacity: 1;
        transform: translateY(0);
    }
    
    .call-item.animate-out {
        opacity: 0;
        transform: translateX(-100%);
    }
    
    .call-item.status-pending {
        border-color: #ff6b6b;
        background-color: #fff5f5;
    }
    
    .call-item.status-acknowledged {
        border-color: #4ecdc4;
        background-color: #f0fdfc;
    }
    
    .call-item.status-completed {
        border-color: #45b7d1;
        background-color: #f0f9ff;
    }
    
    .call-item.urgency-high {
        border-width: 3px;
        animation: pulse 1s infinite;
    }
    
    .call-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }
    
    .call-actions {
        margin-top: 12px;
    }
    
    .btn-acknowledge {
        background: #ff6b6b;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .btn-complete {
        background: #4ecdc4;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .mozo-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border: 2px solid #4ecdc4;
        border-radius: 8px;
        padding: 16px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        z-index: 9999;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
    }
    
    .mozo-notification.show {
        opacity: 1;
        transform: translateX(0);
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
`;

// Inyectar estilos
const styleSheet = document.createElement('style');
styleSheet.textContent = styles;
document.head.appendChild(styleSheet);

// Export
export { MozoRealtimeClient, initMozoQRPage };