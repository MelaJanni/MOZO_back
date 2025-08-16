// 🚀 FIREBASE UNIFICADO - FRONTEND QUE FUNCIONA CON NUEVA ESTRUCTURA
// =====================================================================

import { initializeApp } from 'firebase/app';
import { getDatabase, ref, onValue, off } from 'firebase/database';

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

// ✅ CLIENTE - Para páginas QR de mesas
class UnifiedTableListener {
    constructor(tableId) {
        this.tableId = tableId;
        this.listeners = new Map();
        this.currentCallId = null;
        console.log(`🏠 Unified Table listener para mesa ${tableId}`);
    }

    startListening() {
        // 1. 🎯 Escuchar índice de mesa para saber qué llamada está activa
        const tableRef = ref(database, `tables/${this.tableId}/current_call`);
        
        const tableUnsubscribe = onValue(tableRef, (snapshot) => {
            const newCallId = snapshot.val();
            
            if (newCallId !== this.currentCallId) {
                // Parar escucha anterior
                if (this.currentCallId && this.listeners.has('call')) {
                    const oldUnsubscribe = this.listeners.get('call');
                    oldUnsubscribe();
                    this.listeners.delete('call');
                }
                
                this.currentCallId = newCallId;
                
                if (newCallId) {
                    // 2. 🎯 Escuchar datos completos de la llamada activa
                    this.listenToActiveCall(newCallId);
                } else {
                    // No hay llamada activa
                    this.onNoActiveCall();
                }
            }
        });
        
        this.listeners.set('table', tableUnsubscribe);
    }

    listenToActiveCall(callId) {
        const callRef = ref(database, `active_calls/${callId}`);
        
        const callUnsubscribe = onValue(callRef, (snapshot) => {
            const callData = snapshot.val();
            
            if (callData) {
                this.onCallUpdate(callData);
            } else {
                // Llamada eliminada = completada
                this.onCallCompleted(callId);
            }
        });
        
        this.listeners.set('call', callUnsubscribe);
    }

    onCallUpdate(callData) {
        console.log('📞 Estado de llamada actualizado:', callData);
        
        // Actualizar UI según el estado
        switch (callData.status) {
            case 'pending':
                this.showPendingStatus(callData);
                break;
            case 'acknowledged':
                this.showAcknowledgedStatus(callData);
                break;
            case 'completed':
                this.showCompletedStatus(callData);
                break;
        }
    }

    onNoActiveCall() {
        console.log('🏠 No hay llamadas activas para esta mesa');
        this.showNoCallStatus();
    }

    onCallCompleted(callId) {
        console.log('✅ Llamada completada:', callId);
        this.showCompletedStatus();
        
        // Auto-limpiar después de unos segundos
        setTimeout(() => {
            this.showNoCallStatus();
        }, 5000);
    }

    // UI Methods
    showPendingStatus(callData) {
        const container = document.getElementById('call-status') || document.body;
        container.innerHTML = `
            <div class="call-status pending">
                <h3>🔔 Llamada enviada</h3>
                <p>Tu mozo ${callData.waiter.name} ha sido notificado</p>
                <p>Llamada realizada: ${new Date(callData.called_at).toLocaleTimeString()}</p>
                <div class="loading">Esperando respuesta...</div>
            </div>
        `;
    }

    showAcknowledgedStatus(callData) {
        const container = document.getElementById('call-status') || document.body;
        const responseTime = Math.round((callData.acknowledged_at - callData.called_at) / 1000);
        
        container.innerHTML = `
            <div class="call-status acknowledged">
                <h3>👨‍🍳 ${callData.waiter.name} está en camino</h3>
                <p>Tu solicitud fue recibida en ${responseTime} segundos</p>
                <p>Reconocida: ${new Date(callData.acknowledged_at).toLocaleTimeString()}</p>
                <div class="status-indicator">✅ Atendiendo</div>
            </div>
        `;
    }

