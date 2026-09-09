<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/lib/axios'
import { useToast } from '@/composables/useToast.js'
import { useConfirm } from '@/composables/useConfirm.js'

const { showToast } = useToast()
const { confirmar } = useConfirm()

const reportes = ref([])
const cargando = ref(true)
const error = ref(null)

function fmtFecha(fecha) {
  const d = new Date(fecha)
  if (isNaN(d.getTime())) return '—'
  return d.toLocaleString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function cargarHistorial() {
  cargando.value = true
  error.value = null
  try {
    const { data } = await axios.get('/api/reportes-generados')
    reportes.value = data
  } catch (e) {
    error.value = 'No se pudo cargar el historial de reportes.'
    console.error(e)
  } finally {
    cargando.value = false
  }
}

async function eliminarReporte(id) {
  const ok = await confirmar({
    title: 'Eliminar reporte',
    message: '¿Eliminar este reporte del historial? También se borrará el archivo. Esta acción no se puede deshacer.',
    confirmText: 'Sí, eliminar',
  })
  if (!ok) return

  try {
    await axios.delete(`/api/reportes-generados/${id}`)
    await cargarHistorial()
    showToast('Reporte eliminado del historial.', 'success')
  } catch (e) {
    console.error(e)
    showToast('No se pudo eliminar el reporte.', 'error')
  }
}

onMounted(cargarHistorial)
</script>

<template>
  <div class="p-5 historial-page">
    <div class="flex justify-between items-center mb-4">
      <div class="section-title" style="margin-bottom:0;">Historial de Reportes</div>
      <RouterLink :to="{ name: 'generar-reporte' }" class="btn btn-sm" style="background:#00c9a7;color:#04211c;font-weight:600;">
        <i class="ti ti-plus"></i> Generar nuevo reporte
      </RouterLink>
    </div>

    <div v-if="cargando" class="text-center py-10" style="color:#8ea9bf;">
      Cargando historial...
    </div>

    <div v-else-if="error" class="text-center py-10" style="color:#f87171;">
      {{ error }}
      <button class="btn btn-sm btn-ghost ml-2" @click="cargarHistorial">Reintentar</button>
    </div>

    <div v-else class="rounded-xl overflow-hidden" style="background-color:#0d1f30; border:1px solid #1e3a52;">
      <div v-if="!reportes.length" class="text-center py-10" style="color:#8ea9bf;">
        Todavía no se ha generado ningún reporte.
      </div>

      <div v-else class="overflow-x-auto">
        <table class="historial-table">
          <thead>
            <tr>
              <th>Tipo</th><th>Archivo</th><th>Proyectos</th><th>Fecha de generación</th><th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in reportes" :key="r.id_reporte">
              <td>
                <span class="badge-tipo" :class="r.tipo === 'pdf' ? 'tipo-pdf' : 'tipo-excel'">
                  <i :class="r.tipo === 'pdf' ? 'ti ti-file-type-pdf' : 'ti ti-file-spreadsheet'"></i>
                  {{ r.tipo.toUpperCase() }}
                </span>
              </td>
              <td class="text-left" style="color:#c8dae7;">{{ r.nombre_archivo }}</td>
              <td>{{ r.total_proyectos ?? '—' }}</td>
              <td style="color:#8ea9bf;">{{ fmtFecha(r.creado_en) }}</td>
              <td>
                <a :href="r.url" target="_blank" class="btn-accion btn-descargar" title="Descargar">
                  <i class="ti ti-download"></i>
                </a>
                <button class="btn-accion btn-eliminar" title="Eliminar" @click="eliminarReporte(r.id_reporte)">
                  <i class="ti ti-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.historial-page { max-width: 1100px; margin: auto; }

.historial-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
.historial-table thead tr { background: #0a1826; border-bottom: 1px solid #1e3a52; }
.historial-table th {
  padding: 12px; text-align: center; color: #9cb5c6;
  font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
}
.historial-table tbody tr { border-bottom: 1px solid #152a3e; transition: background .15s; }
.historial-table tbody tr:hover { background: rgba(0, 201, 167, .045); }
.historial-table td { padding: 13px 12px; text-align: center; color: #b9cadb; }
.historial-table td.text-left { text-align: left; }

.badge-tipo {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 4px 10px; border-radius: 6px; border: 1px solid;
  font-size: .7rem; font-weight: 700;
}
.tipo-pdf { color: #f87171; background: rgba(248,113,113,0.1); border-color: rgba(248,113,113,0.3); }
.tipo-excel { color: #00c9a7; background: rgba(0,201,167,0.1); border-color: rgba(0,201,167,0.3); }

.btn-accion {
  width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;
  border-radius: 6px; border: 1px solid transparent; cursor: pointer; font-size: 13px;
  text-decoration: none; margin: 0 2px;
}
.btn-descargar { background: rgba(77,179,240,0.1); border-color: rgba(77,179,240,0.22); color: #55b8ef; }
.btn-descargar:hover { background: rgba(77,179,240,0.2); }
.btn-eliminar { background: rgba(248,113,113,0.08); border-color: rgba(248,113,113,0.18); color: #f87171; }
.btn-eliminar:hover { background: rgba(248,113,113,0.16); }
</style>