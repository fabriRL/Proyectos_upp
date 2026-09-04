import { router } from '@inertiajs/vue3'
import { ref } from 'vue'

export function useContratos(proyectoId) {
  const procesando = ref(false)

  const crear = (data) => {
    procesando.value = true
    router.post(`/proyectos/${proyectoId}/contratos`, data, {
      onFinish: () => (procesando.value = false),
    })
  }

  const actualizar = (id, data) => {
    procesando.value = true
    router.put(`/proyectos/${proyectoId}/contratos/${id}`, data, {
      onFinish: () => (procesando.value = false),
    })
  }

  const eliminar = (id) => {
    if (!confirm('¿Eliminar este contrato?')) return
    router.delete(`/proyectos/${proyectoId}/contratos/${id}`)
  }

  const formatoMoneda = (valor) =>
    'Bs ' + Number(valor).toLocaleString('es-BO', { maximumFractionDigits: 0 })

  return { procesando, crear, actualizar, eliminar, formatoMoneda }
}