    showCompletedStatus(callData = null) {
        const container = document.getElementById('call-status') || document.body;
        container.innerHTML = `
            <div class="call-status completed">
                <h3>✅ Servicio completado</h3>
                <p>Gracias por usar nuestro servicio</p>
                ${callData ? `<p>Completado: ${new Date(callData.completed_at).toLocaleTimeString()}</p>` : ''}
            </div>
        `;
    }

    showNoCallStatus() {
        const container = document.getElementById('call-status') || document.body;
        container.innerHTML = `
            <div class="call-status no-call">
                <h3>📱 Mesa ${this.tableId}</h3>
                <p>Presiona el botón para llamar a tu mozo</p>
                <button onclick="callWaiter()" class="call-button">🔔 Llamar Mozo</button>
            </div>
        `;
    }

    stopListening() {
        this.listeners.forEach((unsubscribe) => unsubscribe());
        this.listeners.clear();
        console.log('🛑 Table listener detenido');
    }
}

// ✅ MOZO - Para app de mozos
class UnifiedWaiterListener {
    constructor(waiterId) {
        this.waiterId = waiterId;
        this.listeners = new Map();
        this.activeCalls = new Map();
        console.log(`👨‍🍳 Unified Waiter listener para mozo ${waiterId}`);
    }

    startListening() {
        // 1. 🎯 Escuchar índice de llamadas del mozo
        const waiterRef = ref(database, `waiters/${this.waiterId}/active_calls`);
        
        const waiterUnsubscribe = onValue(waiterRef, (snapshot) => {
            const callIds = snapshot.val() || [];
            console.log('📋 Llamadas activas del mozo:', callIds);
            
            // Gestionar cambios en la lista de llamadas
            this.updateActiveCallsListeners(callIds);
        });
        
        this.listeners.set('waiter', waiterUnsubscribe);
    }

    updateActiveCallsListeners(newCallIds) {
        const currentCallIds = Array.from(this.activeCalls.keys());
        
        // Remover listeners de llamadas que ya no están activas
        currentCallIds.forEach(callId => {
            if (!newCallIds.includes(callId)) {
                this.removeCallListener(callId);
                this.onCallRemoved(callId);
            }
        });
        
        // Agregar listeners para nuevas llamadas
        newCallIds.forEach(callId => {
            if (!this.activeCalls.has(callId)) {
                this.addCallListener(callId);
            }
        });
    }

    addCallListener(callId) {
        const callRef = ref(database, `active_calls/${callId}`);
        
        const callUnsubscribe = onValue(callRef, (snapshot) => {
            const callData = snapshot.val();
            
            if (callData) {
                const wasNew = !this.activeCalls.has(callId);
                this.activeCalls.set(callId, callData);
                
                if (wasNew) {
                    this.onNewCall(callData);
                } else {
                    this.onCallUpdated(callData);
                }
            }
        });
        
        this.listeners.set(`call_${callId}`, callUnsubscribe);
    }

    removeCallListener(callId) {
        const listenerKey = `call_${callId}`;
        if (this.listeners.has(listenerKey)) {
            const unsubscribe = this.listeners.get(listenerKey);
            unsubscribe();
            this.listeners.delete(listenerKey);
        }
        this.activeCalls.delete(callId);
    }

    onNewCall(callData) {
        console.log('🆕 Nueva llamada:', callData);
        
        // Notificación sonora y visual
        this.playNotificationSound();
        this.showNotification(callData);
        this.addCallToUI(callData);
    }

    onCallUpdated(callData) {
        console.log('🔄 Llamada actualizada:', callData);
        this.updateCallInUI(callData);
    }

    onCallRemoved(callId) {
        console.log('✅ Llamada completada/removida:', callId);
        this.removeCallFromUI(callId);
    }

