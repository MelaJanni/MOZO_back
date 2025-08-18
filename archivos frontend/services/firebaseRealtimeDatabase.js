// 🚀 FIREBASE REALTIME DATABASE - ULTRA FAST FRONTEND
// =====================================================

import { initializeApp } from 'firebase/app';
import { Capacitor } from '@capacitor/core'
import { getDatabase, ref, onValue, off, set, push } from 'firebase/database';

// ✅ 1. CONFIGURACIÓN FIREBASE
const firebaseConfig = {
    projectId: "mozoqr-7d32c",
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: "mozoqr-7d32c.firebaseapp.com",
    databaseURL: "https://mozoqr-7d32c-default-rtdb.firebaseio.com", // ⚡ CLAVE ULTRA RÁPIDA
    storageBucket: "mozoqr-7d32c.appspot.com",
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID
};

const app = initializeApp(firebaseConfig);
const database = getDatabase(app); // ⚡ REALTIME DATABASE

// ✅ 2. CLASE ULTRA RÁPIDA PARA NOTIFICACIONES
class UltraFastWaiterNotifications {
    constructor(waiterId) {
        this.waiterId = waiterId;
        this.listeners = new Map();
        this.activeCalls = new Map();
    this.initializing = false; // evitar notificaciones durante el seed inicial
        // console.log(`⚡ ULTRA FAST listener inicializado para mozo ${waiterId}`);
    }

    // 🎧 INICIAR ESCUCHA ULTRA RÁPIDA
    startListening() {
        console.log(`⚡ Iniciando ULTRA FAST listener para mozo ${this.waiterId}`);
        
        // ⚡ ESCUCHAR: /waiters/{waiterId}/calls (ULTRA RÁPIDO)
        const callsRef = ref(database, `waiters/${this.waiterId}/calls`);
        // console.log(`🔍 DEBUG: Escuchando en ruta: waiters/${this.waiterId}/calls`);

    // Marcar inicialización para evitar reproducir sonidos/notifs por llamadas ya existentes
    this.initializing = true;
    const unsubscribe = onValue(callsRef, 
            (snapshot) => {
                // console.log(`🔍 DEBUG: Snapshot recibido - exists: ${snapshot.exists()}, size: ${snapshot.size}`);
                // console.log(`🔍 DEBUG: Snapshot key: ${snapshot.key}`);
                // console.log(`🔍 DEBUG: Snapshot val:`, snapshot.val());
                this.handleSnapshot(snapshot);
            },
            (error) => {
                console.error(`🚨 Error en listener:`, error);
                this.handleError(error);
            }
        );

        this.listeners.set('main', unsubscribe);
        console.log(`⚡ ULTRA FAST WebSocket conectado`);
    }

    // 📸 MANEJAR CAMBIOS ULTRA RÁPIDOS
    handleSnapshot(snapshot) {
        const data = snapshot.val();
        // console.log(`⚡ ULTRA FAST snapshot recibido:`, data);
        
        if (!data) {
            // No hay llamadas - limpiar UI
            this.clearAllCalls();
            // Si este fue el primer snapshot, desactivar modo inicialización
            if (this.initializing) this.initializing = false;
            return;
        }

        const currentCalls = new Set();

        // Procesar todas las llamadas
        Object.entries(data).forEach(([callId, callData]) => {
            currentCalls.add(callId);
            
            if (!this.activeCalls.has(callId)) {
                // ⚡ NUEVA LLAMADA DETECTADA
                // console.log(`🆕 Nueva llamada: Mesa ${callData.table_number}`);
                this.onNewCall({ id: callId, ...callData });
            } else {
                // ⚡ LLAMADA ACTUALIZADA
                const oldCall = this.activeCalls.get(callId);
                if (oldCall.status !== callData.status) {
                    console.log(`🔄 Firebase: Llamada actualizada Mesa ${callData.table_number}: ${oldCall.status} → ${callData.status}`);
                    this.onCallUpdated({ id: callId, ...callData });
                }
            }
            
            this.activeCalls.set(callId, callData);
        });

        // Detectar llamadas eliminadas
        this.activeCalls.forEach((callData, callId) => {
            if (!currentCalls.has(callId)) {
                // console.log(`✅ Llamada completada: Mesa ${callData.table_number}`);
                this.onCallRemoved(callId);
                this.activeCalls.delete(callId);
            }
        });

        // Primer snapshot procesado, terminar modo inicialización
        if (this.initializing) {
            this.initializing = false;
        }
    }

