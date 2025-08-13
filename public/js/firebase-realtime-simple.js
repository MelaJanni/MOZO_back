/**
 * Firebase Real-time Service SIMPLIFICADO para MOZO QR
 * Version ultra-optimizada para conexión instantánea
 */

class FirebaseSimpleRealtime {
    constructor() {
        this.db = null;
        this.initialized = false;
        this.listeners = new Map();
        this.config = null;
        console.log('🔥 Firebase Simple Real-time Service initialized');
    }

    /**
     * Inicialización ULTRA RÁPIDA
     */
    async initialize() {
        try {
            console.log('🚀 Starting ULTRA FAST Firebase initialization...');
            
            // 1. Obtener config desde el backend
            const response = await fetch('/api/firebase/config');
            const data = await response.json();
            
            console.log('📋 Firebase config received:', data);
            
            if (!data.success || !data.ready_for_realtime) {
                throw new Error('Firebase config not ready: ' + JSON.stringify(data.diagnostics));
            }
            
            this.config = data.firebase_config;
            
            // 2. Cargar Firebase SDK dinámicamente y rápido
            await this.loadFirebaseSDK();
            
            // 3. Inicializar Firebase App
            if (!firebase.apps.length) {
                firebase.initializeApp(this.config);
                console.log('✅ Firebase App initialized');
            }

            // 4. Configurar Firestore con configuración optimizada
            this.db = firebase.firestore();
            
            // 🚀 CONFIGURACIÓN ULTRA RÁPIDA para tiempo real
            this.db.settings({
                cacheSizeBytes: firebase.firestore.CACHE_SIZE_UNLIMITED,
                ignoreUndefinedProperties: true,
                merge: true
            });

            // 5. Configurar para obtener datos INMEDIATAMENTE del servidor
            this.db.enableNetwork();
            
            this.initialized = true;
            console.log('🎉 Firebase Simple Real-time ready!');
            
            return true;
            
        } catch (error) {
            console.error('❌ Firebase initialization failed:', error);
            throw error;
        }
    }

    /**
     * Carga SDK de Firebase con URLs directas
     */
    async loadFirebaseSDK() {
        if (typeof firebase !== 'undefined') {
            console.log('✅ Firebase SDK already loaded');
            return;
        }

        console.log('📦 Loading Firebase SDK...');
        
        // Versión específica conocida que funciona bien
        const version = '9.23.0';
        const scripts = [
            `https://www.gstatic.com/firebasejs/${version}/firebase-app-compat.js`,
            `https://www.gstatic.com/firebasejs/${version}/firebase-firestore-compat.js`,
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
        
        console.log('✅ Firebase SDK loaded successfully');
    }

    /**
     * Escuchar llamadas de mesa EN TIEMPO REAL
     */
    listenToTableCalls(tableId, callback) {
        if (!this.initialized) {
            console.error('🚫 Firebase not initialized');
            return null;
        }

        console.log(`👂 Setting up REAL-TIME listener for table ${tableId}`);

        try {
            const collectionPath = `tables/${tableId}/waiter_calls`;
            
            const unsubscribe = this.db.collection(collectionPath)
                .orderBy('called_at', 'desc')
                .limit(10)  // Solo las últimas 10 llamadas
                .onSnapshot({
                    includeMetadataChanges: false // Solo cambios reales, no cache
                }, 
                (snapshot) => {
                    const fromCache = snapshot.metadata.fromCache;
                    console.log(`📨 Table ${tableId} real-time update (from cache: ${fromCache})`);
                    
                    // 🚀 PROCESAR INMEDIATAMENTE todos los documentos
                    const calls = [];
                    snapshot.forEach(doc => {
                        const data = doc.data();
                        calls.push({
                            id: doc.id,
                            table_id: parseInt(data.table_id),
                            status: data.status,
                            waiter_name: data.waiter_name || 'Mozo',
                            called_at: data.called_at,
                            acknowledged_at: data.acknowledged_at,
                            completed_at: data.completed_at,
                            message: data.message,
                            _metadata: {
                                isFromCache: fromCache,
                                docSize: JSON.stringify(data).length
                            }
                        });
                    });
                    
                    console.log(`🔥 Processed ${calls.length} calls for table ${tableId}`);
                    
                    // Callback con los resultados
                    callback({
                        success: true,
                        calls,
                        tableId: parseInt(tableId),
                        fromCache,
                        timestamp: new Date(),
                        totalCalls: calls.length
                    });
                },
                (error) => {
                    console.error(`❌ Real-time error for table ${tableId}:`, error);
                    callback({
                        success: false,
                        error: error.message,
                        errorCode: error.code,
                        tableId: parseInt(tableId),
                        timestamp: new Date()
                    });
                });

            this.listeners.set(`table_calls_${tableId}`, unsubscribe);
            console.log(`✅ Real-time listener active for table ${tableId}`);
            return unsubscribe;
            
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