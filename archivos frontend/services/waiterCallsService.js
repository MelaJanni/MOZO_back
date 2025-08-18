/**
 * Waiter Calls Service
 * 
 * Servicio para manejar las llamadas de mesa a mozo
 * Implementa todas las APIs del sistema mesa-mozo
 */

import { apiService } from './api'

class WaiterCallsService {
  // ===== LLAMADAS DE MESA =====

  /**
   * Mesa llama al mozo (endpoint público para QR codes)
   * POST /api/tables/{table_id}/call-waiter
   */
  async callWaiter(tableId, options = {}) {
    const { message, urgency = 'normal', client_info } = options
    
    try {
      const response = await apiService.callWaiter(tableId)
      
      return response.data
    } catch (error) {
      console.error('Error calling waiter:', error)
      throw this.handleError(error)
    }
  }

  // ===== MOZO - GESTIÓN DE LLAMADAS =====

  /**
   * Obtener llamadas pendientes del mozo
   * GET /api/waiter/calls/pending
   */
  async getPendingCalls() {
    try {
  const response = await apiService.getPendingCalls()
  return response.data
    } catch (error) {
  // console.error('Error getting pending calls:', error)
      throw this.handleError(error)
    }
  }

  /**
   * Confirmar/Reconocer llamada (mozo presiona "OK")
   * POST /api/waiter/calls/{call_id}/acknowledge
   */
  async acknowledgCall(callId) {
    try {
      const response = await apiService.acknowledgeCall(callId)
      return response.data
    } catch (error) {
  // console.error('Error acknowledging call:', error)
      throw this.handleError(error)
    }
  }

  /**
   * Completar llamada (mozo terminó de atender)
   * POST /api/waiter/calls/{call_id}/complete
   */
  async completeCall(callId) {
    try {
      const response = await apiService.completeCall(callId)
      return response.data
    } catch (error) {
  // console.error('Error completing call:', error)
      throw this.handleError(error)
    }
  }

  // ===== HISTORIAL =====

  /**
   * Obtener historial de llamadas del mozo
   * GET /api/waiter/calls/history
   */
  async getWaiterCallHistory(filter = 'today', page = 1, limit = 20) {
    try {
  // Use the dedicated apiService helper to avoid calling a non-existing `get` method
  const response = await apiService.getWaiterCallHistory({ filter, page, limit })
  return response.data
    } catch (error) {
  // console.error('Error getting waiter call history:', error)
      throw this.handleError(error)
    }
  }

  /**
   * Obtener historial de llamadas del admin
   * GET /api/admin/calls/history
   */
  async getAdminCallHistory(filter = 'today', page = 1, limit = 20) {
    try {
  const response = await apiService.getAdminCallHistory({ filter, page, limit })
  return response.data
    } catch (error) {
  // console.error('Error getting admin call history:', error)
      throw this.handleError(error)
    }
  }

  // ===== GESTIÓN DE MESAS - INDIVIDUAL =====

  /**
   * Activar mozo en una mesa específica
   * POST /api/waiter/tables/{table_id}/activate
   */
  async activateTable(tableId) {
    // console.log('🎯 Activando mesa individual:', tableId)
    try {
      const response = await apiService.activateTable(tableId)
      // console.log('✅ Respuesta activar mesa:', response.data)
      return response.data
    } catch (error) {
      console.error('❌ Error activating table:', error)
      console.error('❌ Error details:', error.response?.data)
      throw this.handleError(error)
    }
  }

  /**
   * Desactivar mozo de una mesa específica
   * DELETE /api/waiter/tables/{table_id}/activate
   */
  async deactivateTable(tableId) {
    try {
      const response = await apiService.deactivateTable(tableId)
      return response.data
    } catch (error) {
      console.error('Error deactivating table:', error)
      throw this.handleError(error)
    }
  }

  // ===== GESTIÓN DE MESAS - MÚLTIPLES =====

  /**
   * Activar múltiples mesas de una vez
   * POST /api/waiter/tables/activate/multiple
   */
  async activateMultipleTables(tableIds) {
    // console.log('🎯 Activando múltiples mesas:', tableIds)
    try {
      const response = await apiService.activateMultipleTables({
        table_ids: tableIds
      })
      // console.log('✅ Respuesta activar múltiples mesas:', response.data)
      return response.data
    } catch (error) {
      console.error('❌ Error activating multiple tables:', error)
      console.error('❌ Error details:', error.response?.data)
      throw this.handleError(error)
    }
  }