    // 🆕 NUEVA LLAMADA (ULTRA RÁPIDA)
    onNewCall(callData) {
    console.debug('⚡ NUEVA LLAMADA ULTRA RÁPIDA (onNewCall):', callData && callData.id, callData);

        // Solo enviar sonido/pendientes si no estamos en el seed inicial
        if (!this.initializing && callData.status === 'pending') {
            console.log('🔔 Enviando notificaciones push para llamada nueva (pending)');
            // 🔊 NOTIFICACIÓN INMEDIATA
            this.playUltraFastSound();
            // 📳 VIBRACIÓN INMEDIATA
            this.vibrateUltraFast();
            // Programar notificación nativa (Android)
            this.scheduleNativeNotification(callData);
            // 🔔 BROWSER NOTIFICATION INMEDIATA (opcional)
            this.showBrowserNotification(callData);
        } else {
            console.log('ℹ️ Nueva llamada agregada en modo inicial o status no-pending:', callData.status);
        }

        // 🎨 UI INMEDIATA - Emitir eventos para que Dashboard se actualice
        this.addCallToUI(callData);
        
        // 🎯 EVENTOS PARA VUE (ambos para asegurar compatibilidad)
        window.dispatchEvent(new CustomEvent('newWaiterCall', { 
            detail: callData 
        }));
        window.dispatchEvent(new CustomEvent('ultraFastAddCall', { 
            detail: callData 
        }));
    }

    // 📱 Programar LocalNotification nativa sin crear DOM
    scheduleNativeNotification(callData) {
        try {
            if (Capacitor && Capacitor.isPluginAvailable && Capacitor.isPluginAvailable('LocalNotifications')) {
                import('@capacitor/local-notifications').then(({ LocalNotifications }) => {
                    const androidChannel = 'mozo_waiter'
                    LocalNotifications.schedule({
                        notifications: [
                            {
                                title: `Mesa ${callData.table_number} · ${callData.urgency ? callData.urgency.toUpperCase() : ''}`,
                                body: callData.message || 'Solicita atención',
                                id: Number(Date.now() % 100000),
                                extra: { callId: callData.id, source: 'ultra-fast-realtime' },
                                schedule: null,
                                smallIcon: undefined,
                                channelId: androidChannel
                            }
                        ]
                    }).then(() => {
                        console.log('✅ Local notification scheduled for native platform (ultra-fast call)')
                    }).catch(err => console.warn('⚠️ Error scheduling local notification:', err))
                }).catch(err => {
                    console.warn('⚠️ Error importing LocalNotifications in scheduleNativeNotification:', err)
                })
            }
        } catch (err) {
            console.warn('⚠️ Exception scheduling native notification:', err)
        }
    }

    // 🔄 LLAMADA ACTUALIZADA (ULTRA RÁPIDA)
    onCallUpdated(callData) {
        console.log('⚡ Llamada actualizada ULTRA RÁPIDA:', callData);
        this.updateCallInUI(callData);
        
        if (callData.status === 'acknowledged') {
            this.showStatusUpdate(`Mesa ${callData.table_number} - Atendiendo`);
        }
        
        // 🎯 EVENTO PARA VUE
        window.dispatchEvent(new CustomEvent('updateCallStatus', { 
            detail: callData 
        }));
    }

