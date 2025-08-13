/**
 * Firebase Real-time Service para MOZO QR
 * Maneja conexiones Firestore para notificaciones en tiempo real
 */

class FirebaseRealtimeService {
    constructor() {
        this.db = null;
        this.config = null;
        this.listeners = new Map();
        this.initialized = false;
        this.retryCount = 0;
        this.maxRetries = 3;
    }

    /**
     * Inicializar Firebase con configuración del backend
     */
    async initialize() {
        try {
            console.log('🔥 Inicializando Firebase Real-time Service...');
            
            // Obtener configuración desde el backend
            const response = await fetch('/api/firebase/config');
            const data = await response.json();
            
            if (!data.success || !data.ready_for_realtime) {
                throw new Error('Firebase configuration not ready: ' + JSON.stringify(data.diagnostics));
            }
            
            this.config = data.firebase_config;
            console.log('✅ Firebase config obtenida:', this.config.projectId);

            // Verificar si Firebase está disponible globalmente
            if (typeof firebase === 'undefined') {
                await this.loadFirebaseSDK();
            }

            // Inicializar Firebase App
            if (!firebase.apps.length) {
                firebase.initializeApp(this.config);
                console.log('✅ Firebase App inicializada');
            }

            // Inicializar Authentication para acceso anónimo
            this.auth = firebase.auth();
            
            // Configurar autenticación anónima para acceso público desde QR
            try {
                await this.auth.signInAnonymously();
                console.log('✅ Autenticación anónima exitosa');
            } catch (authError) {
                console.warn('⚠️  Autenticación anónima falló, continuando sin auth:', authError.message);
                // Continuar sin autenticación - las reglas permiten lectura pública
            }

            // Inicializar Firestore
            this.db = firebase.firestore();
            
            // Configurar opciones de Firestore
            this.db.settings({
                cacheSizeBytes: firebase.firestore.CACHE_SIZE_UNLIMITED
            });

            this.initialized = true;
            console.log('🎉 Firebase Real-time Service listo!');
            
            return true;
            
        } catch (error) {
            console.error('❌ Error inicializando Firebase:', error);
            this.retryCount++;
            
            if (this.retryCount < this.maxRetries) {
                console.log(`🔄 Reintentando inicialización (${this.retryCount}/${this.maxRetries})...`);
                setTimeout(() => this.initialize(), 2000 * this.retryCount);
            } else {
                console.error('💥 Firebase initialization failed after max retries');
                throw error;
            }
        }
    }

    /**
     * Cargar Firebase SDK dinámicamente
     */
    async loadFirebaseSDK() {
        return new Promise((resolve, reject) => {
            if (typeof firebase !== 'undefined') {
                resolve();
                return;
            }

            console.log('📦 Cargando Firebase SDK...');
            
            const script1 = document.createElement('script');
            script1.src = 'https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js';
            
            script1.onload = () => {
                const script2 = document.createElement('script');
                script2.src = 'https://www.gstatic.com/firebasejs/9.23.0/firebase-firestore-compat.js';
                
                script2.onload = () => {
                    const script3 = document.createElement('script');
                    script3.src = 'https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js';
                    
                    script3.onload = () => {
                        console.log('✅ Firebase SDK completo cargado (app, firestore, auth)');
                        resolve();
                    };
                    
                    script3.onerror = (error) => {
                        console.warn('⚠️  Firebase Auth SDK failed to load, continuing without auth');
                        resolve(); // Continue without auth
                    };
                    document.head.appendChild(script3);
                };
                
                script2.onerror = reject;
                document.head.appendChild(script2);
            };
            
            script1.onerror = reject;
            document.head.appendChild(script1);
        });
    }

