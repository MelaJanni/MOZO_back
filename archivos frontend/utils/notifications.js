import Swal from 'sweetalert2'

const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.addEventListener('mouseenter', Swal.stopTimer)
    toast.addEventListener('mouseleave', Swal.resumeTimer)
  }
})

export const showSuccessToast = (title) => {
  Toast.fire({
    icon: 'success',
    title
  })
}

export const showErrorToast = (title) => {
  Toast.fire({
    icon: 'error',
    title
  })
}

export const showConfirmDialog = async (
  title, 
  text, 
  confirmButtonText = 'Confirmar', 
  cancelButtonText = 'Cancelar',
  icon = 'warning'
) => {
  const result = await Swal.fire({
    title,
    text,
    icon,
    showCancelButton: true,
    confirmButtonColor: icon === 'danger' ? '#dc3545' : '#007bff',
    cancelButtonColor: '#6c757d',
    confirmButtonText,
    cancelButtonText,
    reverseButtons: true
  })

  return result.isConfirmed
} 