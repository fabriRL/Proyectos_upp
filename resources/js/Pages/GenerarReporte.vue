<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/lib/axios'
import { useToast } from '@/composables/useToast.js'

const { showToast } = useToast()

const cargando = ref(true)
const error = ref(null)
const generandoPdf = ref(false)
const generandoExcel = ref(false)

const totalProyectos = ref(0)
const stats = ref({ avance_fisico: 0, avance_financiero: 0 })
const proyectos = ref([])
const generadoEn = ref('')

async function cargarReporte() {
  cargando.value = true
  error.value = null
  try {
    const { data } = await axios.get('/api/reporte-general')
    totalProyectos.value = data.total_proyectos
    stats.value = data.stats
    proyectos.value = data.proyectos
    generadoEn.value = data.generado_en
  } catch (e) {
    error.value = 'No se pudo cargar el reporte general.'
    console.error(e)
  } finally {
    cargando.value = false
  }
}

async function descargarPdf() {
  generandoPdf.value = true
  try {
    const { data } = await axios.get('/api/reporte-general/pdf', { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.download = `reporte-general-proyectos-${new Date().toISOString().slice(0, 10)}.pdf`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    console.error(e)
    showToast('No se pudo generar el PDF.', 'error')
  } finally {
    generandoPdf.value = false
  }
}

async function descargarExcel() {
  generandoExcel.value = true
  try {
    const { data } = await axios.get('/api/reporte-general/excel', { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }))
    const link = document.createElement('a')
    link.href = url
    link.download = `reporte-general-proyectos-${new Date().toISOString().slice(0, 10)}.xlsx`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    console.error(e)
    showToast('No se pudo generar el Excel.', 'error')
  } finally {
    generandoExcel.value = false
  }
}

function badgeEstado(estado) {
  if (estado === 'Vencido') return { color: '#f87171', bg: 'rgba(248,113,113,0.12)', border: 'rgba(248,113,113,0.3)' }
  if (estado === 'Paralizado') return { color: '#fbbf24', bg: 'rgba(251,191,36,0.12)', border: 'rgba(251,191,36,0.3)' }
  if (estado === 'Concluido') return { color: '#55b8ef', bg: 'rgba(77,179,240,0.12)', border: 'rgba(77,179,240,0.3)' }
  if (estado === 'Sin contratos') return { color: '#8ea9bf', bg: 'rgba(142,169,191,0.1)', border: 'rgba(142,169,191,0.25)' }
  return { color: '#00c9a7', bg: 'rgba(0,201,167,0.12)', border: 'rgba(0,201,167,0.3)' } // Vigente
}

onMounted(cargarReporte)
</script>

<template>
  <div class="p-5 reporte-page">

    <div v-if="cargando" class="text-center py-10" style="color:#8ea9bf;">
      Cargando reporte general...
    </div>

    <div v-else-if="error" class="text-center py-10" style="color:#f87171;">
      {{ error }}
      <button class="btn btn-sm btn-ghost ml-2" @click="cargarReporte">Reintentar</button>
    </div>

    <template v-else>
      <div class="flex justify-between items-center mb-4 flex-wrap gap-3">
        <div>
          <div class="section-title" style="margin-bottom:2px;">Reporte General de Proyectos</div>
          <p style="font-size:.75rem;color:#8ea9bf;margin:0;">
            {{ totalProyectos }} proyecto(s) — generado el {{ generadoEn }}
          </p>
        </div>
        <div class="flex gap-2">
          <button class="btn btn-sm" style="background:#122130;border:1px solid #1e3a52;color:#8ea9bf;" @click="descargarExcel" :disabled="generandoExcel">
            <i class="ti ti-file-spreadsheet"></i> {{ generandoExcel ? 'Generando...' : 'Excel' }}
          </button>
          <button class="btn btn-sm" style="background:#00c9a7;color:#04211c;font-weight:600;" @click="descargarPdf" :disabled="generandoPdf">
            <i class="ti ti-file-type-pdf"></i> {{ generandoPdf ? 'Generando...' : 'Descargar PDF' }}
          </button>
        </div>
      </div>

      <!-- Stats generales -->
      <div class="grid grid-cols-2 gap-3 mb-5">
        <div class="rounded-xl p-4" style="background-color:#0d1f30; border:1px solid #1e3a52;">
          <div class="field-label">Avance Físico General</div>
          <div class="font-bold text-2xl mt-1" style="color:#00c9a7;">{{ stats.avance_fisico }}%</div>
        </div>
        <div class="rounded-xl p-4" style="background-color:#0d1f30; border:1px solid #1e3a52;">
          <div class="field-label">Avance Financiero General</div>
          <div class="font-bold text-2xl mt-1" style="color:#00c9a7;">{{ stats.avance_financiero }}%</div>
        </div>
      </div>

      <!-- Tabla por proyecto -->
      <div class="rounded-xl overflow-hidden" style="background-color:#0d1f30; border:1px solid #1e3a52;">
        <div class="px-5 py-3" style="border-bottom:1px solid #19354d;">
          <div class="section-title" style="margin-bottom:0;">Estado por proyecto</div>
          <p class="table-legend">
            <i class="ti ti-info-circle"></i>
            Las columnas resaltadas en azul se calculan a partir de los Contratos del proyecto; el resto son datos propios del proyecto.
          </p>
        </div>

        <div v-if="!proyectos.length" class="text-center py-10" style="color:#8ea9bf;">
          Todavía no hay proyectos registrados en el sistema.
        </div>

        <div v-else class="overflow-x-auto">
         <table class="reporte-table">
          <thead>
            <tr>
              <th rowspan="2">N°</th>
              <th rowspan="2">Código</th>
              <th rowspan="2">Código SISINWEB</th>
              <th rowspan="2">Proyecto</th>
              <th rowspan="2">Norma de Financiamiento</th>
              <th rowspan="2">Monto D.S. (Bs)</th>
              <th rowspan="2">Av. Físico (SISIN)</th>
              <th rowspan="2">Av. Financiero (SISIN)</th>
              <th rowspan="2">Estado de Situación</th>
              <th rowspan="2">Inicio Contractual</th>
              <th rowspan="2">Entrega Provisional</th>
              <th rowspan="2">Entrega Definitiva</th>
              <th rowspan="2">Plazo (Días)</th>
              <th colspan="22" class="group-header-contratos">
                Contratos y Planillas
                <span class="group-tag">Desde Contratos</span>
              </th>
              <th rowspan="2">Problemas</th>
              <th rowspan="2">Acciones</th>
              <th rowspan="2">Líneas y Capacidades</th>
              <th rowspan="2">Result. Impacto Socioeconómico</th>
              <th rowspan="2">Observaciones</th>
              <th rowspan="2">Días Restantes</th>
              <th rowspan="2">Semáforo</th>
            </tr>
            <tr>
              <th class="sub-header-contratos">Empresa Contratista</th>
              <th class="sub-header-contratos">Monto Original Contratista (Bs)</th>
              <th class="sub-header-contratos">Monto Modif. Contratista (Bs)</th>
              <th class="sub-header-contratos">Empresa Supervisión</th>
              <th class="sub-header-contratos">Monto Original Supervisión (Bs)</th>
              <th class="sub-header-contratos">Monto Modif. Supervisión (Bs)</th>
              <th class="sub-header-contratos">Orden Proceder</th>
              <th class="sub-header-contratos">Concl. Prevista</th>
              <th class="sub-header-contratos">Av. Infraestr.</th>
              <th class="sub-header-contratos">Av. Equipam.</th>
              <th class="sub-header-contratos">Av. Insumos/P.M.</th>
              <th class="sub-header-contratos">Últ. Modificaciones</th>
              <th class="sub-header-contratos">Últ. Acciones (Resoluciones)</th>
              <th class="sub-header-contratos">Planilla Pend. (Contratista)</th>
              <th class="sub-header-contratos">Monto Pend. Contratista</th>
              <th class="sub-header-contratos">Planilla Pend. (Supervisión)</th>
              <th class="sub-header-contratos">Monto Pend. Supervisión</th>
              <th class="sub-header-contratos">Monto Req. hasta Conclusión</th>
              <th class="sub-header-contratos">Increm. D.S. 5321</th>
              <th class="sub-header-contratos">Anticipo D.S. 5406</th>
              <th class="sub-header-contratos">Presup. Asignado Gestión</th>
              <th class="sub-header-contratos">Asignación SIGEP</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in proyectos" :key="p.codigo">
              <td class="col-numero">{{ p.n }}</td>
              <td class="font-mono" style="color:#8ea9bf;">{{ p.codigo }}</td>
              <td class="font-mono" style="color:#8ea9bf;">{{ p.numero_sisin_web || '—' }}</td>
              <td class="text-left" style="color:#e4f0f7;font-weight:600;">{{ p.nombre }}</td>
              <td class="text-left" style="color:#b9cadb;">{{ p.norma_financiamiento || '—' }}</td>
              <td class="money">{{ Number(p.monto_decreto_vigente).toLocaleString('es-BO', { minimumFractionDigits: 2 }) }}</td>
              <td class="money">{{ p.avance_fisico }}%</td>
              <td class="money">{{ p.avance_financiero }}%</td>
              <td>
                <span
                  class="badge-estado"
                  :style="{ color: badgeEstado(p.estado_general).color, background: badgeEstado(p.estado_general).bg, borderColor: badgeEstado(p.estado_general).border }"
                >
                  {{ p.estado_general }}
                </span>
              </td>
              <td>{{ p.fecha_inicio_contractual || '—' }}</td>
              <td>{{ p.fecha_entrega_provisional || '—' }}</td>
              <td>{{ p.fecha_entrega_definitiva || '—' }}</td>
              <td>{{ p.plazo_dias || '—' }}</td>

              <td class="text-left col-contrato" style="color:#b9cadb;">{{ p.contratistas }}</td>
              <td class="money col-contrato">{{ Number(p.monto_original).toLocaleString('es-BO', { minimumFractionDigits: 2 }) }}</td>
              <td class="money col-contrato">{{ Number(p.monto_modificaciones).toLocaleString('es-BO', { minimumFractionDigits: 2 }) }}</td>
              <td class="text-left col-contrato" style="color:#b9cadb;">{{ p.empresa_supervision || '—' }}</td>
              <td class="money col-contrato">{{ Number(p.monto_original_supervision).toLocaleString('es-BO', { minimumFractionDigits: 2 }) }}</td>
              <td class="money col-contrato">{{ Number(p.monto_modificaciones_supervision).toLocaleString('es-BO', { minimumFractionDigits: 2 }) }}</td>
              <td class="col-contrato">{{ p.fecha_orden_proceder || '—' }}</td>
              <td class="col-contrato">{{ p.fecha_conclusion_prevista || '—' }}</td>
              <td class="col-contrato">{{ p.avance_fisico_infraestructura ?? '—' }}</td>
              <td class="col-contrato">{{ p.avance_fisico_equipamiento ?? '—' }}</td>
              <td class="col-contrato">{{ p.avance_insumos_puesta_marcha ?? '—' }}</td>
              <td class="text-left col-contrato" style="color:#b9cadb;max-width:180px;">{{ p.ultimas_modificaciones || '—' }}</td>
              <td class="col-pendiente">{{ p.ultimas_acciones || '—' }}</td>
              <td class="col-pendiente">{{ p.descripcion_planilla_pendiente_contratista || '—' }}</td>
              <td class="col-pendiente">{{ p.monto_planilla_pendiente_contratista || '—' }}</td>
              <td class="col-pendiente">{{ p.descripcion_planilla_pendiente_supervision || '—' }}</td>
              <td class="col-pendiente">{{ p.monto_planilla_pendiente_supervision || '—' }}</td>
              <td class="col-pendiente">{{ p.monto_requerido_hasta_conclusion || '—' }}</td>
              <td class="col-pendiente">{{ p.incremento_ds_5321 || '—' }}</td>
              <td class="col-pendiente">{{ p.anticipo_adicional_ds_5406 || '—' }}</td>
              <td class="col-pendiente">{{ p.presupuesto_gestion_actual || '—' }}</td>
              <td class="col-pendiente">{{ p.tiene_sigep === true ? 'Sí' : p.tiene_sigep === false ? 'No' : '—' }}</td>

              <td class="text-left" style="color:#b9cadb;max-width:160px;">{{ p.problemas || '—' }}</td>
              <td class="text-left" style="color:#b9cadb;max-width:160px;">{{ p.acciones || '—' }}</td>
              <td class="text-left" style="color:#b9cadb;max-width:200px;">{{ p.lineas_capacidades || '—' }}</td>
              <td class="text-left" style="color:#b9cadb;max-width:200px;">{{ p.resultado_impacto_socioeconomico || '—' }}</td>
              <td class="text-left" style="color:#b9cadb;max-width:200px;">{{ p.observaciones || '—' }}</td>

              <td>{{ p.dias_restantes !== null ? p.dias_restantes + ' días' : '—' }}</td>
              <td>
                <span class="badge-semaforo" :style="{ color: p.semaforo.color, borderColor: p.semaforo.color }">
                  {{ p.semaforo.texto }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
        </div>
      </div>
    </template>

  </div>
</template>

<style scoped>
.reporte-page { max-width: 1600px; margin: auto; }

.table-legend {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: .7rem;
  color: #55b8ef;
  margin: 6px 0 0;
}
.table-legend i { font-size: .85rem; flex-shrink: 0; }

.reporte-table {
  width: 100%;
  min-width: 1900px;
  border-collapse: collapse;
  font-size: .82rem;
}

.reporte-table thead tr {
  background: #0a1826;
  border-bottom: 1px solid #1e3a52;
}

.reporte-table th {
  padding: 12px;
  text-align: center;
  color: #9cb5c6;
  font-size: .68rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .04em;
  vertical-align: middle;
}

/* --- Grupo "Datos de Contratos" (mismo patrón que Contratos.vue) --- */
.group-header-contratos {
  background: rgba(85, 184, 239, .12) !important;
  color: #55b8ef !important;
  border-left: 1px solid rgba(85, 184, 239, .3);
  border-right: 1px solid rgba(85, 184, 239, .3);
}
.group-tag {
  display: block;
  margin-top: 3px;
  font-size: .58rem;
  font-weight: 700;
  text-transform: none;
  letter-spacing: 0;
  color: #55b8ef;
  opacity: .85;
}
.sub-header-contratos {
  background: rgba(85, 184, 239, .07) !important;
  color: #7fc4ef !important;
  border-left: 1px solid rgba(85, 184, 239, .18);
  border-right: 1px solid rgba(85, 184, 239, .18);
}

.reporte-table tbody tr {
  border-bottom: 1px solid #152a3e;
  transition: background .15s;
}

.reporte-table tbody tr:hover {
  background: rgba(0, 201, 167, .045);
}

.reporte-table td {
  padding: 13px 12px;
  text-align: center;
  color: #b9cadb;
}

.reporte-table td.text-left {
  text-align: left;
}

/* Celdas del cuerpo que pertenecen al grupo de Contratos */
.col-contrato {
  background: rgba(85, 184, 239, .045);
  border-left: 1px solid rgba(85, 184, 239, .12);
  border-right: 1px solid rgba(85, 184, 239, .12);
}

.col-numero {
  font-weight: 800;
  color: #00c9a7;
}

.money {
  font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
  color: #e4f0f7 !important;
  font-weight: 600;
}

.badge-estado {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 6px;
  border: 1px solid;
  font-size: .72rem;
  font-weight: 700;
}

.badge-semaforo {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 999px;
  border: 1.5px solid;
  font-size: .7rem;
  font-weight: 800;
  letter-spacing: .02em;
} 

@media (max-width: 700px) {
  .reporte-page { padding: 14px; }
  .grid-cols-2 { grid-template-columns: 1fr !important; }
}
</style>