  /**
   * Desactivar múltiples mesas de una vez
   * POST /api/waiter/tables/deactivate/multiple
   */
  async deactivateMultipleTables(tableIds) {
    try {
      const response = await apiService.deactivateMultipleTables({
        table_ids: tableIds
      })
      return response.data
    } catch (error) {
      console.error('Error deactivating multiple tables:', error)
      throw this.handleError(error)
    }
  }

  // ===== SILENCIADO DE MESAS =====

  /**
   * Silenciar mesa manualmente
   * POST /api/waiter/tables/{table_id}/silence
   */
  async silenceTable(tableId, durationMinutes = 30, notes = '') {
    // console.log('🔇 Silenciando mesa:', tableId, 'por', durationMinutes, 'minutos')
    // Asegurar que notes sea siempre un string
    const cleanNotes = typeof notes === 'string' ? notes : ''
    const payload = {
      duration_minutes: durationMinutes,
      notes: cleanNotes
    }
    // console.log('📤 Enviando payload para silenciar:', payload)
    
    try {
      const response = await apiService.silenceTable(tableId, payload)
      console.log('✅ Respuesta silenciar mesa:', response.data)
      return response.data
    } catch (error) {
      console.error('❌ Error silencing table:', error)
      console.error('❌ Error details:', error.response?.data)
      throw this.handleError(error)
    }
  }

  /**
   * Quitar silencio de mesa
   * DELETE /api/waiter/tables/{table_id}/silence
   */
  async unsilenceTable(tableId) {
    console.log('🔊 Quitando silencio de mesa:', tableId)
    try {
      const response = await apiService.unsilenceTable(tableId)
      console.log('✅ Respuesta quitar silencio:', response.data)
      return response.data
    } catch (error) {
      console.error('❌ Error unsilencing table:', error)
      console.error('❌ Error details:', error.response?.data)
    }
  }

  /**
   * Silenciar múltiples mesas
   * POST /api/waiter/tables/silence/multiple
   */
  async silenceMultipleTables(tableIds, durationMinutes = 30, notes = '') {
    try {
      const response = await apiService.post('/waiter/tables/silence/multiple', {
        table_ids: tableIds,
        duration_minutes: durationMinutes,
        notes
      })
      return response.data
    } catch (error) {
      console.error('Error silencing multiple tables:', error)
      throw this.handleError(error)
    }
  }

  /**
   * Quitar silencio de múltiples mesas
   * POST /api/waiter/tables/unsilence/multiple
   */
  async unsilenceMultipleTables(tableIds) {
    try {
      const response = await apiService.post('/waiter/tables/unsilence/multiple', {
        table_ids: tableIds
      })
      return response.data
    } catch (error) {
      console.error('Error unsilencing multiple tables:', error)
      throw this.handleError(error)
    }
  }

  /**
   * Obtener mesas silenciadas
   * GET /api/waiter/tables/silenced
   */
  async getSilencedTables() {
    try {
      const response = await apiService.getSilencedTables()
      return response.data
    } catch (error) {
      console.error('Error getting silenced tables:', error)
      throw this.handleError(error)
    }
  }

  // ===== CONSULTA DE MESAS =====

  /**
   * Obtener mesas asignadas al mozo
   * GET /api/waiter/tables/assigned
   */
  async getAssignedTables() {
    try {
      const response = await apiService.getAssignedTables()
      return response.data
    } catch (error) {
      console.error('Error getting assigned tables:', error)
      throw this.handleError(error)
    }
  }

  /**
   * Obtener mesas disponibles para asignar
   * GET /api/waiter/tables/available
   */
  async getAvailableTables() {
    try {
      const response = await apiService.getAvailableTables()
      return response.data
    } catch (error) {
      console.error('Error getting available tables:', error)
      throw this.handleError(error)
    }
  }

  // ===== GESTIÓN DE NEGOCIOS =====

  /**
   * Obtener negocios donde el mozo trabaja
   * GET /api/waiter/businesses
   */
  async getWaiterBusinesses() {
    try {
      const response = await apiService.getWaiterBusinesses()
      return response.data
    } catch (error) {
      console.error('Error getting waiter businesses:', error)
      throw this.handleError(error)
    }
  }

  /**
   * Seleccionar negocio activo para trabajar
   * POST /api/waiter/businesses/active
   */
  async setActiveWaiterBusiness(businessId) {
    try {
      const response = await apiService.setActiveWaiterBusiness(businessId)
      return response.data
    } catch (error) {
      console.error('Error setting active business:', error)
      throw this.handleError(error)
    }
  }

  /**
   * Obtener negocio actualmente activo
   * GET /api/waiter/businesses/active
   */
  async getActiveWaiterBusiness() {
    try {
      const response = await apiService.getActiveWaiterBusiness()
      return response.data
    } catch (error) {
      console.error('Error getting active business:', error)
      throw this.handleError(error)
    }
  }

