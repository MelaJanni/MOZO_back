// 🚀 FIREBASE UNIFIED ADAPTER - Para Dashboard Vue
// ===================================================
// Adapta el UnifiedWaiterListener para trabajar con el Dashboard Vue actual
// Mantiene compatibilidad total con eventos existentes

import { Capacitor } from '@capacitor/core'
import { ref, onValue, off } from 'firebase/database';
import { getRealtimeDB } from '@/services/firebaseCore'

// ✅ Base Realtime Database singleton
const database = getRealtimeDB();

// ✅ 2. ADAPTER PARA DASHBOARD VUE - Compatible con eventos existentes
class UnifiedWaiterNotifications {
    constructor(waiterId) {
        this.waiterId = waiterId;
        this.listeners = new Map();
    this.activeCalls = new Map();
    // Fase de inicialización: se cargan llamadas existentes sin disparar sonido
    this.initializing = false;
    this._expectedInitialCallIds = new Set();
    this._initialSetProcessed = false;
    // console.log(`⚡ UNIFIED Waiter listener para mozo ${waiterId}`);
    }

    // 🎧 INICIAR ESCUCHA UNIFICADA
    startListening() {
    // console.log(`⚡ Iniciando UNIFIED listener para mozo ${this.waiterId}`);
        
        // 🎯 Escuchar índice de llamadas activas del mozo
        const waiterRef = ref(database, `waiters/${this.waiterId}/active_calls`);
        
        const waiterUnsubscribe = onValue(waiterRef, (snapshot) => {
            const callIds = snapshot.val() || [];
            // console.log('📋 UNIFIED: Llamadas activas del mozo:', callIds);

            if (this.initializing && !this._initialSetProcessed) {
                this._expectedInitialCallIds = new Set(callIds.map(id => String(id)));
                if (this._expectedInitialCallIds.size === 0) {
                    this.initializing = false;
                    this._initialSetProcessed = true;
                    // console.log('✅ UNIFIED: Inicialización sin llamadas existentes');
                }
            }

            // Gestionar cambios en la lista de llamadas
            this.updateActiveCallsListeners(callIds);
        }, (error) => {
            console.error(`🚨 Error en UNIFIED listener:`, error);
            this.handleError(error);
        });
        
        this.initializing = true;
    this.listeners.set('waiter', waiterUnsubscribe);
    // console.log(`⚡ UNIFIED WebSocket conectado`);
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
                
                // Normalizar datos para compatibilidad con Dashboard Vue
                const normalizedCall = this.normalizeCallData(callData);
                
                if (wasNew) {
                    this.onNewCall(normalizedCall);
                } else {
                    this.onCallUpdated(normalizedCall);
                }
                
                // Mover a historial sólo si acknowledged/completed
                if (callData.status === 'acknowledged' || callData.status === 'completed') {
                    // console.log(`🔄 UNIFIED: Llamada ${callId} cambió a ${callData.status}, removiendo de vista activa`);
                    setTimeout(() => {
                        this.onCallMovedToHistory(callId, normalizedCall);
                    }, 400);
                }

                // Progreso de inicialización: marcar id como procesado
                if (this.initializing && !this._initialSetProcessed) {
                    if (this._expectedInitialCallIds.has(String(callId))) {
                        this._expectedInitialCallIds.delete(String(callId));
                    }
                    if (this._expectedInitialCallIds.size === 0) {
                        this.initializing = false;
                        this._initialSetProcessed = true;
                        // console.log('✅ UNIFIED: Finalizada fase de inicialización');
                    }
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

    // 🆕 NUEVA LLAMADA - Compatible con Dashboard Vue
    onNewCall(callData) {
        console.debug('⚡ UNIFIED: Nueva llamada:', callData.id, callData);
        const isPending = callData.status === 'pending';
        if (isPending) {
            // Propagar siempre la llamada pending al Dashboard
            window.dispatchEvent(new CustomEvent('newWaiterCall', { detail: callData }));
            window.dispatchEvent(new CustomEvent('ultraFastAddCall', { detail: callData }));

            if (!this.initializing) {
                // pending log (consolidated below via logStatus)
                this.playUltraFastSound();
                this.vibrateUltraFast();
                this.scheduleNativeNotification(callData);
                this.showBrowserNotification(callData);
            } else {
                // console.log('⏳ UNIFIED: Llamada pending inicial (sin sonido):', callData.id);
            }
            this.logStatus(callData, 'pending');
        } else {
            // acknowledged/completed
            this.onCallMovedToHistory(callData.id, callData);
        }
    }

    // 🔄 LLAMADA ACTUALIZADA - Compatible con Dashboard Vue
    onCallUpdated(callData) {
    // console.log('⚡ UNIFIED: Llamada actualizada:', callData.id);
        
        if (callData.status === 'acknowledged') {
            this.showStatusUpdate(`Mesa ${callData.table_number} - Atendiendo`);
            this.logStatus(callData, 'acknowledged');
        }
        
        // 🎯 EVENTO PARA VUE
        window.dispatchEvent(new CustomEvent('updateCallStatus', { 
            detail: callData 
        }));
    }

    // ❌ LLAMADA ELIMINADA - Compatible con Dashboard Vue
    onCallRemoved(callId) {
    // console.log('⚡ UNIFIED: Llamada completada:', callId);
        this.showStatusUpdate('Servicio completado');
        
        // 🎯 EVENTO PARA VUE
        window.dispatchEvent(new CustomEvent('removeCall', { 
            detail: { callId } 
        }));
    }

    // 📚 LLAMADA MOVIDA A HISTORIAL - Cuando cambia de pending a acknowledged/completed
    onCallMovedToHistory(callId, callData) {
    // console.log('📚 UNIFIED: Llamada movida a historial:', callId, callData.status);
        
        let message = '';
        switch (callData.status) {
            case 'acknowledged':
                message = `Mesa ${callData.table_number} - Atendiendo`;
                break;
            case 'completed':
                message = `Mesa ${callData.table_number} - Completado`;
                break;
            default:
                message = `Mesa ${callData.table_number} - Actualizado`;
        }
        
        this.showStatusUpdate(message);
        
        // 🎯 EVENTO PARA VUE - Remover de vista activa
        window.dispatchEvent(new CustomEvent('removeCall', { 
            detail: { callId } 
        }));
        
        // 📚 EVENTO ADICIONAL para historial si es necesario
        window.dispatchEvent(new CustomEvent('callMovedToHistory', { 
            detail: { callId, callData, status: callData.status } 
        }));

        if (callData.status === 'acknowledged') {
            this.logStatus(callData, 'acknowledged');
        } else if (callData.status === 'completed') {
            this.logStatus(callData, 'completed');
        }
    }

    // Consolidated status logger (only three categories)
    logStatus(call, phase) {
        try {
            if (!call || !call.id) return;
            switch (phase) {
                case 'pending':
                    console.info('[UNIFIED][PENDING]', `id=${call.id}`, `mesa=${call.table_number}`, `t=${call.called_at}`);
                    break;
                case 'acknowledged':
                    console.info('[UNIFIED][ACKNOWLEDGED]', `id=${call.id}`, `mesa=${call.table_number}`, `ack=${call.acknowledged_at}`);
                    break;
                case 'completed':
                    console.info('[UNIFIED][COMPLETED]', `id=${call.id}`, `mesa=${call.table_number}`, `comp=${call.completed_at}`);
                    break;
            }
        } catch (_) { /* ignore */ }
    }

    // 🔄 NORMALIZAR DATOS para compatibilidad con Dashboard Vue
    normalizeCallData(unifiedCallData) {
        return {
            id: unifiedCallData.id,
            table_number: unifiedCallData.table?.number || unifiedCallData.table_number,
            message: unifiedCallData.message || 'Cliente solicita atención',
            urgency: unifiedCallData.urgency || 'normal',
            status: unifiedCallData.status,
            called_at: unifiedCallData.called_at,
            acknowledged_at: unifiedCallData.acknowledged_at,
            completed_at: unifiedCallData.completed_at,
            timestamp: unifiedCallData.called_at,
            waiter_id: unifiedCallData.waiter?.id || this.waiterId,
            // Mantener estructura original para compatibilidad
            table: unifiedCallData.table,
            waiter: unifiedCallData.waiter
        };
    }

    // 📱 Programar LocalNotification nativa
    scheduleNativeNotification(callData) {
        try {
            if (Capacitor && Capacitor.isPluginAvailable && Capacitor.isPluginAvailable('LocalNotifications')) {
                import('@capacitor/local-notifications').then(({ LocalNotifications }) => {
                    LocalNotifications.schedule({
                        notifications: [
                            {
                                title: `Mesa ${callData.table_number} · ${callData.urgency ? callData.urgency.toUpperCase() : ''}`,
                                body: callData.message || 'Solicita atención',
                                id: Number(Date.now() % 100000),
                                extra: { callId: callData.id, source: 'unified-realtime' },
                                schedule: null,
                                smallIcon: undefined,
                            }
                        ]
                    }).then(() => {
                        // console.log('✅ UNIFIED: Local notification scheduled')
                    }).catch(err => console.warn('⚠️ Error scheduling local notification:', err))
                }).catch(err => {
                    console.warn('⚠️ Error importing LocalNotifications:', err)
                })
            }
        } catch (err) {
            console.warn('⚠️ Exception scheduling native notification:', err)
        }
    }

    // 🔔 NOTIFICACIÓN BROWSER
    showBrowserNotification(callData) {
        try {
            if (typeof Notification === 'undefined') return; // WebView Android
            if (Notification.permission !== 'granted') return;
            const notification = new Notification(`⚡ Mesa ${callData.table_number}`, {
                body: `${callData.message || 'Solicita atención'} (UNIFIED)`,
                icon: '/favicon.ico',
                tag: `unified-call-${callData.id}`,
                requireInteraction: true
            });
            notification.onclick = () => {
                window.focus();
                notification.close();
            };
        } catch (_) { /* ignore in unsupported env */ }
    }

    // 🔊 SONIDO ULTRA RÁPIDO
    playUltraFastSound() {
        try {
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
            const audio = new Audio();
            audio.src = 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+nfvnUjBjd+zO/eizEIDGKw5+mqWRMJRZzp4b5+JgU2jdXzzX4tBSF1xe/ejzQIElyx6OyrYRYJSKXi67RiHAY8k9nyyXkpBSR+zO/ijDEIDWOz6OyrXRQIR6Hn4L55KAU5jNTx0IAvBh1qtOnnqlkSCUig5OqyYhsGPJHY8sp7KgUle8vt3o4yBxFYr+ftrWIaBjeL0/LNfjAGIn3M7+CNMA==';
            audio.volume = 0.7;
            audio.play().catch(e => {/* console.log('Audio bloqueado por navegador') */});
        }
    }

    // 📳 VIBRACIÓN ULTRA RÁPIDA
    vibrateUltraFast() {
        if (navigator.vibrate) {
            navigator.vibrate([100, 50, 100, 50, 200]);
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

    // ✅ RECONOCER LLAMADA usando API existente
    async acknowledgeCall(callId) {
        try {
            const token = localStorage.getItem('token');
            let response = null;
            
            try {
                const mod = await import('@/services/waiterCallsService')
                const svc = mod && (mod.default || mod)
                if (svc && typeof svc.acknowledgCall === 'function') {
                    response = await svc.acknowledgCall(callId)
                    // console.log('🔁 UNIFIED: Acknowledge call response:', response)
                } else if (svc && typeof svc.acknowledgeCall === 'function') {
                    response = await svc.acknowledgeCall(callId)
                    // console.log('🔁 UNIFIED: Acknowledge call response:', response)
                } else {
                    throw new Error('No acknowledge helper found')
                }
            } catch (e) {
                // Fallback to fetch
                response = await fetch(`${import.meta.env.VITE_API_URL || 'https://mozoqr.com/api'}/waiter/calls/${callId}/acknowledge`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
                // console.log('🔁 UNIFIED: Acknowledge via fetch:', response.status)
            }

            let success = false
            if (response && typeof response.ok === 'boolean') {
                success = response.ok
            } else if (response && typeof response.success === 'boolean') {
                success = response.success !== false
            } else if (response) {
                success = true
            }

            if (success) {
                // console.log('⚡ UNIFIED: Llamada reconocida');
                window.dispatchEvent(new CustomEvent('callAcknowledged', { detail: { callId } }))
            } else {
                const status = response && (response.status || response.code || 'unknown')
                throw new Error(`Acknowledge failed: ${status}`)
            }
        } catch (error) {
            console.error('UNIFIED: Error reconociendo llamada:', error);
            this.showError('Error al reconocer la llamada');
        }
    }

    // ✅ COMPLETAR LLAMADA usando API existente
    async completeCall(callId) {
        try {
            const token = localStorage.getItem('token');
            let response = null;
            
            try {
                const mod = await import('@/services/waiterCallsService')
                const svc = mod && (mod.default || mod)
                if (svc && typeof svc.completeCall === 'function') {
                    response = await svc.completeCall(callId)
                    // console.log('🔁 UNIFIED: Complete call response:', response)
                } else if (svc && typeof svc.complete === 'function') {
                    response = await svc.complete(callId)
                    // console.log('🔁 UNIFIED: Complete call response:', response)
                } else {
                    throw new Error('No complete helper found')
                }
            } catch (e) {
                response = await fetch(`${import.meta.env.VITE_API_URL || 'https://mozoqr.com/api'}/waiter/calls/${callId}/complete`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
                // console.log('🔁 UNIFIED: Complete via fetch:', response.status)
            }

            let success = false
            if (response && typeof response.ok === 'boolean') {
                success = response.ok
            } else if (response && typeof response.success === 'boolean') {
                success = response.success !== false
            } else if (response) {
                success = true
            }

            if (success) {
                console.log('⚡ UNIFIED: Llamada completada');
                window.dispatchEvent(new CustomEvent('removeCall', { detail: { callId } }))
                
                try {
                    const { useNotificationsStore } = await import('@/stores/notifications')
                    useNotificationsStore().removeNotification(callId)
                } catch (e) {
                    console.warn('⚠️ Unable to remove notification from store:', e)
                }
            } else {
                const status = response && (response.status || response.code || 'unknown')
                throw new Error(`Complete failed: ${status}`)
            }
        } catch (error) {
            console.error('UNIFIED: Error completando llamada:', error);
            this.showError('Error al completar la llamada');
        }
    }

    // 🛑 DETENER ESCUCHA
    stopListening() {
        this.listeners.forEach((unsubscribe) => {
            unsubscribe();
        });
        this.listeners.clear();
        this.activeCalls.clear();
        console.log('🛑 UNIFIED listener detenido');
    }

    // 🧪 CREAR DATOS DE PRUEBA (para testing)
    async createTestCall() {
        console.log('🧪 UNIFIED: Creando llamada de prueba...');
        try {
            const response = await fetch(`${import.meta.env.VITE_API_URL || 'https://mozoqr.com/api'}/tables/1/call-waiter`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message: 'Llamada de prueba UNIFIED',
                    urgency: 'high'
                })
            });
            
            if (response.ok) {
                console.log('✅ UNIFIED: Llamada de prueba creada');
            }
        } catch (error) {
            console.error('❌ UNIFIED: Error creando llamada de prueba:', error);
        }
    }

    // ❌ MANEJAR ERRORES
    handleError(error) {
        console.error('🚨 UNIFIED: Error en listener:', error);
        this.showError('Error de conexión en tiempo real');
    }

    // 🚨 MOSTRAR ERROR
    showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'ultra-fast-error';
        errorDiv.innerHTML = `
            <p>❌ ${message}</p>
            <p>Reintentando automáticamente...</p>
            <button onclick="this.remove()">Cerrar</button>
        `;
        document.body.appendChild(errorDiv);
        
        setTimeout(() => {
            if (errorDiv.parentElement) {
                errorDiv.remove();
            }
        }, 5000);
        
        setTimeout(() => {
            this.startListening();
        }, 2000);
    }
}

// ✅ 3. FUNCIÓN DE INICIALIZACIÓN COMPATIBLE
export function initializeUnifiedWaiterNotifications(waiterId) {
    // Solicitar permisos de notificación
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    // Intentar crear canal LocalNotifications
    try {
        if (Capacitor && Capacitor.isPluginAvailable && Capacitor.isPluginAvailable('LocalNotifications')) {
            import('@capacitor/local-notifications').then(({ LocalNotifications }) => {
                LocalNotifications.requestPermissions().then((perm) => {
                    console.log('UNIFIED: LocalNotifications permissions:', perm)
                }).catch(err => {
                    console.warn('Error pidiendo permisos LocalNotifications:', err)
                })

                if (LocalNotifications.createChannel) {
                    LocalNotifications.createChannel({
                        id: 'mozo_waiter',
                        name: 'Mozo - Llamadas',
                        importance: 5,
                        visibility: 1,
                        description: 'Notificaciones de llamadas y eventos importantes para mozos'
                    }).then(() => {
                        console.log('✅ UNIFIED: Canal mozo_waiter creado')
                    }).catch(err => {
                        console.warn('⚠️ Error creando canal mozo_waiter:', err)
                    })
                }
            }).catch(err => console.warn('⚠️ No se pudo importar LocalNotifications:', err))
        }
    } catch (err) {
        console.warn('⚠️ Excepción al preparar LocalNotifications:', err)
    }
    
    // Crear instancia UNIFIED
    const unifiedNotifications = new UnifiedWaiterNotifications(waiterId);
    unifiedNotifications.startListening();
    
    console.log('⚡ UNIFIED notifications inicializadas');
    
    return unifiedNotifications;
}

// Cleanup al salir
window.addEventListener('beforeunload', () => {
    if (window.unifiedNotifications) {
        window.unifiedNotifications.stopListening();
    }
});

export default { UnifiedWaiterNotifications, initializeUnifiedWaiterNotifications };