    // ❌ LLAMADA ELIMINADA (ULTRA RÁPIDA)
    onCallRemoved(callId) {
        console.log('⚡ Llamada completada ULTRA RÁPIDA:', callId);
        this.removeCallFromUI(callId);
        this.showStatusUpdate('Servicio completado');
        
        // 🎯 EVENTO PARA VUE
        window.dispatchEvent(new CustomEvent('removeCall', { 
            detail: { callId } 
        }));
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
            const audio = new Audio();
            audio.src = 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+nfvnUjBjd+zO/eizEIDGKw5+mqWRMJRZzp4b5+JgU2jdXzzX4tBSF1xe/ejzQIElyx6OyrYRYJSKXi67RiHAY8k9nyyXkpBSR+zO/ijDEIDWOz6OyrXRQIR6Hn4L55KAU5jNTx0IAvBh1qtOnnqlkSCUig5OqyYhsGPJHY8sp7KgUle8vt3o4yBxFYr+ftrWIaBjeL0/LNfjAGIn3M7+CNMA==';
            audio.volume = 0.7;
            audio.play().catch(e => console.log('Audio bloqueado por navegador'));
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
                <span class="urgency-${callData.urgency || 'normal'}">${callData.urgency || 'normal'}</span>
                <span class="ultra-fast-badge">ULTRA FAST</span>
            </div>
            <div class="notification-body">
                <p>${callData.message || 'Solicita atención'}</p>
                <small>⚡ ${new Date(callData.called_at || callData.timestamp).toLocaleTimeString()}</small>
            </div>
            <div class="notification-actions">
                <button onclick="window.ultraFastNotifications.acknowledgeCall('${callData.id}')" 
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

        // Además, si estamos en una plataforma móvil con Capacitor y el plugin
        // LocalNotifications está disponible, programar una notificación nativa
        // para que aparezca en la barra de notificaciones de Android incluso
        // cuando la app esté en background.
        try {
            if (Capacitor && Capacitor.isPluginAvailable && Capacitor.isPluginAvailable('LocalNotifications')) {
                import('@capacitor/local-notifications').then(({ LocalNotifications }) => {
                    const androidChannel = 'mozo_waiter'
                    LocalNotifications.schedule({
                        notifications: [
                            {
                                title: `Mesa ${callData.table_number} · ${callData.urgency ? callData.urgency.toUpperCase() : ''}`,
                                body: callData.message || 'Solicita atención',
                                id: Number(Date.now() % 100000),
                                extra: { callId: callData.id, source: 'ultra-fast-realtime' },
                                schedule: null,
                                smallIcon: undefined,
                                // channelId: androidChannel // Usar canal por defecto
                            }
                        ]
                    }).then(() => {
                        console.log('✅ Local notification scheduled for native platform (ultra-fast call)')
                    }).catch(err => console.warn('⚠️ Error scheduling local notification:', err))
                }).catch(err => {
                    console.warn('⚠️ Error importing LocalNotifications in showUltraFastNotification:', err)
                })
            }
        } catch (err) {
            console.warn('⚠️ Exception scheduling native notification:', err)
        }
    }