    /**
     * Escuchar llamadas de mozo para una mesa específica
     */
    listenToTableCalls(tableId, callback) {
        if (!this.initialized) {
            console.warn('🚫 Firebase not initialized, cannot listen to calls');
            return null;
        }

        const collectionPath = `tables/${tableId}/waiter_calls`;
        console.log(`👂 Listening to: ${collectionPath}`);

        try {
            const unsubscribe = this.db.collection(collectionPath)
                .where('status', '==', 'pending')
                .orderBy('called_at', 'desc')
                .limit(5)
                .onSnapshot({
                    // Incluir metadatos para debugging
                    includeMetadataChanges: true
                }, 
                (snapshot) => {
                    const fromCache = snapshot.metadata.fromCache;
                    const hasPendingWrites = snapshot.metadata.hasPendingWrites;
                    
                    console.log(`📨 Received ${snapshot.docs.length} call updates for table ${tableId} (cache: ${fromCache}, pending: ${hasPendingWrites})`);
                    
                    const calls = snapshot.docs.map(doc => ({
                        id: doc.id,
                        ...doc.data(),
                        _metadata: {
                            isFromCache: fromCache,
                            hasPendingWrites: hasPendingWrites
                        }
                    }));

                    callback({
                        success: true,
                        calls,
                        tableId,
                        fromCache,
                        timestamp: new Date()
                    });
                },
                (error) => {
                    console.error(`❌ Error listening to table ${tableId} calls:`, error);
                    
                    // Specific error handling for common Firestore errors
                    let userFriendlyError = error.message;
                    if (error.code === 'permission-denied') {
                        userFriendlyError = 'Sin permisos para acceder a las notificaciones';
                    } else if (error.code === 'failed-precondition') {
                        userFriendlyError = 'Índices de Firestore no configurados';
                    } else if (error.code === 'unavailable') {
                        userFriendlyError = 'Servicio Firebase temporalmente no disponible';
                    }
                    
                    callback({
                        success: false,
                        error: userFriendlyError,
                        errorCode: error.code,
                        tableId,
                        timestamp: new Date()
                    });
                });

            this.listeners.set(`table_calls_${tableId}`, unsubscribe);
            return unsubscribe;
            
        } catch (error) {
            console.error(`💥 Failed to setup listener for table ${tableId}:`, error);
            callback({
                success: false,
                error: error.message,
                tableId,
                timestamp: new Date()
            });
            return null;
        }
    }

    /**
     * Escuchar cambios de estado de una mesa
     */
    listenToTableStatus(tableId, callback) {
        if (!this.initialized) {
            console.warn('🚫 Firebase not initialized, cannot listen to status');
            return null;
        }

        const docPath = `tables/${tableId}/status/current`;
        console.log(`👂 Listening to: ${docPath}`);

        try {
            const unsubscribe = this.db.doc(docPath)
                .onSnapshot(
                    (doc) => {
                        if (doc.exists) {
                            console.log(`📊 Status update for table ${tableId}`);
                            callback({
                                success: true,
                                status: doc.data(),
                                tableId,
                                timestamp: new Date()
                            });
                        }
                    },
                    (error) => {
                        console.error(`❌ Error listening to table ${tableId} status:`, error);
                        callback({
                            success: false,
                            error: error.message,
                            tableId,
                            timestamp: new Date()
                        });
                    }
                );

            this.listeners.set(`table_status_${tableId}`, unsubscribe);
            return unsubscribe;
            
        } catch (error) {
            console.error(`💥 Failed to setup status listener for table ${tableId}:`, error);
            return null;
        }
    }

    /**
     * Desuscribirse de todas las escuchas
     */
    unsubscribeAll() {
        console.log(`🔇 Unsubscribing from ${this.listeners.size} listeners`);
        
        this.listeners.forEach((unsubscribe, key) => {
            if (typeof unsubscribe === 'function') {
                unsubscribe();
                console.log(`✅ Unsubscribed from ${key}`);
            }
        });
        
        this.listeners.clear();
    }

    /**
     * Verificar conexión y estado
     */
    async checkConnection() {
        if (!this.initialized) {
            return { connected: false, error: 'Not initialized' };
        }

        try {
            // Probar una consulta simple
            const testDoc = await this.db.collection('_connection_test').limit(1).get();
            
            return {
                connected: true,
                fromCache: testDoc.metadata.fromCache,
                timestamp: new Date()
            };
        } catch (error) {
            return {
                connected: false,
                error: error.message,
                timestamp: new Date()
            };
        }
    }

    /**
     * Obtener diagnóstico completo
     */
    async getDiagnostics() {
        return {
            initialized: this.initialized,
            config_loaded: !!this.config,
            firestore_ready: !!this.db,
            active_listeners: this.listeners.size,
            connection: await this.checkConnection(),
            retry_count: this.retryCount
        };
    }
}

// Crear instancia global
window.FirebaseRealtimeService = new FirebaseRealtimeService();

// Auto-inicializar si la página está lista
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.FirebaseRealtimeService.initialize();
    });
} else {
    window.FirebaseRealtimeService.initialize();
}

console.log('📦 Firebase Real-time Service loaded and ready!');