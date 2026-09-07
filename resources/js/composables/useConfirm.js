import { reactive } from 'vue'

// Estado global (singleton), mismo patrón que useToast.js — cualquier
// componente puede llamar a confirmar() y el <ConfirmDialog /> montado una
// sola vez en App.vue reacciona.
const state = reactive({
  visible: false,
  title: '',
  message: '',
  confirmText: 'Eliminar',
  cancelText: 'Cancelar',
  variant: 'danger', // 'danger' | 'warning' | 'info'
  resolver: null,
})

export function useConfirm() {
  // Devuelve una Promise<boolean> — true si el usuario aceptó, false si canceló.
  // Uso: const ok = await confirmar({ title: '...', message: '...' }); if (!ok) return
  function confirmar(opciones) {
    return new Promise((resolve) => {
      state.title = opciones.title ?? '¿Estás seguro?'
      state.message = opciones.message ?? ''
      state.confirmText = opciones.confirmText ?? 'Eliminar'
      state.cancelText = opciones.cancelText ?? 'Cancelar'
      state.variant = opciones.variant ?? 'danger'
      state.visible = true
      state.resolver = resolve
    })
  }

  function aceptar() {
    state.visible = false
    state.resolver?.(true)
    state.resolver = null
  }

  function cancelar() {
    state.visible = false
    state.resolver?.(false)
    state.resolver = null
  }

  return { confirmState: state, confirmar, aceptar, cancelar }
}