    // 🔔 NOTIFICACIÓN BROWSER ULTRA RÁPIDA
    async showBrowserNotification(callData) {
        try {
            if (typeof Notification === 'undefined') {
                // Browser does not support Notifications API - fallback to in-app UI
                this.showUltraFastNotification(callData);
                return;
            }

            // Solicitar permiso si el estado es 'default'
            if (Notification.permission === 'default') {
                try {
                    const perm = await Notification.requestPermission();
                    console.log('Notification.requestPermission result:', perm);
                } catch (e) {
                    console.warn('Error requesting Notification permission:', e);
                }
            }

            if (Notification.permission === 'granted') {
                const notification = new Notification(`⚡ Mesa ${callData.table_number}`, {
                    body: `${callData.message || 'Solicita atención'} (ULTRA FAST)`,
                    icon: '/favicon.ico',
                    tag: `ultra-fast-call-${callData.id}`,
                    requireInteraction: true
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
            } else {
                // Permiso denegado o no disponible - usar fallback visual en la app
                console.log('Notification permission not granted, using in-app fallback');
                this.showUltraFastNotification(callData);
            }
        } catch (err) {
            console.warn('Error showing browser notification, fallback to in-app UI:', err);
            this.showUltraFastNotification(callData);
        }
    }

    // 🎨 AGREGAR LLAMADA A UI (ULTRA RÁPIDA)
    addCallToUI(callData) {
        // No crear nodos DOM aquí — evitar duplicación en la maquetación.
        // Emitir un evento para que la app Vue lo maneje y renderice la llamada
        // desde su estado reactivo (por ejemplo agregar a pendingCalls).
        try {
            console.debug('dispatch ultraFastAddCall', callData && callData.id, callData)
            window.dispatchEvent(new CustomEvent('ultraFastAddCall', { detail: callData }))
        } catch (err) {
            console.warn('⚠️ ultraFastAddCall dispatch failed:', err)
        }
    }

    // 🔄 ACTUALIZAR LLAMADA EN UI
    updateCallInUI(callData) {
        const element = document.getElementById(`call-${callData.id}`);
        if (element) {
            element.className = `ultra-fast-call-item urgency-${callData.urgency || 'normal'} status-${callData.status}`;
            
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
            const callItems = container.querySelectorAll('.ultra-fast-call-item');
            callItems.forEach(item => item.remove());
        }
        this.activeCalls.clear();
        
        // 🎯 EVENTO PARA VUE
        window.dispatchEvent(new CustomEvent('clearAllCalls'));
    }

    // ✅ RECONOCER LLAMADA (ULTRA RÁPIDO)
    async acknowledgeCall(callId) {
        try {
            const token = localStorage.getItem('token');
            // Use API helper if available so axios interceptors handle auth headers
            let response = null;
            try {
                const mod = await import('@/services/waiterCallsService')
                const svc = mod && (mod.default || mod)
                if (svc && typeof svc.acknowledgCall === 'function') {
                    // waiterCallsService.acknowledgCall returns response.data (axios style)
                    response = await svc.acknowledgCall(callId)
                    console.log('🔁 Acknowledge call (via service) response:', response)
                } else if (svc && typeof svc.acknowledgeCall === 'function') {
                    response = await svc.acknowledgeCall(callId)
                    console.log('🔁 Acknowledge call (via service.named) response:', response)
                } else {
                    throw new Error('No acknowledge helper found in waiterCallsService')
                }
            } catch (e) {
                // Fallback to fetch (returns a Response)
                try {
                    response = await fetch(`${import.meta.env.VITE_API_URL || 'https://mozoqr.com/api'}/waiter/calls/${callId}/acknowledge`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json'
                        }
                    });
                    console.log('🔁 Acknowledge call (via fetch) response status:', response.status)
                } catch (fetchErr) {
                    throw fetchErr
                }
            }

            // Normalizar comprobación de éxito: soportar Response (fetch) y objetos repsonse.data (axios)
            let success = false
            if (response && typeof response.ok === 'boolean') {
                success = response.ok
            } else if (response && typeof response.success === 'boolean') {
                success = response.success !== false
            } else if (response) {
                // Si la llamada devolvió datos (axios .data), asumimos éxito
                success = true
            }

            if (success) {
                console.log('⚡ Llamada reconocida ULTRA RÁPIDO');
                // El cambio se detectará automáticamente via Firebase Realtime Database
                // Emitir evento para sincronizar UI/tienda si es necesario
                try {
                    window.dispatchEvent(new CustomEvent('callAcknowledged', { detail: { callId } }))
                } catch (e) {
                    /* no-op */
                }
            } else {
                const status = response && (response.status || response.code || 'unknown')
                throw new Error(`Acknowledge failed: ${status}`)
            }
        } catch (error) {
            console.error('Error reconociendo llamada:', error);
            this.showError('Error al reconocer la llamada');
        }
    }

    // ✅ COMPLETAR LLAMADA (ULTRA RÁPIDO)
    async completeCall(callId) {
        try {
            const token = localStorage.getItem('token');
            let response = null;
            try {
                const mod = await import('@/services/waiterCallsService')
                const svc = mod && (mod.default || mod)
                if (svc && typeof svc.completeCall === 'function') {
                    response = await svc.completeCall(callId)
                    console.log('🔁 Complete call (via service) response:', response)
                } else if (svc && typeof svc.complete === 'function') {
                    response = await svc.complete(callId)
                    console.log('🔁 Complete call (via service.named) response:', response)
                } else {
                    throw new Error('No complete helper found in waiterCallsService')
                }
            } catch (e) {
                // Fallback to fetch
                try {
                    response = await fetch(`${import.meta.env.VITE_API_URL || 'https://mozoqr.com/api'}/waiter/calls/${callId}/complete`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json'
                        }
                    });
                    console.log('🔁 Complete call (via fetch) response status:', response.status)
                } catch (fetchErr) {
                    throw fetchErr
                }
            }

            // Normalizar comprobación de éxito
            let success = false
            if (response && typeof response.ok === 'boolean') {
                success = response.ok
            } else if (response && typeof response.success === 'boolean') {
                success = response.success !== false
            } else if (response) {
                success = true
            }

