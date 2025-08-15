/**
 * Firebase Real-time Service SIMPLIFICADO para MOZO QR
 * Version ultra-optimizada para conexión instantánea con REALTIME DATABASE
 */

class FirebaseSimpleRealtime {
    constructor() {
        this.db = null;
        this.initialized = false;
        this.listeners = new Map();
        this.config = null;
        console.log('🔥 Firebase Simple Real-time Service initialized (REALTIME DATABASE)');
    }

    /**
     * Inicialización ULTRA RÁPIDA con REALTIME DATABASE
     */
    async initialize() {
        try {
            console.log('🚀 Starting ULTRA FAST Firebase Realtime Database initialization...');
            
            // Config directo optimizado para Realtime Database
            this.config = {
                projectId: "mozoqr-7d32c",
                apiKey: "AIzaSyDGJJKNfSSxD6YnXnNjwRb6VUtPSyGN5CM",
                authDomain: "mozoqr-7d32c.firebaseapp.com",
                databaseURL: "https://mozoqr-7d32c-default-rtdb.firebaseio.com",
                storageBucket: "mozoqr-7d32c.appspot.com"
            };
            
            console.log('📋 Firebase Realtime Database config:', this.config);
            
            // 2. Cargar Firebase SDK para Realtime Database
            await this.loadFirebaseSDK();
            
            // 3. Inicializar Firebase App
            if (!firebase.apps.length) {
                firebase.initializeApp(this.config);
                console.log('✅ Firebase App initialized');
            }

            // 4. Configurar REALTIME DATABASE
            this.db = firebase.database();
            console.log('✅ Firebase Realtime Database reference obtained');
            
            this.initialized = true;
            console.log('🎉 Firebase Realtime Database ready!');
            
            return true;
            
        } catch (error) {
            console.error('❌ Firebase Realtime Database initialization failed:', error);
            throw error;
        }
    }

    /**
     * Carga SDK de Firebase Realtime Database
     */
    async loadFirebaseSDK() {
        if (typeof firebase !== 'undefined') {
            console.log('✅ Firebase SDK already loaded');
            return;
        }

        console.log('📦 Loading Firebase Realtime Database SDK...');
        
        // Scripts para Realtime Database
        const version = '9.23.0';
        const scripts = [
            `https://www.gstatic.com/firebasejs/${version}/firebase-app-compat.js`,
            `https://www.gstatic.com/firebasejs/${version}/firebase-database-compat.js`,
        ];

        for (const scriptUrl of scripts) {
            await new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = scriptUrl;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }
        
        console.log('✅ Firebase Realtime Database SDK loaded successfully');
    }

    /**
     * Escuchar llamadas de mesa EN TIEMPO REAL con REALTIME DATABASE
     */
    listenToTableCalls(tableId, callback) {
        if (!this.initialized) {
            console.error('🚫 Firebase Realtime Database not initialized');
            return null;
        }

        console.log(`👂 Setting up REAL-TIME listener for table ${tableId} - REALTIME DATABASE`);

        try {
            // 🎯 ESCUCHAR EN EL PATH CORRECTO: /tables/call_status/
            const callStatusRef = this.db.ref('tables/call_status');
            console.log('🎧 Listener configurado en path: /tables/call_status/');
            
            // Listener para cambios en cualquier call del table
            const unsubscribe = callStatusRef.on('value', (snapshot) => {
                console.log(`📨 Table ${tableId} real-time update (from cache: false)`);
                
                // 🚀 PROCESAR todos los calls activos
                const calls = [];
                const data = snapshot.val();
                
                if (data) {
                    Object.keys(data).forEach(callId => {
                        const callData = data[callId];
                        // Filtrar por table_id si está disponible
                        if (!callData.table_id || parseInt(callData.table_id) === parseInt(tableId)) {
                            calls.push({
                                id: callId,
                                table_id: parseInt(callData.table_id || tableId),
                                status: callData.status,
                                waiter_name: callData.waiter_name || 'Mozo',
                                waiter_id: callData.waiter_id,
                                called_at: callData.called_at,
                                acknowledged_at: callData.acknowledged_at,
                                completed_at: callData.completed_at,
                                message: callData.message,
                                _metadata: {
                                    isFromCache: false,
                                    docSize: JSON.stringify(callData).length
                                }
                            });
                        }
                    });
                }
                
                console.log(`🔥 Processed ${calls.length} calls for table ${tableId}`);
                
                // Callback con los resultados
                callback({
                    success: true,
                    calls,
                    tableId: parseInt(tableId),
                    fromCache: false,
                    timestamp: new Date(),
                    totalCalls: calls.length
                });
            }, (error) => {
                console.error(`❌ Real-time error for table ${tableId}:`, error);
                callback({
                    success: false,
                    error: error.message,
                    errorCode: error.code,
                    tableId: parseInt(tableId),
                    timestamp: new Date()
                });
            });

            this.listeners.set(`table_calls_${tableId}`, () => callStatusRef.off('value', unsubscribe));
            console.log(`✅ Real-time listener active for table ${tableId}`);
            return () => callStatusRef.off('value', unsubscribe);
            
        } catch (error) {
            console.error(`💥 Failed to setup listener for table ${tableId}:`, error);
            callback({
                success: false,
                error: error.message,
                tableId: parseInt(tableId),
                timestamp: new Date()
            });
            return null;
        }
    }

    /**
     * Forzar inicialización inmediata (usado por el frontend)
     */
    forceInit() {
        if (!this.initialized) {
            return this.initialize();
        }
        return Promise.resolve(true);
    }

    /**
     * Limpiar todos los listeners
     */
    cleanup() {
        console.log(`🔇 Cleaning up ${this.listeners.size} listeners`);
        this.listeners.forEach((unsubscribe) => {
            if (typeof unsubscribe === 'function') {
                unsubscribe();
            }
        });
        this.listeners.clear();
    }
}

// Crear instancia global
window.FirebaseRealtimeService = new FirebaseSimpleRealtime();

// Auto-inicializar INMEDIATAMENTE
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('📄 DOM ready, initializing Firebase...');
        window.FirebaseRealtimeService.initialize().catch(console.error);
    });
} else {
    console.log('📄 DOM already ready, initializing Firebase immediately...');
    window.FirebaseRealtimeService.initialize().catch(console.error);
}

console.log('🔥 Firebase Simple Real-time Service loaded and ready!');