    // UI Methods para mozos
    addCallToUI(callData) {
        const container = document.getElementById('calls-container') || document.body;
        
        const callElement = document.createElement('div');
        callElement.id = `call-${callData.id}`;
        callElement.className = `call-item urgency-${callData.urgency} status-${callData.status}`;
        callElement.innerHTML = `
            <div class="call-header">
                <h4>🏠 Mesa ${callData.table.number}</h4>
                <span class="urgency-badge">${callData.urgency}</span>
                <span class="time">${new Date(callData.called_at).toLocaleTimeString()}</span>
            </div>
            <div class="call-message">${callData.message}</div>
            <div class="call-actions">
                <button onclick="acknowledgeCall('${callData.id}')" 
                        class="btn-acknowledge">✅ Atender</button>
                <button onclick="completeCall('${callData.id}')" 
                        class="btn-complete">🏁 Completar</button>
            </div>
            <div class="call-stats">
                <small>Tiempo: <span id="timer-${callData.id}">0s</span></small>
            </div>
        `;
        
        container.prepend(callElement);
        
        // Iniciar timer
        this.startCallTimer(callData.id, callData.called_at);
    }

    updateCallInUI(callData) {
        const element = document.getElementById(`call-${callData.id}`);
        if (element) {
            element.className = `call-item urgency-${callData.urgency} status-${callData.status}`;
            
            // Actualizar acciones según estado
            const actionsDiv = element.querySelector('.call-actions');
            if (callData.status === 'acknowledged') {
                actionsDiv.innerHTML = `
                    <button onclick="completeCall('${callData.id}')" 
                            class="btn-complete">🏁 Completar</button>
                    <span class="status-badge acknowledged">✅ Atendiendo</span>
                `;
            }
        }
    }

    removeCallFromUI(callId) {
        const element = document.getElementById(`call-${callId}`);
        if (element) {
            element.classList.add('removing');
            setTimeout(() => element.remove(), 300);
        }
    }

    startCallTimer(callId, startTime) {
        const timerElement = document.getElementById(`timer-${callId}`);
        if (!timerElement) return;
        
        const updateTimer = () => {
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            const minutes = Math.floor(elapsed / 60);
            const seconds = elapsed % 60;
            timerElement.textContent = minutes > 0 ? `${minutes}m ${seconds}s` : `${seconds}s`;
        };
        
        updateTimer();
        const interval = setInterval(() => {
            if (document.getElementById(`timer-${callId}`)) {
                updateTimer();
            } else {
                clearInterval(interval);
            }
        }, 1000);
    }

    playNotificationSound() {
        try {
            const audio = new Audio('/sounds/notification.mp3');
            audio.volume = 0.7;
            audio.play().catch(e => console.log('Audio bloqueado'));
        } catch (e) {
            console.log('No se pudo reproducir audio');
        }
    }

    showNotification(callData) {
        if (Notification.permission === 'granted') {
            new Notification(`🔔 Mesa ${callData.table.number}`, {
                body: callData.message,
                icon: '/icons/waiter-icon.png',
                tag: `call-${callData.id}`
            });
        }
    }

    stopListening() {
        this.listeners.forEach((unsubscribe) => unsubscribe());
        this.listeners.clear();
        this.activeCalls.clear();
        console.log('🛑 Waiter listener detenido');
    }
}

// ✅ FUNCIONES GLOBALES PARA USO EN HTML
window.acknowledgeCall = async function(callId) {
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
};

window.completeCall = async function(callId) {
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
};

window.callWaiter = async function() {
    const tableId = window.currentTableId; // Debe ser establecido por tu app
    
    try {
        const response = await fetch(`/api/tables/${tableId}/call-waiter`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message: 'Cliente solicita atención',
                urgency: 'normal'
            })
        });
        
        if (response.ok) {
            console.log('📞 Mozo llamado exitosamente');
        }
    } catch (error) {
        console.error('Error llamando mozo:', error);
    }
};

// ✅ FUNCIONES DE INICIALIZACIÓN
window.initTableListener = function(tableId) {
    const listener = new UnifiedTableListener(tableId);
    listener.startListening();
    return listener;
};

window.initWaiterListener = function(waiterId) {
    if (Notification.permission === 'default') {
        Notification.requestPermission();
    }
    
    const listener = new UnifiedWaiterListener(waiterId);
    listener.startListening();
    return listener;
};

// Exportar para uso como módulo
export { UnifiedTableListener, UnifiedWaiterListener };