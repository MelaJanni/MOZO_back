import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useAuthStore } from './auth'
import { apiService } from '@/services/api'

export const useUserStore = defineStore('user', () => {
  const userRole = ref(localStorage.getItem('userRole') || null) // 'admin' o 'waiter'
  const userProfile = ref(null)
  const isLoading = ref(false)
  const error = ref(null)
  
  const isAdmin = computed(() => userRole.value === 'admin')
  const isWaiter = computed(() => userRole.value === 'waiter')
  
  const setRole = (role) => {
    if (role === 'admin' || role === 'waiter') {
      userRole.value = role
      localStorage.setItem('userRole', role)
      return true
    } else {
      error.value = 'Rol inválido'
      return false
    }
  }
  
  const fetchProfile = async () => {
    isLoading.value = true
    error.value = null
    
    try {
      console.log('Obteniendo perfil del usuario')
      
      const mockProfile = {
        id: 1,
        name: 'Usuario Demo',
        email: 'usuario@demo.com',
        avatar: null,
        preferences: {
          notifications: true,
          theme: 'light'
        }
      }
      
      userProfile.value = mockProfile
      return true
    } catch (err) {
      error.value = err.message || 'Error al obtener el perfil'
      return false
    } finally {
      isLoading.value = false
    }
  }
  
  const updateProfile = async (profileData) => {
    isLoading.value = true
    error.value = null
    
    try {
      console.log('Actualizando perfil con datos:', profileData)
      
      userProfile.value = {
        ...userProfile.value,
        ...profileData
      }
      
      return true
    } catch (err) {
      error.value = err.message || 'Error al actualizar el perfil'
      return false
    } finally {
      isLoading.value = false
    }
  }
  
  const changePassword = async (passwordData) => {
    isLoading.value = true
    error.value = null
    
    try {
      await apiService.changePassword(passwordData)
      return true
    } catch (err) {
      error.value = err.message || 'Error al cambiar la contraseña'
      return false
    } finally {
      isLoading.value = false
    }
  }
  
  const deleteAccount = async (password) => {
    isLoading.value = true
    error.value = null
    
    try {
      await apiService.deleteAccount({ password })
      
      const authStore = useAuthStore()
      authStore.logout()
      userRole.value = null
      userProfile.value = null
      
      return true
    } catch (err) {
      error.value = err.message || 'Error al eliminar la cuenta'
      return false
    } finally {
      isLoading.value = false
    }
  }
  
  return {
      userRole,
    userProfile,
    isLoading,
    error,
    
      isAdmin,
    isWaiter,
    
      setRole,
    fetchProfile,
    updateProfile,
    changePassword,
    deleteAccount
  }
}) 