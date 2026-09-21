<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { useToast } from '@/composables/useToast.js'

const props = defineProps({
  proyecto: { type: Object, default: null },
  proyectoId: { type: [Number, String], default: null },
  routeParam: { type: String, default: 'codigo' },
})

const route = useRoute()
const { showToast } = useToast()

const MAX_ACTIVIDADES = 15
const MONTH_NAMES = ['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE']
const ESTADO_BADGE = {
  'Concluida': 'badge badge-success',
  'En curso': 'badge badge-info',
  'En ejecución': 'badge badge-info',
  'Retrasada': 'badge badge-danger',

}

/* ---------------- Proyecto / identificador ---------------- */

const identificador = computed(() =>
  props.proyecto?.codigo ?? props.proyecto?.id_proyecto ?? props.proyectoId ??
  route.params[props.routeParam] ?? route.params.codigo ?? route.params.proyecto ?? route.params.id ?? null
)

const proyectoInfo = ref(props.proyecto ?? null)
const actividadesRaw = ref([])
const loading = ref(true)
const error = ref(null)

/* ---------------- Modal / formulario ---------------- */

const mostrarModal = ref(false)
const guardando = ref(false)
const errorFormulario = ref(null)
const modoEdicion = ref(false)
const actividadEditandoId = ref(null)

// "porcentaje_cumplimiento_programado" no se pide aquí: se calcula solo
// en el backend a partir de fecha_inicio/fecha_fin/estado.
const FORM_DEFAULT = () => ({
  numero: '', actividad: '', fecha_inicio: '', fecha_fin: '',
  estado: 'Pendiente', porcentaje_cumplimiento_real: 0,
})
const actividadFormulario = ref(FORM_DEFAULT())

function abrirNuevaActividad() {
  if (actividadesRaw.value.length >= MAX_ACTIVIDADES) {
    showToast(`Este proyecto ya alcanzó el límite de ${MAX_ACTIVIDADES} actividades. No se pueden registrar más.`, 'warning')
    return
  }

  const siguienteNumero = actividadesRaw.value.length
    ? Math.max(...actividadesRaw.value.map(a => Number(a.numero) || 0)) + 1
    : 1

  errorFormulario.value = null
  modoEdicion.value = false
  actividadEditandoId.value = null
  actividadFormulario.value = { ...FORM_DEFAULT(), numero: siguienteNumero }
  mostrarModal.value = true
}

function abrirEditarActividad(actividad) {
  errorFormulario.value = null
  modoEdicion.value = true
  actividadEditandoId.value = actividad.id_actividad

  actividadFormulario.value = {
    numero: Number(actividad.numero) || 1,
    actividad: actividad.actividad ?? '',
    fecha_inicio: normalizarFechaInput(actividad.fecha_inicio),
    fecha_fin: normalizarFechaInput(actividad.fecha_fin),
    estado: actividad.estado ?? 'Pendiente',
    porcentaje_cumplimiento_real: Number(actividad.porcentaje_cumplimiento_real ?? 0),
  }

  mostrarModal.value = true
}

function cerrarModal() {
  if (guardando.value) return
  mostrarModal.value = false
  errorFormulario.value = null
  modoEdicion.value = false
  actividadEditandoId.value = null
}

