import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { apiService } from '@/services/api'

export const useWaiterStore = defineStore('waiter', () => {
  // --- STATE ---
  const businessId = ref(localStorage.getItem('waiterBusinessId') || null)
  const businessData = ref(null)
  const tables = ref([])
  const profiles = ref([])
  const notifications = ref([])
  const isLoading = ref(false)
  const error = ref(null)
  
  // --- GETTERS ---
  const isAssociated = computed(() => !!businessId.value)
  const activeNotifications = computed(() => 
    notifications.value.filter(n => n.status === 'pending').sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp))
  )
  
  // --- HELPERS ---
  const normalizeTable = (t) => ({
    ...t,
    // El front espera estas propiedades
    name: String(t.number),
    notifications_on: t.notifications_enabled ?? true,
    is_muted: !(t.notifications_enabled ?? true),
    color: t.color ?? null,
    alert: false // Ajustar según la lógica de alertas que prefieras
  })
  
  // --- ACTIONS ---

  const associateBusiness = async (code) => {
    isLoading.value = true
    error.value = null
    try {
      const response = await apiService.waiterOnboard(code)
      const { business } = response.data
      businessId.value = business.id
      businessData.value = business
      localStorage.setItem('waiterBusinessId', business.id)
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al asociar el negocio.'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  const fetchInitialData = async () => {
    if (!isAssociated.value) return
    isLoading.value = true
    error.value = null
    try {
      const [tablesData, profilesData, notificationsData] = await Promise.all([
        apiService.getWaiterTables(),
        apiService.listWaiterProfiles(),
        apiService.getWaiterNotifications()
      ])
      const rawTables = tablesData.data?.tables ?? tablesData.data
      tables.value = Array.isArray(rawTables) ? rawTables.map(normalizeTable) : []
      profiles.value = profilesData.data
      notifications.value = notificationsData.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al cargar los datos iniciales.'
      console.error(err)
    } finally {
      isLoading.value = false
    }
  }
  
  const toggleTableNotifications = async (table) => {
    const originalStatus = table.notifications_on
    table.notifications_on = !originalStatus
    table.is_muted = !table.notifications_on
    try {
      await apiService.toggleTableNotifications(table.id, { value: table.notifications_on })
    } catch (err) {
      table.notifications_on = originalStatus // Revert
      table.is_muted = !originalStatus
      error.value = err.response?.data?.message || 'Error al actualizar la mesa.'
      console.error(err)
    }
  }
  
  const fetchNotifications = async () => {
    try {
      const response = await apiService.getWaiterNotifications()
      notifications.value = response.data
    } catch (err) {
      console.error('Error fetching notifications:', err)
    }
  }

  const handleNotification = async (notificationId, action) => {
    try {
      await apiService.handleWaiterNotification(notificationId, { action })
      notifications.value = notifications.value.filter(n => n.id !== notificationId)
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al procesar la notificación.'
      console.error(err)
    }
  }
  
  const createProfile = async (profileName) => {
    try {
      const response = await apiService.createWaiterProfile({ name: profileName })
      profiles.value.push(response.data)
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al crear el perfil.'
      console.error(err)
    }
  }
  
  const deleteProfile = async (profileId) => {
    try {
      await apiService.deleteWaiterProfile(profileId)
      profiles.value = profiles.value.filter(p => p.id !== profileId)
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al eliminar el perfil.'
      console.error(err)
    }
  }

  const clearWaiterData = () => {
    businessId.value = null
    businessData.value = null
    tables.value = []
    profiles.value = []
    notifications.value = []
    localStorage.removeItem('waiterBusinessId')
  }

  return {
    // State
    businessId,
    businessData,
    tables,
    profiles,
    notifications,
    isLoading,
    error,
    // Getters
    isAssociated,
    activeNotifications,
    // Actions
    associateBusiness,
    fetchInitialData,
    toggleTableNotifications,
    fetchNotifications,
    handleNotification,
    createProfile,
    deleteProfile,
    clearWaiterData
  }
}) 