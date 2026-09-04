import { reactive } from 'vue'

// Estado global (singleton) — cualquier componente puede llamar a
// showToast() y el <ToastNotification /> montado una sola vez en App.vue
// reacciona, sin necesidad de props ni eventos entre componentes.
const state = reactive({
  visible: false,
  message: '',
  type: 'info', // 'info' | 'success' | 'error' | 'warning'
})

let timeoutId = null

export function useToast() {
  function showToast(message, type = 'info', duracionMs = 3500) {
    state.message = message
    state.type = type
    state.visible = true

    if (timeoutId) clearTimeout(timeoutId)
    timeoutId = setTimeout(() => {
      state.visible = false
    }, duracionMs)
  }

  function cerrarToast() {
    state.visible = false
    if (timeoutId) clearTimeout(timeoutId)
  }

  return { toastState: state, showToast, cerrarToast }
}