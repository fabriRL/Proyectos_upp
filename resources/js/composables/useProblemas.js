import { ref, unref } from 'vue'
import axios from 'axios'

export function useProblemas(proyectoIdRef) {
  const problemas = ref([])
  const cargando = ref(true)
  const error = ref(null)

  const id = () => unref(proyectoIdRef)

  async function fetchProblemas() {
    if (!id()) {
      error.value = 'No se identificó el proyecto.'
      cargando.value = false
      return
    }

    cargando.value = true
    error.value = null

    try {
      const { data } = await axios.get(`/api/proyectos/${id()}/problemas`)
      problemas.value = Array.isArray(data) ? data : []
    } catch (e) {
      console.error(e)
      error.value = e.response?.data?.message ?? 'No se pudieron cargar los problemas.'
    } finally {
      cargando.value = false
    }
  }

  async function crearProblema(payload) {
    const { data } = await axios.post(`/api/proyectos/${id()}/problemas`, payload)
    problemas.value.unshift(data)
    return data
  }

  async function actualizarProblema(idProblema, payload) {
    const { data } = await axios.put(`/api/problemas/${idProblema}`, payload)
    const idx = problemas.value.findIndex(p => p.id_problema === idProblema)
    if (idx !== -1) problemas.value[idx] = data
    return data
  }

  async function eliminarProblema(idProblema) {
    await axios.delete(`/api/problemas/${idProblema}`)
    problemas.value = problemas.value.filter(p => p.id_problema !== idProblema)
  }

  function diasAbiertos(p) {
    if (p.estado === 'Resuelto' || !p.fecha_registro) return 0
    const inicio = new Date(p.fecha_registro)
    const hoy = new Date()
    return Math.max(0, Math.floor((hoy - inicio) / 86400000))
  }

  return {
    problemas, cargando, error,
    fetchProblemas, crearProblema, actualizarProblema, eliminarProblema,
    diasAbiertos,
  }
}