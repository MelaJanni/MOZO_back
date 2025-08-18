import apiService from './api'

export default {
  
  async getNotifications() {
    try {
      const response = await apiService.getNotifications()
      return response.data
    } catch (error) {
      console.error('Error obteniendo notificaciones:', error)
      throw error
    }
  },

  async handleNotification(notificationId, action) {
    try {
      const response = await apiService.handleNotification(notificationId, { action })
      return response.data
    } catch (error) {
      console.error('Error manejando notificación:', error)
      throw error
    }
  },

  async globalNotifications(enabled) {
    try {
      const response = await apiService.globalNotifications({ enabled })
      return response.data
    } catch (error) {
      console.error('Error configurando notificaciones globales:', error)
      throw error
    }
  },

  
  async getWaiterNotifications() {
    try {
      const response = await apiService.getWaiterNotifications()
      return response.data
    } catch (error) {
      console.error('Error obteniendo notificaciones de mozo:', error)
      throw error
    }
  },

  async handleWaiterNotification(notificationId, action) {
    try {
      const response = await apiService.handleWaiterNotification(notificationId, { action })
      return response.data
    } catch (error) {
      console.error('Error manejando notificación de mozo:', error)
      throw error
    }
  },

  async waiterGlobalNotifications(enabled) {
    try {
      const response = await apiService.waiterGlobalNotifications({ enabled })
      return response.data
    } catch (error) {
      console.error('Error configurando notificaciones globales de mozo:', error)
      throw error
    }
  },

  async waiterToggleTableNotifications(tableId) {
    try {
      const response = await apiService.waiterToggleTableNotifications(tableId)
      return response.data
    } catch (error) {
      console.error('Error alternando notificaciones de mesa para mozo:', error)
      throw error
    }
  },

  
  async getAdminNotifications() {
    try {
      const response = await apiService.getAdminNotifications()
      return response.data
    } catch (error) {
      console.error('Error obteniendo notificaciones de administrador:', error)
      throw error
    }
  },

  
  async storeDeviceToken(token, platform = 'web', userId = null) {
    try {
      const payload = { token, platform }
      if (userId) {
        payload.user_id = userId
      }
      const response = await apiService.storeDeviceToken(payload)
      return response.data
    } catch (error) {
      console.error('Error guardando token de dispositivo:', error)
      throw error
    }
  },

  async deleteDeviceToken(tokenId) {
    try {
      const response = await apiService.deleteDeviceToken(tokenId)
      return response.data
    } catch (error) {
      console.error('Error eliminando token de dispositivo:', error)
      throw error
    }
  },

  async getDeviceTokens(userId) {
    try {
      const response = await apiService.getDeviceTokens(userId)
      return response.data
    } catch (error) {
      console.error('Error obteniendo tokens de dispositivo:', error)
      throw error
    }
  },

  // Nuevas funciones para envío de notificaciones FCM
  async sendNotificationToAll(title, body, data = null) {
    try {
      const payload = { title, body }
      if (data) payload.data = data
      
      const response = await apiService.sendNotificationToAll(payload)
      return response.data
    } catch (error) {
      console.error('Error enviando notificación a todos:', error)
      throw error
    }
  },

  async sendNotificationToUser(userId, title, body, data = null) {
    try {
      const payload = { user_id: userId, title, body }
      if (data) payload.data = data
      
      const response = await apiService.sendNotificationToUser(payload)
      return response.data
    } catch (error) {
      console.error('Error enviando notificación a usuario:', error)
      throw error
    }
  },

  async sendNotificationToDevice(token, title, body, data = null) {
    try {
      const payload = { token, title, body }
      if (data) payload.data = data
      
      const response = await apiService.sendNotificationToDevice(payload)
      return response.data
    } catch (error) {
      console.error('Error enviando notificación a dispositivo:', error)
      throw error
    }
  },

  async sendNotificationToTopic(topic, title, body, data = null) {
    try {
      const payload = { topic, title, body }
      if (data) payload.data = data
      
      const response = await apiService.sendNotificationToTopic(payload)
      return response.data
    } catch (error) {
      console.error('Error enviando notificación a topic:', error)
      throw error
    }
  },

  async subscribeToTopic(tokens, topic) {
    try {
      const payload = { tokens: Array.isArray(tokens) ? tokens : [tokens], topic }
      const response = await apiService.subscribeToTopic(payload)
      return response.data
    } catch (error) {
      console.error('Error suscribiendo a topic:', error)
      throw error
    }
  },

  async unsubscribeFromTopic(tokens, topic) {
    try {
      const payload = { tokens: Array.isArray(tokens) ? tokens : [tokens], topic }
      const response = await apiService.unsubscribeFromTopic(payload)
      return response.data
    } catch (error) {
      console.error('Error desuscribiendo de topic:', error)
      throw error
    }
  },

  
  async toggleTableNotifications(tableId) {
    try {
      const response = await apiService.toggleTableNotifications(tableId)
      return response.data
    } catch (error) {
      console.error('Error alternando notificaciones de mesa:', error)
      throw error
    }
  },

  
  async callWaiter(tableId) {
    try {
      const response = await apiService.callWaiter(tableId)
      return response.data
    } catch (error) {
      console.error('Error llamando al camarero:', error)
      throw error
    }
  },

  
  async markAllAsRead(notifications) {
    try {
      const promises = notifications.map(notification => 
        this.handleNotification(notification.id, 'mark_as_read')
      )
      return await Promise.all(promises)
    } catch (error) {
      console.error('Error marcando todas como leídas:', error)
      throw error
    }
  },

  async deleteAllRead(notifications) {
    try {
      const promises = notifications.map(notification => 
        this.handleNotification(notification.id, 'delete')
      )
      return await Promise.all(promises)
    } catch (error) {
      console.error('Error eliminando todas las leídas:', error)
      throw error
    }
  },

  async getNotificationsByRole(role) {
    try {
      switch (role) {
        case 'waiter':
          return await this.getWaiterNotifications()
        case 'admin':
          return await this.getAdminNotifications()
        default:
          return await this.getNotifications()
      }
    } catch (error) {
      console.error('Error obteniendo notificaciones por rol:', error)
      throw error
    }
  },

  async healthCheck() {
    try {
      return await apiService.healthCheck()
    } catch (error) {
      console.error('Error en el health check de la API:', error)
      throw error
    }
  },

  async testWebSocketConnection() {
    try {
      //console.log('🔍 Probando conexión WebSocket...')
      
      const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY
      const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER

      //console.log('🔍 Variables de entorno:', {
      //  pusherKey: pusherKey ? 'Definida' : 'No definida',
      //  pusherCluster: pusherCluster ? 'Definida' : 'No definida',
      //  pusherKeyValue: pusherKey ? `${pusherKey.substring(0, 8)}...` : 'undefined',
      //  pusherClusterValue: pusherCluster || 'undefined'
      //})
      
      if (!pusherKey || !pusherCluster) {
        throw new Error('Variables de entorno de Pusher no configuradas')
      }
      
      const token = localStorage.getItem('token')
      if (!token) {
        throw new Error('No hay token de autenticación')
      }
      
      //console.log('🔍 Token de autenticación:', token ? 'Presente' : 'Ausente')
      //console.log('🔍 Token value:', token ? `${token.substring(0, 20)}...` : 'undefined')
      
      const user = localStorage.getItem('user')
      const userData = user ? JSON.parse(user) : null
      //console.log('🔍 Usuario:', userData ? `ID: ${userData.id}, Nombre: ${userData.name}` : 'No encontrado')
      
      //console.log('🔍 Importando módulo echo...')
      const { initializeEcho } = await import('./echo')
      // console.log('🔍 Módulo echo importado correctamente')

      // console.log('🔍 Inicializando Echo...')
      const echo = initializeEcho()
      // console.log('🔍 Echo inicializado:', !!echo)
      
      if (!echo) {
        throw new Error('No se pudo inicializar Echo')
      }
      
      if (!echo.connector || !echo.connector.pusher) {
        throw new Error('Pusher no está disponible en Echo')
      }
      
      // console.log('🔍 Pusher disponible en Echo')
      
      // console.log('🔍 Esperando conexión WebSocket...')
      await new Promise((resolve, reject) => {
        const timeout = setTimeout(() => {
          // console.log('🔍 Timeout en la conexión WebSocket')
          reject(new Error('Timeout en la conexión WebSocket (5 segundos)'))
        }, 5000)
        
        echo.connector.pusher.connection.bind('connected', () => {
          clearTimeout(timeout)
        // console.log('🔍 WebSocket conectado exitosamente')
          resolve()
        })
        
        echo.connector.pusher.connection.bind('error', (error) => {
          clearTimeout(timeout)
          console.error('🔍 Error en WebSocket:', error)
          reject(new Error(`Error de conexión: ${error.message || 'Error desconocido'}`))
        })
        
        echo.connector.pusher.connection.bind('disconnected', () => {
          // console.log('🔍 WebSocket desconectado')
        })
        
        echo.connector.pusher.connection.bind('connecting', () => {
          // console.log('🔍 WebSocket conectando...')
        })
      })

      // console.log('🔍 Conexión WebSocket exitosa')
      return { success: true, message: 'Conexión WebSocket exitosa' }
    } catch (error) {
      console.error('🔍 Error en testWebSocketConnection:', error)
      console.error('🔍 Stack trace:', error.stack)
      throw error
    }
  }
} 