            if (success) {
                console.log('⚡ Llamada completada ULTRA RÁPIDO');
                // Emitir evento para que la UI y la tienda eliminen la llamada
                try {
                    window.dispatchEvent(new CustomEvent('removeCall', { detail: { callId } }))
                } catch (e) {
                    /* no-op */
                }

                // También intentar limpiar la notificación de la tienda
                try {
                    const { useNotificationsStore } = await import('@/stores/notifications')
                    useNotificationsStore().removeNotification(callId)
                } catch (e) {
                    console.warn('⚠️ Unable to remove notification from store after complete:', e)
                }
            } else {
                const status = response && (response.status || response.code || 'unknown')
                throw new Error(`Complete failed: ${status}`)
            }
        } catch (error) {
            console.error('Error completando llamada:', error);
            this.showError('Error al completar la llamada');
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

    // 🧪 CREAR DATOS DE PRUEBA
    async createTestCall() {
        console.log('🧪 Creando llamada de prueba...');
        try {
            const callsRef = ref(database, `waiters/${this.waiterId}/calls`);
            const newCallRef = push(callsRef);
            
            const testCall = {
                id: newCallRef.key,
                table_number: Math.floor(Math.random() * 20) + 1,
                message: 'Llamada de prueba - Solicita la cuenta',
                timestamp: Date.now(),
                status: 'pending',
                urgency: 'high',
                called_at: Date.now()
            };
            
            await set(newCallRef, testCall);
            console.log('✅ Llamada de prueba creada:', testCall);
            return testCall;
        } catch (error) {
            console.error('❌ Error creando llamada de prueba:', error);
            throw error;
        }
    }

    // ❌ MANEJAR ERRORES
    handleError(error) {
        console.error('🚨 Error en ULTRA FAST listener:', error);
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
        
        // Auto-remove después de 5 segundos
        setTimeout(() => {
            if (errorDiv.parentElement) {
                errorDiv.remove();
            }
        }, 5000);
        
        // Auto-retry después de 2 segundos
        setTimeout(() => {
            this.startListening();
        }, 2000);
    }
}

// ✅ 3. INICIALIZACIÓN GLOBAL
let ultraFastNotifications = null;

// Función para inicializar desde tu app
export function initializeUltraFastWaiterNotifications(waiterId) {
    // Solicitar permisos de notificación
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    // Intentar crear canal y pedir permiso para LocalNotifications en dispositivos
    try {
        if (Capacitor && Capacitor.isPluginAvailable && Capacitor.isPluginAvailable('LocalNotifications')) {
            import('@capacitor/local-notifications').then(({ LocalNotifications }) => {
                // Pedir permisos de LocalNotifications
                LocalNotifications.requestPermissions().then((perm) => {
                    console.log('LocalNotifications permissions:', perm)
                }).catch(err => {
                    console.warn('Error pidiendo permisos LocalNotifications:', err)
                })

                // Crear canal Android
                if (LocalNotifications.createChannel) {
                    LocalNotifications.createChannel({
                        id: 'mozo_waiter',
                        name: 'Mozo - Llamadas',
                        importance: 5,
                        visibility: 1,
                        description: 'Notificaciones de llamadas y eventos importantes para mozos'
                    }).then(() => {
                        console.log('✅ Canal mozo_waiter creado desde UltraFastWaiterNotifications')
                    }).catch(err => {
                        console.warn('⚠️ Error creando canal mozo_waiter:', err)
                    })
                }
            }).catch(err => console.warn('⚠️ No se pudo importar LocalNotifications:', err))
        }
    } catch (err) {
        console.warn('⚠️ Excepción al preparar LocalNotifications:', err)
    }
    
    // Limpiar instancia anterior si existe
    if (ultraFastNotifications) {
        ultraFastNotifications.stopListening();
    }
    
    // Crear instancia ULTRA RÁPIDA
    ultraFastNotifications = new UltraFastWaiterNotifications(waiterId);
    ultraFastNotifications.startListening();
    
    // Hacer disponible globalmente
    window.ultraFastNotifications = ultraFastNotifications;
    
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

/* Integración con estilos del Dashboard existente */
.notification-item.ultra-fast {
    border: 2px solid #ff6b6b;
    background: linear-gradient(135deg, #fff, #f8f9fa);
    box-shadow: 0 4px 15px rgba(255, 107, 107, 0.1);
}

.notification-item.ultra-fast.urgent {
    border-color: #e74c3c;
    animation: pulse-ultra 1s infinite;
}
`;

// Inyectar estilos ULTRA RÁPIDOS automáticamente
const ultraFastStyleSheet = document.createElement('style');
ultraFastStyleSheet.textContent = ultraFastStyles;
document.head.appendChild(ultraFastStyleSheet);

export default { UltraFastWaiterNotifications, initializeUltraFastWaiterNotifications };