  /**
   * Obtener mesas de un negocio específico
   * GET /api/waiter/businesses/{business_id}/tables
   */
  async getWaiterBusinessTables(businessId) {
    // console.log('🏢 Obteniendo mesas del negocio:', businessId)
    try {
      const response = await apiService.getWaiterBusinessTables(businessId)
      // console.log('📋 Respuesta mesas del negocio desde API:', response.data)
      return response.data
    } catch (error) {
      console.error('❌ Error getting business tables:', error)
      console.error('❌ Error details:', error.response?.data)
      throw this.handleError(error)
    }
  }

  /**
   * Unirse a un negocio con código
   * POST /api/waiter/businesses/join
   */
  async joinBusinessWithCode(code) {
    try {
      const response = await apiService.joinBusinessWithCode(code)
      return response.data
    } catch (error) {
      console.error('Error joining business with code:', error)
      throw this.handleError(error)
    }
  }

  /**
   * Salir de un negocio
   * DELETE /api/waiter/businesses/{business_id}/leave
   */
  async leaveWaiterBusiness(businessId) {
    try {
      const response = await apiService.leaveWaiterBusiness(businessId)
      return response.data
    } catch (error) {
      console.error('Error leaving business:', error)
      throw this.handleError(error)
    }
  }

  // ===== ADMIN - MESAS SILENCIADAS =====

  /**
   * Admin: Obtener mesas silenciadas
   * GET /api/admin/tables/silenced
   */
  async getAdminSilencedTables() {
    try {
      const response = await apiService.get('/admin/tables/silenced')
      return response.data
    } catch (error) {
      console.error('Error getting admin silenced tables:', error)
      throw this.handleError(error)
    }
  }

  /**
   * Admin: Quitar silencio de mesa
   * DELETE /api/admin/tables/{table_id}/silence
   */
  async adminUnsilenceTable(tableId) {
    try {
      const response = await apiService.delete(`/admin/tables/${tableId}/silence`)
      return response.data
    } catch (error) {
      console.error('Error admin unsilencing table:', error)
      throw this.handleError(error)
    }
  }

  // ===== HELPERS =====

  /**
   * Manejo centralizado de errores
   */
  handleError(error) {
    if (error.response?.data) {
      return {
        success: false,
        message: error.response.data.message || 'Error en la solicitud',
        code: error.response.status,
        data: error.response.data
      }
    }
    
    return {
      success: false,
      message: error.message || 'Error de conexión',
      code: 'NETWORK_ERROR'
    }
  }

  /**
   * Formatear tiempo de respuesta
   */
  formatResponseTime(seconds) {
    if (seconds < 60) {
      return `${seconds}s`
    } else if (seconds < 3600) {
      const minutes = Math.floor(seconds / 60)
      const remainingSeconds = seconds % 60
      return remainingSeconds > 0 ? `${minutes}m ${remainingSeconds}s` : `${minutes}m`
    } else {
      const hours = Math.floor(seconds / 3600)
      const minutes = Math.floor((seconds % 3600) / 60)
      return minutes > 0 ? `${hours}h ${minutes}m` : `${hours}h`
    }
  }

  /**
   * Formatear tiempo transcurrido desde una fecha
   */
  formatTimeAgo(timestamp) {
    const now = new Date()
    const date = new Date(timestamp)
    const diffInMs = now - date
    const diffInMinutes = Math.floor(diffInMs / 60000)

    if (diffInMinutes < 1) {
      return 'Ahora'
    } else if (diffInMinutes < 60) {
      return `${diffInMinutes}m`
    } else if (diffInMinutes < 1440) {
      const hours = Math.floor(diffInMinutes / 60)
      return `${hours}h`
    } else {
      const days = Math.floor(diffInMinutes / 1440)
      return `${days}d`
    }
  }

  /**
   * Determinar el color de urgencia
   */
  getUrgencyColor(urgency, minutesAgo = 0) {
    if (urgency === 'high') {
      return '#dc3545' // Rojo para urgente
    } else if (minutesAgo > 5) {
      return '#fd7e14' // Naranja para llamadas viejas
    } else {
      return '#007bff' // Azul para normal
    }
  }

  /**
   * Determinar el ícono de urgencia
   */
  getUrgencyIcon(urgency, minutesAgo = 0) {
    if (urgency === 'high') {
      return '🚨'
    } else if (minutesAgo > 5) {
      return '⏰'
    } else {
      return '🔔'
    }
  }
}

const waiterCallsService = new WaiterCallsService()
export default waiterCallsService