function normalizarFechaInput(value) {
  if (!value) return ''
  if (typeof value === 'string') return value.substring(0, 10)

  const d = new Date(value)
  if (isNaN(d)) return ''
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

/* ---------------- Carga de datos ---------------- */

async function cargarProyecto() {
  if (props.proyecto) {
    proyectoInfo.value = props.proyecto
    return
  }
  try {
    const { data } = await axios.get(`/api/proyectos/${identificador.value}`)
    proyectoInfo.value = data
  } catch (e) {
    console.error(e)
  }
}

async function cargarActividades() {
  if (!identificador.value) {
    error.value = 'No se identificó el proyecto.'
    loading.value = false
    return
  }

  loading.value = true
  error.value = null

  try {
    await cargarProyecto()
    const { data } = await axios.get(`/api/proyectos/${identificador.value}/actividades`)
    actividadesRaw.value = Array.isArray(data) ? data : []
  } catch (e) {
    console.error(e)
    error.value = e.response?.data?.message ?? 'No se pudo cargar el cronograma. Intenta nuevamente.'
  } finally {
    loading.value = false
  }
}

/* ---------------- Validación / guardado ---------------- */

function validarFormulario() {
  errorFormulario.value = null
  const f = actividadFormulario.value

  if (!f.numero) return (errorFormulario.value = 'El número de actividad es obligatorio.'), false
  if (!f.actividad.trim()) return (errorFormulario.value = 'Debes escribir el nombre de la actividad.'), false
  if (!f.fecha_inicio) return (errorFormulario.value = 'Debes seleccionar la fecha de inicio.'), false
  if (!f.fecha_fin) return (errorFormulario.value = 'Debes seleccionar la fecha de finalización.'), false
  if (f.fecha_fin < f.fecha_inicio) return (errorFormulario.value = 'La fecha de finalización no puede ser anterior a la fecha de inicio.'), false

  const real = Number(f.porcentaje_cumplimiento_real)

  if (real < 0 || real > 100) return (errorFormulario.value = 'El cumplimiento real debe estar entre 0 y 100%.'), false

  return true
}

function calcularDuracion(inicio, fin) {
  const fechaInicio = new Date(`${inicio}T00:00:00`)
  const fechaFin = new Date(`${fin}T00:00:00`)
  return Math.floor((fechaFin - fechaInicio) / 86400000) + 1
}

// Respaldo para la tabla: solo se usa si el backend no envía duracion_dias.
function calcularDuracionVista(inicio, fin) {
  if (!inicio || !fin) return null
  const fechaInicio = toDate(inicio)
  const fechaFin = toDate(fin)
  if (!fechaInicio || !fechaFin) return null
  return diffDays(fechaFin, fechaInicio) + 1
}

async function guardarActividad() {
  if (!validarFormulario()) return

  guardando.value = true
  errorFormulario.value = null

  const f = actividadFormulario.value
  const payload = {
    numero: Number(f.numero),
    actividad: f.actividad.trim(),
    fecha_inicio: f.fecha_inicio,
    fecha_fin: f.fecha_fin,
    duracion_dias: calcularDuracion(f.fecha_inicio, f.fecha_fin),
    estado: f.estado,
    porcentaje_cumplimiento_real: Number(f.porcentaje_cumplimiento_real || 0),
  }

  try {
    if (modoEdicion.value && actividadEditandoId.value) {
      await axios.put(`/api/actividades/${actividadEditandoId.value}`, payload)
    } else {
      await axios.post(`/api/proyectos/${identificador.value}/actividades`, payload)
    }

    showToast(
      modoEdicion.value ? 'Actividad actualizada correctamente.' : 'Actividad registrada correctamente.',
      'success'
    )

    cerrarModal()
    await cargarActividades()
  } catch (e) {
    console.error(e)

    if (e.response?.status === 422) {
      const errores = e.response.data.errors
      errorFormulario.value = errores
        ? Object.values(errores).flat().join(' ')
        : e.response.data.message ?? 'Los datos enviados no son válidos.'
    } else {
      errorFormulario.value = e.response?.data?.message ??
        (modoEdicion.value ? 'No se pudo actualizar la actividad.' : 'No se pudo registrar la actividad.')

      showToast(errorFormulario.value, 'error')
    }
  } finally {
    guardando.value = false
  }
}

/* ---------------- Utilidades de fecha ---------------- */

function toDate(value) {
  if (!value) return null
  const d = value instanceof Date ? value : new Date(value)
  return isNaN(d) ? null : d
}

function formatDate(value) {
  const d = toDate(value)
  if (!d) return '—'
  return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${String(d.getFullYear()).slice(-2)}`
}

function diffDays(a, b) {
  return Math.round((a - b) / 86400000)
}

/* ---------------- Actividades para vista ---------------- */

const actividades = computed(() =>
  [...actividadesRaw.value]
    .sort((a, b) => (a.numero ?? 0) - (b.numero ?? 0))
    .map(a => ({
      id: a.id_actividad,
      raw: a,
      n: a.numero,
      nombre: a.actividad,
      inicio: formatDate(a.fecha_inicio),
      fin: formatDate(a.fecha_fin),
      duracion: a.duracion_dias ?? calcularDuracionVista(a.fecha_inicio, a.fecha_fin),
      estado: a.estado,
      p: Math.min(100, Math.max(0, Number(a.porcentaje_cumplimiento_programado ?? 0))),
      r: Math.min(100, Math.max(0, Number(a.porcentaje_cumplimiento_real ?? 0))),
      fechaInicioRaw: toDate(a.fecha_inicio),
      fechaFinRaw: toDate(a.fecha_fin),
      actualizadoEn: toDate(a.actualizado_en),
    }))
)

/* ---------------- Línea de tiempo ---------------- */

const proyectoInicio = computed(() => toDate(proyectoInfo.value?.fecha_inicio_contractual))
const proyectoFin = computed(() =>
  toDate(proyectoInfo.value?.fecha_conclusion_actual) ?? toDate(proyectoInfo.value?.fecha_conclusion_inicial_contractual)
)

// Sin actividades ni fechas del proyecto, la línea de tiempo muestra solo el mes
// actual: "hoy" siempre es candidata, así el rango nunca queda vacío. En cuanto
// exista una actividad o fecha de proyecto fuera de ese mes, el rango se estira.

function extremo(candidatas, comparador) {
  return candidatas.filter(Boolean).reduce((acc, d) => (comparador(d, acc) ? d : acc), candidatas.find(Boolean))
}

const timelineStart = computed(() => {
  const min = extremo([proyectoInicio.value, new Date(), ...actividades.value.map(a => a.fechaInicioRaw)], (d, acc) => d < acc)
  return new Date(min.getFullYear(), min.getMonth(), 1)
})

const timelineEnd = computed(() => {
  const max = extremo([proyectoFin.value, new Date(), ...actividades.value.map(a => a.fechaFinRaw)], (d, acc) => d > acc)
  return new Date(max.getFullYear(), max.getMonth() + 1, 0)
})

const totalDias = computed(() => Math.max(diffDays(timelineEnd.value, timelineStart.value) + 1, 1))

const months = computed(() => {
  const list = []
  let cursor = new Date(timelineStart.value)

  while (cursor <= timelineEnd.value) {
    list.push({ key: `${cursor.getFullYear()}-${cursor.getMonth()}`, label: MONTH_NAMES[cursor.getMonth()] })
    cursor = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1)
  }

  return list
})

const weeks = computed(() => Array.from({ length: months.value.length * 4 }, (_, i) => i))

function dayOffset(date) {
  return diffDays(date, timelineStart.value)
}

const hoyOffset = computed(() => {
  const off = dayOffset(new Date())
  if (off < 0 || off > totalDias.value) return null
  return (off / totalDias.value) * 100
})

function ganttStyle(fila) {
  if (!fila.fechaInicioRaw || !fila.fechaFinRaw) return { left: '0%', width: '.8%' }

  const start = Math.max(0, dayOffset(fila.fechaInicioRaw))
  const end = Math.min(dayOffset(fila.fechaFinRaw), totalDias.value)
  const left = (start / totalDias.value) * 100
  const width = Math.max(((end - start + 1) / totalDias.value) * 100, .8)

  return { left: `${left}%`, width: `${width}%` }
}

function realStyle(fila) {
  const base = ganttStyle(fila)
  const totalWidth = parseFloat(base.width)
  return { ...base, width: `${Math.max((totalWidth * fila.r) / 100, 0)}%` }
}

function badgeClass(estado) {
  return ESTADO_BADGE[estado] ?? 'badge badge-muted'
}

/* ---------------- Resumen ---------------- */

const completadas = computed(() => actividades.value.filter(a => a.estado === 'Concluida').length)

function promedio(campo) {
  if (!actividades.value.length) return 0
  return Math.round(actividades.value.reduce((sum, a) => sum + a[campo], 0) / actividades.value.length)
}

const avanceProgramado = computed(() => promedio('p'))
const avanceReal = computed(() => promedio('r'))

const ultimaActualizacion = computed(() => {
  const fechas = actividades.value.map(a => a.actualizadoEn).filter(Boolean)
  if (!fechas.length) return null

  const max = fechas.reduce((acc, d) => (d > acc ? d : acc), fechas[0])
  return max.toLocaleDateString('es-BO', { day: '2-digit', month: 'long', year: 'numeric' })
})

/* ---------------- Ciclo de vida ---------------- */

onMounted(cargarActividades)
watch(identificador, cargarActividades)
watch(() => props.proyecto, nuevo => { if (nuevo) proyectoInfo.value = nuevo })

defineExpose({ recargar: cargarActividades })
</script>

<template>
  <div class="gantt-page">

    <div class="cronograma-header">
      <div class="header-title">
        <div class="header-icon"><i class="ti ti-calendar-event"></i></div>
        <div>
          <h2>Cronograma del proyecto</h2>
          <p>Gestiona las actividades y visualiza el avance del proyecto.</p>
        </div>
      </div>

      <button type="button" class="btn-nueva-actividad" @click="abrirNuevaActividad">
        <i class="ti ti-plus"></i> Nueva actividad
      </button>
    </div>

    <div v-if="loading" class="gantt-state">
      <div class="state-icon"><i class="ti ti-loader-2 spin"></i></div>
      <div>
        <strong>Cargando cronograma</strong>
        <span>Estamos obteniendo las actividades del proyecto...</span>
      </div>
    </div>

    <div v-else-if="error" class="gantt-state gantt-state-error">
      <div class="state-icon error-icon"><i class="ti ti-alert-triangle"></i></div>
      <div>
        <strong>No se pudo cargar el cronograma</strong>
        <span>{{ error }}</span>
        <button class="btn-reintentar" @click="cargarActividades"><i class="ti ti-refresh"></i> Reintentar</button>
      </div>
    </div>

    <template v-else>

      <section class="gantt-summary">
        <div class="summary-item">
          <div class="summary-icon"><i class="ti ti-list-check"></i></div>
          <div><strong>{{ actividades.length }}</strong><span>Actividades</span></div>
        </div>

        <div class="summary-item">
          <div class="summary-icon success"><i class="ti ti-circle-check"></i></div>
          <div><strong>{{ completadas }}</strong><span>Concluidas</span></div>
        </div>

        <div class="summary-item">
          <div class="summary-icon blue"><i class="ti ti-progress"></i></div>
          <div><strong>{{ avanceProgramado }}%</strong><span>Programado</span></div>
        </div>

        <div class="summary-item">
          <div class="summary-icon purple"><i class="ti ti-chart-line"></i></div>
          <div><strong>{{ avanceReal }}%</strong><span>Avance real</span></div>
        </div>

        <div class="summary-legend">
          <span><i class="planned-dot"></i> Programado</span>
          <span><i class="actual-dot"></i> Ejecutado</span>
        </div>
      </section>

      <section class="gantt-card">

        <div class="gantt-card-header">
          <div>
            <h3><i class="ti ti-chart-gantt"></i> Línea de tiempo</h3>
            <span>Planificación y avance de actividades</span>
          </div>
          <div class="timeline-info">
            <i class="ti ti-calendar"></i> {{ formatDate(timelineStart) }} — {{ formatDate(timelineEnd) }}
          </div>
        </div>

        <div class="gantt-scroll">
          <div class="gantt-table" :style="{ '--n-cols': weeks.length }">

            <div class="gantt-side header-side">
              <span>N°</span><span>Actividad</span><span>Inicio</span><span>Fin</span>
              <span>Dur.</span><span>% Prog.</span><span>% Real</span><span>Estado</span>
            </div>

            <div class="gantt-timeline gantt-months">
              <div v-for="m in months" :key="m.key" class="month">{{ m.label }}</div>
            </div>

            <div class="gantt-side week-side">
              <span></span><span></span><span></span><span></span>
              <span></span><span></span><span></span><span></span>
            </div>

            <div class="gantt-timeline week-row">
              <span v-for="week in weeks" :key="week">S{{ (week % 4) + 1 }}</span>
              <div v-if="hoyOffset !== null" class="today-marker" :style="{ left: hoyOffset + '%' }" title="Hoy">
                <span>HOY</span>
              </div>
            </div>

            <template v-for="fila in actividades" :key="fila.id">

              <div class="gantt-side activity-info">
                <span class="number">{{ fila.n }}</span>
                <span class="activity-name" :title="fila.nombre">{{ fila.nombre }}</span>
                <span class="date-cell">{{ fila.inicio }}</span>
                <span class="date-cell">{{ fila.fin }}</span>
                <span class="date-cell col-centro">{{ fila.duracion ?? '—' }}</span>
                <span class="date-cell col-centro">{{ fila.p }}%</span>
                <span class="date-cell col-centro">{{ fila.r }}%</span>
                <span class="estado-cell">
                  <i :class="badgeClass(fila.estado)" :title="fila.estado">{{ fila.estado }}</i>
                  <button type="button" class="action-btn edit-btn" title="Editar actividad" @click="abrirEditarActividad(fila.raw)">
                    <i class="ti ti-pencil"></i>
                  </button>
                </span>
              </div>

              <div class="gantt-timeline activity-track">
                <span v-for="week in weeks" :key="week" class="week-cell"></span>

                <div class="planned-bar" :style="ganttStyle(fila)"
                     :title="`${fila.nombre} · ${fila.inicio} - ${fila.fin} · Programado ${fila.p}%`">
                  <span v-if="fila.p >= 8">{{ fila.p }}%</span>
                </div>

                <div v-if="fila.r > 0" class="actual-bar" :style="realStyle(fila)" :title="`Avance real: ${fila.r}%`">
                  <span v-if="fila.r >= 12">{{ fila.r }}%</span>
                </div>
              </div>

            </template>

            <div v-if="!actividades.length" class="empty-gantt">
              <div class="empty-icon"><i class="ti ti-calendar-off"></i></div>
              <div>
                <strong>No hay actividades todavía</strong>
                <small>Haz clic en "Nueva actividad" para comenzar a construir el cronograma.</small>
              </div>
            </div>

          </div>
        </div>

        <footer class="gantt-note">
          <div><i class="ti ti-info-circle"></i></div>
          <span>
            La barra clara representa el avance programado y la barra sólida representa el avance real.
            <template v-if="ultimaActualizacion">Última actualización: <strong>{{ ultimaActualizacion }}</strong>.</template>
          </span>
        </footer>

      </section>

    </template>

    <div v-if="mostrarModal" class="modal-overlay" @click.self="cerrarModal">
      <div class="modal-actividad">

        <div class="modal-header">
          <div class="modal-title">
            <div class="modal-icon"><i :class="modoEdicion ? 'ti ti-pencil' : 'ti ti-plus'"></i></div>
            <div>
              <h3>{{ modoEdicion ? 'Editar actividad' : 'Nueva actividad' }}</h3>
              <p>{{ modoEdicion ? 'Modifica los datos de la actividad.' : 'Agrega una actividad al cronograma del proyecto.' }}</p>
            </div>
          </div>
          <button type="button" class="modal-close" @click="cerrarModal"><i class="ti ti-x"></i></button>
        </div>

        <form class="form-actividad" @submit.prevent="guardarActividad">

          <div v-if="errorFormulario" class="form-error">
            <i class="ti ti-alert-circle"></i>
            <span>{{ errorFormulario }}</span>
          </div>

          <div class="form-grid">

            <div class="form-group">
              <label>N° de actividad</label>
              <div class="input-wrap">
                <i class="ti ti-hash"></i>
                <input v-model.number="actividadFormulario.numero" type="number" min="1" required />
              </div>
            </div>

            <div class="form-group">
              <label>Estado</label>
              <div class="input-wrap">
                <i class="ti ti-flag"></i>
                <select v-model="actividadFormulario.estado" required>
                  <option value="Pendiente">Pendiente</option>
                  <option value="En curso">En curso</option>
                  <option value="En ejecución">En ejecución</option>
                  <option value="Concluida">Concluida</option>
           
                </select>
              </div>
            </div>

            <div class="form-group form-full">
              <label>Actividad</label>
              <div class="input-wrap">
                <i class="ti ti-clipboard-text"></i>
                <input v-model="actividadFormulario.actividad" type="text" maxlength="255"
                       placeholder="Ej. Excavación y preparación del terreno" required />
              </div>
            </div>

            <div class="form-group">
              <label>Fecha de inicio</label>
              <div class="input-wrap">
                <i class="ti ti-calendar"></i>
                <input v-model="actividadFormulario.fecha_inicio" type="date" required />
              </div>
            </div>

            <div class="form-group">
              <label>Fecha de finalización</label>
              <div class="input-wrap">
                <i class="ti ti-calendar-check"></i>
                <input v-model="actividadFormulario.fecha_fin" type="date" :min="actividadFormulario.fecha_inicio" required />
              </div>
            </div>

            <div class="form-group">
              <label>Cumplimiento real</label>
              <div class="input-wrap percentage-input">
                <i class="ti ti-chart-line"></i>
                <input v-model.number="actividadFormulario.porcentaje_cumplimiento_real" type="number" min="0" max="100" step="0.01" />
                <span>%</span>
              </div>
            </div>

          </div>

          <div class="modal-actions">
            <button type="button" class="btn-cancelar" @click="cerrarModal" :disabled="guardando">Cancelar</button>
            <button type="submit" class="btn-guardar" :disabled="guardando">
              <i v-if="guardando" class="ti ti-loader-2 spin"></i>
              <i v-else :class="modoEdicion ? 'ti ti-device-floppy' : 'ti ti-plus'"></i>
              {{ guardando ? 'Guardando...' : (modoEdicion ? 'Actualizar actividad' : 'Guardar actividad') }}
            </button>
          </div>

        </form>

      </div>
    </div>

  </div>
</template>

<style scoped>
/* ============================================================
   VARIABLES
============================================================ */
.gantt-page {
  --accent: #00c9a7;
  --accent-strong: #00d0aa;
  --accent-10: rgba(0,201,167,.10);
  --accent-12: rgba(0,201,167,.12);
  --accent-18: rgba(0,201,167,.18);
  --accent-border: rgba(0,229,192,.4);
  --accent-border-soft: rgba(0,201,167,.2);
  --blue: #55b8ef;
  --blue-10: rgba(77,179,240,.1);
  --blue-18: rgba(77,179,240,.18);
  --purple: #a482ff;
  --purple-10: rgba(164,130,255,.1);
  --danger: #f28b82;
  --danger-10: rgba(242,139,130,.08);
  --danger-18: rgba(242,139,130,.18);
  --muted: #91a9ba;
  --muted-10: rgba(142,169,191,.1);
  --warning: #f0b43c;
  --warning-10: rgba(240,180,60,.1);
  --warning-2: rgba(240,180,60,.2);

  --bg-0: #081520;
  --bg-1: #0a1a28;
  --bg-2: #0b1c2b;
  --bg-3: #0d2233;
  --border-1: #1b354a;
  --border-2: #244158;
  --border-3: #29465c;

  --text: #d4e4f0;
  --text-strong: #e4f0f7;
  --text-soft: #c9dce8;
  --text-muted: #7893a7;
  --text-muteder: #718ca1;

  width: 100%;
  max-width: 1680px;
  margin: 0 auto;
  color: var(--text);
}

/* ============================================================
   HEADER
============================================================ */
.cronograma-header { display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 22px; }
.header-title { display: flex; align-items: center; gap: 13px; }
.header-icon {
  width: 42px; height: 42px; display: flex; justify-content: center; align-items: center;
  border: 1px solid var(--accent-border-soft); border-radius: 10px;
  background: linear-gradient(145deg, rgba(0,201,167,.18), rgba(0,201,167,.04));
  color: var(--accent); font-size: 21px; box-shadow: 0 8px 25px rgba(0,0,0,.16);
}
.cronograma-header h2 { margin: 0; color: var(--text-strong); font-size: 1.08rem; font-weight: 750; letter-spacing: -.01em; }
.cronograma-header p { margin: 5px 0 0; color: #7894aa; font-size: .74rem; }

.btn-nueva-actividad {
  display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 38px;
  border: 1px solid var(--accent-border); border-radius: 8px; padding: 0 15px;
  background: linear-gradient(135deg, #00d3ae, #00b99b); color: #04151b;
  font-size: .74rem; font-weight: 800; cursor: pointer; white-space: nowrap;
  box-shadow: 0 5px 18px rgba(0,201,167,.15);
  transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
}
.btn-nueva-actividad:hover { transform: translateY(-1px); filter: brightness(1.06); box-shadow: 0 8px 25px rgba(0,201,167,.23); }
.btn-nueva-actividad i { font-size: 17px; }

/* ============================================================
   SUMMARY
============================================================ */
.gantt-summary {
  display: flex; align-items: stretch; min-height: 86px; margin-bottom: 20px; overflow: hidden;
  border: 1px solid var(--border-1); border-radius: 12px;
  background: linear-gradient(135deg, var(--bg-3), var(--bg-2));
  box-shadow: 0 8px 30px rgba(0,0,0,.1);
}
.summary-item { display: flex; align-items: center; gap: 13px; min-width: 170px; padding: 16px 22px; border-right: 1px solid #19354a; }
.summary-icon { width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; border-radius: 9px; background: var(--accent-10); color: var(--accent); font-size: 19px; }
.summary-icon.success { color: #00d0aa; }
.summary-icon.blue { background: var(--blue-10); color: var(--blue); }
.summary-icon.purple { background: var(--purple-10); color: var(--purple); }
.summary-item strong { display: block; color: #e0edf5; font-size: 1.05rem; line-height: 1; }
.summary-item span { display: block; margin-top: 5px; color: #7793a8; font-size: .68rem; }

.summary-legend { display: flex; align-items: center; gap: 20px; margin-left: auto; padding: 0 24px; color: #829caf; font-size: .69rem; white-space: nowrap; }
.summary-legend span { display: flex; align-items: center; gap: 6px; }
.summary-legend i { width: 17px; height: 7px; display: block; border-radius: 3px; }
.planned-dot { background: rgba(0,201,167,.32); border: 1px solid var(--accent-border); }
.actual-dot { background: var(--accent); }

/* ============================================================
   GANTT CARD
============================================================ */
.gantt-card { overflow: hidden; border: 1px solid #1d3a51; border-radius: 13px; background: var(--bg-2); box-shadow: 0 12px 35px rgba(0,0,0,.12); }
.gantt-card-header {
  min-height: 72px; display: flex; align-items: center; justify-content: space-between; gap: 20px;
  padding: 15px 20px; border-bottom: 1px solid var(--border-1);
  background: linear-gradient(135deg, #0e2436, var(--bg-2));
}
.gantt-card-header h3 { margin: 0; display: flex; align-items: center; gap: 8px; color: #d8e8f1; font-size: .86rem; }
.gantt-card-header h3 i { color: var(--accent); font-size: 18px; }
.gantt-card-header span { display: block; margin-top: 5px; color: var(--text-muteder); font-size: .67rem; }

.timeline-info { display: flex; align-items: center; gap: 7px; padding: 8px 12px; border: 1px solid #24445a; border-radius: 7px; background: rgba(7,22,34,.65); color: #91aabd; font-size: .66rem; }
.timeline-info i { color: var(--accent); }

/* ============================================================
   SCROLL / TABLE
============================================================ */
.gantt-scroll { width: 100%; overflow-x: auto; overflow-y: hidden; scrollbar-width: thin; scrollbar-color: #31536b #091722; }
.gantt-scroll::-webkit-scrollbar { height: 8px; }
.gantt-scroll::-webkit-scrollbar-track { background: #091722; }
.gantt-scroll::-webkit-scrollbar-thumb { border-radius: 10px; background: #31536b; }

.gantt-table { min-width: 1520px; display: grid; grid-template-columns: 700px minmax(820px, 1fr); grid-template-rows: 40px 32px; }

.gantt-side { display: grid; grid-template-columns: 36px 1fr 66px 66px 50px 62px 62px 142px; align-items: center; }
.header-side { background: var(--bg-0); border-right: 1px solid var(--border-2); color: #8da6b8; font-size: .64rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; }
.header-side span { padding: 0 10px; }

.gantt-timeline { position: relative; display: grid; grid-template-columns: repeat(var(--n-cols), minmax(16px, 1fr)); }
.gantt-months { overflow: hidden; background: linear-gradient(135deg, #0879b9, #086da7); color: #fff; }
.month { grid-column: span 4; display: flex; align-items: center; justify-content: center; border-right: 1px solid rgba(255,255,255,.19); font-size: .63rem; font-weight: 800; letter-spacing: .04em; }

.week-side { background: #091926; border-top: 1px solid #1a3448; border-right: 1px solid var(--border-2); }
.week-row { background: var(--bg-1); border-top: 1px solid #1a3448; }
.week-row span { display: flex; justify-content: center; align-items: center; border-right: 1px solid var(--border-1); color: #6f8b9e; font-size: .58rem; font-weight: 700; }
.week-row span:nth-child(4n) { border-right-color: #35556a; }

.today-marker { position: absolute; top: 0; bottom: -3000px; width: 2px; z-index: 20; background: #ff5577; pointer-events: none; box-shadow: 0 0 7px rgba(255,85,119,.45); }
.today-marker::before { content: ''; position: absolute; top: -1px; left: -4px; width: 10px; height: 10px; border-radius: 50%; background: #ff5577; box-shadow: 0 0 0 3px rgba(255,85,119,.12); }
.today-marker span { position: absolute; top: 11px; left: 5px; padding: 2px 4px; border-radius: 3px; background: #ff5577; color: #fff; font-size: .46rem; font-weight: 800; }

/* ============================================================
   ROWS
============================================================ */
.activity-info { min-height: 60px; border-top: 1px solid #152d41; border-right: 1px solid var(--border-2); background: #0b1d2c; color: #829bae; font-size: .69rem; transition: background .15s ease; }
.activity-info:hover { background: #0e2435; }
.activity-info span { padding: 0 10px; }
.number { color: var(--accent); font-weight: 800; text-align: center; }
.activity-name { color: var(--text-soft); font-size: .75rem; font-weight: 550; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.date-cell { color: #7893a7; font-size: .64rem; }
.date-cell.col-centro { text-align: center; }
.estado-cell { display: flex; align-items: center; gap: 6px; min-width: 0; overflow: hidden; padding-right: 8px; }
.estado-cell .badge { overflow: hidden; text-overflow: ellipsis; max-width: 96px; }

.action-btn { width: 27px; height: 27px; flex: 0 0 27px; display: inline-flex; justify-content: center; align-items: center; border: 1px solid transparent; border-radius: 6px; cursor: pointer; font-size: 13px; transition: background .15s ease, border-color .15s ease, transform .15s ease; }
.edit-btn { margin-left: 2px; background: var(--blue-10); border-color: rgba(77,179,240,.16); color: var(--blue); }
.edit-btn:hover { background: var(--blue-18); border-color: rgba(77,179,240,.3); transform: translateY(-1px); }

.activity-track { min-height: 60px; border-top: 1px solid #152d41; background: var(--bg-1); isolation: isolate; }
.activity-track::after { content: ''; position: absolute; inset: 0; z-index: 0; pointer-events: none; background: linear-gradient(90deg, transparent, rgba(255,255,255,.012), transparent); }
.week-cell { border-right: 1px solid #19374d; opacity: .85; }
.week-cell:nth-child(4n) { border-right-color: #35576b; }

/* ============================================================
   BARRAS
============================================================ */
.planned-bar, .actual-bar { position: absolute; left: 0; border-radius: 4px; transition: filter .2s ease, transform .2s ease; }
.planned-bar {
  top: 17px; height: 26px; min-width: 5px; z-index: 2;
  background: linear-gradient(90deg, rgba(0,201,167,.2), rgba(0,201,167,.32));
  border: 1px solid var(--accent-border); box-shadow: inset 0 1px 0 rgba(255,255,255,.04);
}
.planned-bar:hover { filter: brightness(1.18); }
.planned-bar span { position: absolute; top: 50%; right: 5px; transform: translateY(-50%); color: #bff8ed; font-size: .57rem; font-weight: 800; white-space: nowrap; }

.actual-bar {
  top: 24px; height: 12px; min-width: 3px; z-index: 4; overflow: hidden;
  background: linear-gradient(90deg, #00ae94, #00d0ae);
  border: 1px solid var(--accent-border); box-shadow: 0 1px 8px rgba(0,201,167,.28);
}
.actual-bar span { position: absolute; left: 4px; top: 50%; transform: translateY(-50%); color: #e8fffa; font-size: .48rem; font-weight: 800; line-height: 1; white-space: nowrap; }

/* ============================================================
   BADGES
============================================================ */
.badge { display: inline-flex; align-items: center; justify-content: center; padding: 3px 8px; border-radius: 20px; font-size: .57rem; font-weight: 800; font-style: normal; line-height: 1.1; white-space: nowrap; }
.badge-success { background: var(--accent-12); border: 1px solid var(--accent-18); color: #00d2ad; }
.badge-info { background: var(--blue-10); border: 1px solid var(--blue-18); color: var(--blue); }
.badge-danger { background: var(--danger-10); border: 1px solid var(--danger-18); color: var(--danger); }
.badge-muted { background: var(--muted-10); border: 1px solid rgba(142,169,191,.13); color: var(--muted); }
.badge-warning { background: var(--warning-10); border: 1px solid var(--warning-2); color: var(--warning); }

/* ============================================================
   EMPTY / FOOTER
============================================================ */
.empty-gantt {
  grid-column: 1 / -1; min-height: 190px; display: flex; justify-content: center; align-items: center; gap: 13px;
  border-top: 1px solid #152d41; background: radial-gradient(circle at center, rgba(0,201,167,.045), transparent 45%); color: #8ea9bb;
}
.empty-icon { width: 45px; height: 45px; display: flex; justify-content: center; align-items: center; border-radius: 10px; background: var(--accent-10); color: var(--accent); font-size: 23px; }
.empty-gantt strong { display: block; color: var(--text-soft); font-size: .76rem; }
.empty-gantt small { display: block; margin-top: 4px; color: #718da2; font-size: .65rem; }

.gantt-note { display: flex; align-items: center; gap: 9px; min-height: 48px; padding: 9px 18px; border-top: 1px solid #19354b; background: var(--bg-0); color: #728da2; font-size: .65rem; }
.gantt-note > div { width: 24px; height: 24px; display: flex; justify-content: center; align-items: center; flex: 0 0 24px; border-radius: 6px; background: var(--accent-10); }
.gantt-note i { color: var(--accent); font-size: 14px; }
.gantt-note strong { color: #a3baca; font-weight: 700; }

/* ============================================================
   ESTADOS (loading / error)
============================================================ */
.gantt-state { display: flex; justify-content: center; align-items: center; gap: 12px; min-height: 120px; padding: 25px; border: 1px solid #1e3a52; border-radius: 11px; background: #0d1f30; color: #8ea9bf; }
.state-icon { width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; flex: 0 0 40px; border-radius: 10px; background: var(--accent-10); color: var(--accent); font-size: 19px; }
.gantt-state strong { display: block; color: #cbdde8; font-size: .78rem; }
.gantt-state span { display: block; margin-top: 3px; color: var(--text-muteder); font-size: .66rem; }
.gantt-state-error { border-color: rgba(242,139,130,.18); }
.error-icon { background: var(--danger-10); color: var(--danger); }

.btn-reintentar { display: inline-flex; align-items: center; gap: 5px; margin-top: 8px; padding: 6px 10px; border: 1px solid #31516a; border-radius: 6px; background: transparent; color: #a9c0d0; font-size: .65rem; cursor: pointer; }
.btn-reintentar:hover { background: rgba(255,255,255,.04); }

/* ============================================================
   MODAL
============================================================ */
.modal-overlay { position: fixed; inset: 0; z-index: 9999; display: flex; justify-content: center; align-items: center; padding: 20px; background: rgba(1,8,14,.78); backdrop-filter: blur(5px); }
.modal-actividad {
  width: min(720px, 100%); max-height: 92vh; overflow-y: auto; border: 1px solid #29485e; border-radius: 14px;
  background: linear-gradient(145deg, var(--bg-3), var(--bg-2)); box-shadow: 0 25px 90px rgba(0,0,0,.58);
  scrollbar-width: thin; scrollbar-color: #31536b transparent;
}

.modal-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 15px; padding: 22px 24px; border-bottom: 1px solid #1b394e; background: linear-gradient(135deg, rgba(0,201,167,.045), transparent); }
.modal-title { display: flex; align-items: center; gap: 11px; }
.modal-icon { width: 38px; height: 38px; display: flex; justify-content: center; align-items: center; border: 1px solid var(--accent-border-soft); border-radius: 9px; background: rgba(0,201,167,.09); color: var(--accent); font-size: 18px; }
.modal-header h3 { margin: 0; color: #dceaf2; font-size: .92rem; }
.modal-header p { margin: 4px 0 0; color: #718da2; font-size: .65rem; }
.modal-close { width: 30px; height: 30px; display: flex; justify-content: center; align-items: center; border: 1px solid transparent; border-radius: 6px; background: transparent; color: #7893a7; font-size: 18px; cursor: pointer; transition: .15s; }
.modal-close:hover { border-color: #29465c; background: rgba(255,255,255,.04); color: #e0edf5; }

/* ============================================================
   FORM
============================================================ */
.form-actividad { padding: 24px; }
.form-error { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 18px; padding: 11px 13px; border: 1px solid rgba(242,139,130,.25); border-radius: 8px; background: var(--danger-10); color: var(--danger); font-size: .7rem; line-height: 1.4; }
.form-error i { margin-top: 1px; font-size: 15px; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px 20px; }
.form-group { display: flex; flex-direction: column; gap: 7px; }
.form-full { grid-column: 1 / -1; }
.form-group label { color: #9cb5c6; font-size: .67rem; font-weight: 750; }

.input-wrap { position: relative; display: flex; align-items: center; }
.input-wrap > i { position: absolute; left: 12px; z-index: 2; color: #5f7c91; font-size: 15px; pointer-events: none; }

.form-group input, .form-group select {
  width: 100%; box-sizing: border-box; min-height: 42px; padding: 10px 12px 10px 36px;
  border: 1px solid var(--border-3); border-radius: 8px; outline: none; background: #081a29; color: var(--text);
  font-size: .72rem; transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
}
.form-group select { appearance: auto; }
.form-group input::placeholder { color: #536e82; }
.form-group input:focus, .form-group select:focus { border-color: rgba(0,201,167,.65); background: #091d2c; box-shadow: 0 0 0 3px rgba(0,201,167,.07); }

.percentage-input input { padding-right: 32px; }
.percentage-input > span { position: absolute; right: 13px; color: #6e8a9d; font-size: .68rem; pointer-events: none; }

.modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; padding-top: 18px; border-top: 1px solid #19374c; }
.btn-cancelar, .btn-guardar { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 40px; padding: 0 16px; border-radius: 8px; font-size: .7rem; font-weight: 750; cursor: pointer; transition: .15s; }
.btn-cancelar { border: 1px solid #31516a; background: transparent; color: #91aabd; }
.btn-cancelar:hover:not(:disabled) { background: rgba(255,255,255,.04); border-color: #45657b; }
.btn-guardar { border: 1px solid var(--accent-border); background: linear-gradient(135deg, #00d0aa, #00b99b); color: #04151b; box-shadow: 0 5px 15px rgba(0,201,167,.12); }
.btn-guardar:hover:not(:disabled) { filter: brightness(1.07); transform: translateY(-1px); }
.btn-cancelar:disabled, .btn-guardar:disabled { opacity: .55; cursor: not-allowed; }

.spin { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ============================================================
   RESPONSIVE
============================================================ */
@media (max-width: 950px) {
  .gantt-summary { flex-wrap: wrap; }
  .summary-item { flex: 1 1 160px; min-width: 0; }
  .summary-legend { width: 100%; min-height: 42px; margin-left: 0; padding: 0 17px; border-top: 1px solid #19354a; }
}

@media (max-width: 800px) {
  .cronograma-header { align-items: stretch; flex-direction: column; }
  .header-title { align-items: flex-start; }
  .btn-nueva-actividad { width: 100%; }
  .gantt-card-header { align-items: flex-start; flex-direction: column; }
  .timeline-info { width: fit-content; }
  .gantt-summary { border-radius: 9px; }
  .summary-item { flex: 1 1 50%; border-bottom: 1px solid #19354a; }
  .summary-legend { padding-bottom: 10px; }
  .form-grid { grid-template-columns: 1fr; }
  .form-full { grid-column: auto; }
  .modal-overlay { align-items: flex-end; padding: 8px; }
  .modal-actividad { max-height: 94vh; border-radius: 12px 12px 8px 8px; }
}

@media (max-width: 520px) {
  .cronograma-header h2 { font-size: .95rem; }
  .cronograma-header p { font-size: .67rem; }
  .header-icon { width: 37px; height: 37px; flex-basis: 37px; }
  .summary-item { min-width: 50%; padding: 10px 12px; }
  .summary-icon { width: 30px; height: 30px; font-size: 15px; }
  .summary-item strong { font-size: .82rem; }
  .summary-item span { font-size: .59rem; }
  .summary-legend { gap: 12px; font-size: .6rem; }
  .modal-header { padding: 15px; }
  .form-actividad { padding: 15px; }
  .modal-actions { flex-direction: column-reverse; }
  .btn-cancelar, .btn-guardar { width: 100%; }
}
</style>