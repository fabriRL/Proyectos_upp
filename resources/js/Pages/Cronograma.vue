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

/* ============================================================
   PROYECTO
============================================================ */

const identificador = computed(() =>
  props.proyecto?.codigo ??
  props.proyecto?.id_proyecto ??
  props.proyectoId ??
  route.params[props.routeParam] ??
  route.params.codigo ??
  route.params.proyecto ??
  route.params.id ??
  null
)

const proyectoInfo = ref(props.proyecto ?? null)
const actividadesRaw = ref([])
const loading = ref(true)
const error = ref(null)

/* ============================================================
   MODAL ACTIVIDAD
============================================================ */

const mostrarModal = ref(false)
const guardando = ref(false)
const errorFormulario = ref(null)

const modoEdicion = ref(false)
const actividadEditandoId = ref(null)

const actividadFormulario = ref({
  numero: '',
  actividad: '',
  fecha_inicio: '',
  fecha_fin: '',
  estado: 'Pendiente',
  porcentaje_cumplimiento_programado: 0,
  porcentaje_cumplimiento_real: 0,
})

/* ============================================================
   NUEVA ACTIVIDAD
============================================================ */

function abrirNuevaActividad() {
  if (actividadesRaw.value.length >= 15) {
    showToast('Este proyecto ya alcanzó el límite de 15 actividades. No se pueden registrar más.', 'warning')
    return
  }

  errorFormulario.value = null
  modoEdicion.value = false
  actividadEditandoId.value = null

  const siguienteNumero = actividadesRaw.value.length
    ? Math.max(
        ...actividadesRaw.value.map(
          a => Number(a.numero) || 0
        )
      ) + 1
    : 1

  actividadFormulario.value = {
    numero: siguienteNumero,
    actividad: '',
    fecha_inicio: '',
    fecha_fin: '',
    estado: 'Pendiente',
    porcentaje_cumplimiento_programado: 0,
    porcentaje_cumplimiento_real: 0,
  }

  mostrarModal.value = true
}

/* ============================================================
   EDITAR ACTIVIDAD
============================================================ */

function abrirEditarActividad(actividad) {
  errorFormulario.value = null
  modoEdicion.value = true
  actividadEditandoId.value = actividad.id_actividad

  actividadFormulario.value = {
    numero: Number(actividad.numero) || 1,
    actividad: actividad.actividad ?? '',
    fecha_inicio: normalizarFechaInput(
      actividad.fecha_inicio
    ),
    fecha_fin: normalizarFechaInput(
      actividad.fecha_fin
    ),
    estado: actividad.estado ?? 'Pendiente',
    porcentaje_cumplimiento_programado:
      Number(
        actividad.porcentaje_cumplimiento_programado ?? 0
      ),
    porcentaje_cumplimiento_real:
      Number(
        actividad.porcentaje_cumplimiento_real ?? 0
      ),
  }

  mostrarModal.value = true
}

/* ============================================================
   CERRAR MODAL
============================================================ */

function cerrarModal() {
  if (guardando.value) return

  mostrarModal.value = false
  errorFormulario.value = null
  modoEdicion.value = false
  actividadEditandoId.value = null
}

/* ============================================================
   FECHA PARA INPUT DATE
============================================================ */

function normalizarFechaInput(value) {
  if (!value) return ''

  if (typeof value === 'string') {
    return value.substring(0, 10)
  }

  const d = new Date(value)

  if (isNaN(d)) return ''

  const year = d.getFullYear()
  const month = String(
    d.getMonth() + 1
  ).padStart(2, '0')
  const day = String(
    d.getDate()
  ).padStart(2, '0')

  return `${year}-${month}-${day}`
}

/* ============================================================
   CARGAR PROYECTO
============================================================ */

async function cargarProyecto() {
  if (props.proyecto) {
    proyectoInfo.value = props.proyecto
    return
  }

  try {
    const { data } = await axios.get(
      `/api/proyectos/${identificador.value}`
    )

    proyectoInfo.value = data
  } catch (e) {
    console.error(e)
  }
}

/* ============================================================
   CARGAR ACTIVIDADES
============================================================ */

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

    const { data } = await axios.get(
      `/api/proyectos/${identificador.value}/actividades`
    )

    actividadesRaw.value =
      Array.isArray(data) ? data : []
  } catch (e) {
    console.error(e)

    error.value =
      e.response?.data?.message ??
      'No se pudo cargar el cronograma. Intenta nuevamente.'
  } finally {
    loading.value = false
  }
}

/* ============================================================
   VALIDAR FORMULARIO
============================================================ */

function validarFormulario() {
  errorFormulario.value = null

  if (!actividadFormulario.value.numero) {
    errorFormulario.value =
      'El número de actividad es obligatorio.'
    return false
  }

  if (!actividadFormulario.value.actividad.trim()) {
    errorFormulario.value =
      'Debes escribir el nombre de la actividad.'
    return false
  }

  if (!actividadFormulario.value.fecha_inicio) {
    errorFormulario.value =
      'Debes seleccionar la fecha de inicio.'
    return false
  }

  if (!actividadFormulario.value.fecha_fin) {
    errorFormulario.value =
      'Debes seleccionar la fecha de finalización.'
    return false
  }

  if (actividadFormulario.value.fecha_fin < actividadFormulario.value.fecha_inicio) {
    errorFormulario.value =
      'La fecha de finalización no puede ser anterior a la fecha de inicio.'
    return false
  }
  

  const programado =
    Number(
      actividadFormulario.value
        .porcentaje_cumplimiento_programado
    )

  const real =
    Number(
      actividadFormulario.value
        .porcentaje_cumplimiento_real
    )

  if (programado < 0 || programado > 100) {
    errorFormulario.value =
      'El cumplimiento programado debe estar entre 0 y 100%.'
    return false
  }

  if (real < 0 || real > 100) {
    errorFormulario.value =
      'El cumplimiento real debe estar entre 0 y 100%.'
    return false
  }

  return true
}

/* ============================================================
   DURACIÓN
============================================================ */

function calcularDuracion(inicio, fin) {
  const fechaInicio = new Date(
    `${inicio}T00:00:00`
  )

  const fechaFin = new Date(
    `${fin}T00:00:00`
  )

  return (
    Math.floor(
      (fechaFin - fechaInicio) / 86400000
    ) + 1
  )
}

/* ============================================================
   GUARDAR ACTIVIDAD
============================================================ */

async function guardarActividad() {
  if (!validarFormulario()) return

  guardando.value = true
  errorFormulario.value = null

  try {
    const duracionDias = calcularDuracion(
      actividadFormulario.value.fecha_inicio,
      actividadFormulario.value.fecha_fin
    )

    const payload = {
      numero: Number(
        actividadFormulario.value.numero
      ),

      actividad:
        actividadFormulario.value.actividad.trim(),

      fecha_inicio:
        actividadFormulario.value.fecha_inicio,

      fecha_fin:
        actividadFormulario.value.fecha_fin,

      duracion_dias: duracionDias,

      estado:
        actividadFormulario.value.estado,

      porcentaje_cumplimiento_programado:
        Number(
          actividadFormulario.value
            .porcentaje_cumplimiento_programado || 0
        ),

      porcentaje_cumplimiento_real:
        Number(
          actividadFormulario.value
            .porcentaje_cumplimiento_real || 0
        ),
    }

    /* ========================================================
       EDITAR
    ======================================================== */

    if (
      modoEdicion.value &&
      actividadEditandoId.value
    ) {
      await axios.put(
        `/api/actividades/${actividadEditandoId.value}`,
        payload
      )
    }

    /* ========================================================
       CREAR
    ======================================================== */

    else {
      await axios.post(
        `/api/proyectos/${identificador.value}/actividades`,
        payload
      )
    }

    cerrarModal()

    await cargarActividades()

  } catch (e) {
    console.error(e)

    if (e.response?.status === 422) {
      const errores =
        e.response.data.errors

      if (errores) {
        errorFormulario.value =
          Object.values(errores)
            .flat()
            .join(' ')
      } else {
        errorFormulario.value =
          e.response.data.message ??
          'Los datos enviados no son válidos.'
      }
    } else {
      errorFormulario.value =
        e.response?.data?.message ??
        (
          modoEdicion.value
            ? 'No se pudo actualizar la actividad.'
            : 'No se pudo registrar la actividad.'
        )
    }
  } finally {
    guardando.value = false
  }
}

/* ============================================================
   UTILIDADES FECHA
============================================================ */

function toDate(value) {
  if (!value) return null

  const d =
    value instanceof Date
      ? value
      : new Date(value)

  return isNaN(d) ? null : d
}

function formatDate(value) {
  const d = toDate(value)

  if (!d) return '—'

  const day = String(
    d.getDate()
  ).padStart(2, '0')

  const month = String(
    d.getMonth() + 1
  ).padStart(2, '0')

  const year = String(
    d.getFullYear()
  ).slice(-2)

  return `${day}/${month}/${year}`
}

function diffDays(a, b) {
  return Math.round(
    (a - b) / 86400000
  )
}

/* ============================================================
   ACTIVIDADES PARA VISTA
============================================================ */

const actividades = computed(() =>
  [...actividadesRaw.value]
    .sort(
      (a, b) =>
        (a.numero ?? 0) -
        (b.numero ?? 0)
    )
    .map(a => ({
      id: a.id_actividad,

      raw: a,

      n: a.numero,

      nombre: a.actividad,

      inicio: formatDate(
        a.fecha_inicio
      ),

      fin: formatDate(
        a.fecha_fin
      ),

      estado: a.estado,

      p: Math.min(
        100,
        Math.max(
          0,
          Number(
            a.porcentaje_cumplimiento_programado ??
            0
          )
        )
      ),

      r: Math.min(
        100,
        Math.max(
          0,
          Number(
            a.porcentaje_cumplimiento_real ??
            0
          )
        )
      ),

      fechaInicioRaw:
        toDate(a.fecha_inicio),

      fechaFinRaw:
        toDate(a.fecha_fin),

      actualizadoEn:
        toDate(a.actualizado_en),
    }))
)

/* ============================================================
   LINEA DE TIEMPO
============================================================ */

const monthNames = [
  'ENERO',
  'FEBRERO',
  'MARZO',
  'ABRIL',
  'MAYO',
  'JUNIO',
  'JULIO',
  'AGOSTO',
  'SEPTIEMBRE',
  'OCTUBRE',
  'NOVIEMBRE',
  'DICIEMBRE'
]

const proyectoInicio = computed(() =>
  toDate(
    proyectoInfo.value
      ?.fecha_inicio_contractual
  )
)

const proyectoFin = computed(() =>
  toDate(
    proyectoInfo.value
      ?.fecha_conclusion_actual
  ) ??
  toDate(
    proyectoInfo.value
      ?.fecha_conclusion_inicial_contractual
  )
)

/*
  Por defecto (sin actividades ni fechas del proyecto) la línea de
  tiempo muestra SOLO el mes actual — "hoy" siempre se incluye como
  candidata, así que el rango nunca queda vacío ni salta a mostrar
  el año completo. En cuanto exista una actividad o fecha de
  proyecto fuera de ese mes, el rango se estira solo para incluirla.
*/

const timelineStart = computed(() => {
  const hoy = new Date()

  const candidatas = [
    proyectoInicio.value,
    hoy,
    ...actividades.value.map(
      a => a.fechaInicioRaw
    )
  ].filter(Boolean)

  const min = candidatas.reduce(
    (acc, d) =>
      d < acc ? d : acc,
    candidatas[0]
  )

  return new Date(
    min.getFullYear(),
    min.getMonth(),
    1
  )
})

const timelineEnd = computed(() => {
  const hoy = new Date()

  const candidatas = [
    proyectoFin.value,
    hoy,
    ...actividades.value.map(
      a => a.fechaFinRaw
    )
  ].filter(Boolean)

  const max = candidatas.reduce(
    (acc, d) =>
      d > acc ? d : acc,
    candidatas[0]
  )

  return new Date(
    max.getFullYear(),
    max.getMonth() + 1,
    0
  )
})

const totalDias = computed(() =>
  Math.max(
    diffDays(
      timelineEnd.value,
      timelineStart.value
    ) + 1,
    1
  )
)

const months = computed(() => {
  const list = []

  let cursor = new Date(
    timelineStart.value
  )

  while (
    cursor <= timelineEnd.value
  ) {
    list.push({
      key:
        `${cursor.getFullYear()}-${cursor.getMonth()}`,

      label:
        monthNames[
          cursor.getMonth()
        ],
    })

    cursor = new Date(
      cursor.getFullYear(),
      cursor.getMonth() + 1,
      1
    )
  }

  return list
})

const weeks = computed(() =>
  Array.from(
    {
      length:
        months.value.length * 4
    },
    (_, i) => i
  )
)

function dayOffset(date) {
  return diffDays(
    date,
    timelineStart.value
  )
}

const hoyOffset = computed(() => {
  const hoy = new Date()
  const off = dayOffset(hoy)

  if (
    off < 0 ||
    off > totalDias.value
  ) {
    return null
  }

  return (
    off /
    totalDias.value
  ) * 100
})

function ganttStyle(fila) {
  if (
    !fila.fechaInicioRaw ||
    !fila.fechaFinRaw
  ) {
    return {
      left: '0%',
      width: '.8%'
    }
  }

  const start = Math.max(
    0,
    dayOffset(
      fila.fechaInicioRaw
    )
  )

  const end = Math.min(
    dayOffset(
      fila.fechaFinRaw
    ),
    totalDias.value
  )

  const left =
    (start / totalDias.value) *
    100

  const width = Math.max(
    (
      (end - start + 1) /
      totalDias.value
    ) * 100,
    .8
  )

  return {
    left: `${left}%`,
    width: `${width}%`
  }
}

function realStyle(fila) {
  const base =
    ganttStyle(fila)

  const totalWidth =
    parseFloat(base.width)

  return {
    ...base,
    width: `${Math.max(
      (
        totalWidth *
        fila.r
      ) / 100,
      0
    )}%`
  }
}

function badgeClass(estado) {
  const map = {
    'Concluida':
      'badge badge-success',

    'En curso':
      'badge badge-info',

    'Retrasada':
      'badge badge-danger',

    'Pendiente':
      'badge badge-muted',
  }

  return (
    map[estado] ??
    'badge badge-muted'
  )
}

/* ============================================================
   RESUMEN
============================================================ */

const completadas = computed(() =>
  actividades.value.filter(
    a =>
      a.estado === 'Concluida'
  ).length
)

const avanceProgramado = computed(() => {
  if (!actividades.value.length)
    return 0

  const total =
    actividades.value.reduce(
      (sum, a) =>
        sum + a.p,
      0
    )

  return Math.round(
    total /
    actividades.value.length
  )
})

const avanceReal = computed(() => {
  if (!actividades.value.length)
    return 0

  const total =
    actividades.value.reduce(
      (sum, a) =>
        sum + a.r,
      0
    )

  return Math.round(
    total /
    actividades.value.length
  )
})

const ultimaActualizacion =
  computed(() => {
    const fechas =
      actividades.value
        .map(
          a =>
            a.actualizadoEn
        )
        .filter(Boolean)

    if (!fechas.length)
      return null

    const max =
      fechas.reduce(
        (acc, d) =>
          d > acc ? d : acc,
        fechas[0]
      )

    return max.toLocaleDateString(
      'es-BO',
      {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
      }
    )
  })

/* ============================================================
   INICIO
============================================================ */

onMounted(
  cargarActividades
)

watch(
  identificador,
  cargarActividades
)

watch(
  () => props.proyecto,
  nuevo => {
    if (nuevo) {
      proyectoInfo.value =
        nuevo
    }
  }
)

defineExpose({
  recargar:
    cargarActividades
})
</script>

<template>
  <div class="gantt-page">

    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="cronograma-header">

      <div class="header-title">

        <div class="header-icon">
          <i class="ti ti-calendar-event"></i>
        </div>

        <div>
          <h2>
            Cronograma del proyecto
          </h2>

          <p>
            Gestiona las actividades y visualiza el avance del proyecto.
          </p>
        </div>

      </div>

      <button
        type="button"
        class="btn-nueva-actividad"
        @click="abrirNuevaActividad"
      >
        <i class="ti ti-plus"></i>
        Nueva actividad
      </button>

    </div>

    <!-- =====================================================
         LOADING
    ====================================================== -->

    <div
      v-if="loading"
      class="gantt-state"
    >
      <div class="state-icon">
        <i class="ti ti-loader-2 spin"></i>
      </div>

      <div>
        <strong>
          Cargando cronograma
        </strong>

        <span>
          Estamos obteniendo las actividades del proyecto...
        </span>
      </div>
    </div>

    <!-- =====================================================
         ERROR
    ====================================================== -->

    <div
      v-else-if="error"
      class="gantt-state gantt-state-error"
    >
      <div class="state-icon error-icon">
        <i class="ti ti-alert-triangle"></i>
      </div>

      <div>
        <strong>
          No se pudo cargar el cronograma
        </strong>

        <span>
          {{ error }}
        </span>

        <button
          class="btn-reintentar"
          @click="cargarActividades"
        >
          <i class="ti ti-refresh"></i>
          Reintentar
        </button>
      </div>
    </div>

    <!-- =====================================================
         CONTENIDO
    ====================================================== -->

    <template v-else>

      <!-- ===================================================
           RESUMEN
      ==================================================== -->

      <section class="gantt-summary">

        <div class="summary-item">

          <div class="summary-icon">
            <i class="ti ti-list-check"></i>
          </div>

          <div>
            <strong>
              {{ actividades.length }}
            </strong>

            <span>
              Actividades
            </span>
          </div>

        </div>

        <div class="summary-item">

          <div class="summary-icon success">
            <i class="ti ti-circle-check"></i>
          </div>

          <div>
            <strong>
              {{ completadas }}
            </strong>

            <span>
              Concluidas
            </span>
          </div>

        </div>

        <div class="summary-item">

          <div class="summary-icon blue">
            <i class="ti ti-progress"></i>
          </div>

          <div>
            <strong>
              {{ avanceProgramado }}%
            </strong>

            <span>
              Programado
            </span>
          </div>

        </div>

        <div class="summary-item">

          <div class="summary-icon purple">
            <i class="ti ti-chart-line"></i>
          </div>

          <div>
            <strong>
              {{ avanceReal }}%
            </strong>

            <span>
              Avance real
            </span>
          </div>

        </div>

        <div class="summary-legend">

          <span>
            <i class="planned-dot"></i>
            Programado
          </span>

          <span>
            <i class="actual-dot"></i>
            Ejecutado
          </span>

        </div>

      </section>

      <!-- ===================================================
           GANTT
      ==================================================== -->

      <section class="gantt-card">

        <div class="gantt-card-header">

          <div>
            <h3>
              <i class="ti ti-chart-gantt"></i>
              Línea de tiempo
            </h3>

            <span>
              Planificación y avance de actividades
            </span>
          </div>

          <div class="timeline-info">
            <i class="ti ti-calendar"></i>

            {{ formatDate(timelineStart) }}
            —
            {{ formatDate(timelineEnd) }}
          </div>

        </div>

        <div class="gantt-scroll">

          <div
            class="gantt-table"
            :style="{
              '--n-cols': weeks.length
            }"
          >

            <!-- CABECERA IZQUIERDA -->

            <div class="gantt-side header-side">

              <span>N°</span>

              <span>
                Actividad
              </span>

              <span>
                Inicio
              </span>

              <span>
                Fin
              </span>

              <span>
                Estado
              </span>

            </div>

            <!-- MESES -->

            <div class="gantt-timeline gantt-months">

              <div
                v-for="m in months"
                :key="m.key"
                class="month"
              >
                {{ m.label }}
              </div>

            </div>

            <!-- SEMANAS IZQUIERDA -->

            <div class="gantt-side week-side">

              <span></span>
              <span></span>
              <span></span>
              <span></span>
              <span></span>

            </div>

            <!-- SEMANAS -->

            <div class="gantt-timeline week-row">

              <span
                v-for="week in weeks"
                :key="week"
              >
                S{{ (week % 4) + 1 }}
              </span>

              <div
                v-if="hoyOffset !== null"
                class="today-marker"
                :style="{
                  left: hoyOffset + '%'
                }"
                title="Hoy"
              >
                <span>HOY</span>
              </div>

            </div>

            <!-- =================================================
                 ACTIVIDADES
            ================================================== -->

            <template
              v-for="fila in actividades"
              :key="fila.id"
            >

              <!-- INFO -->

              <div class="gantt-side activity-info">

                <span class="number">
                  {{ fila.n }}
                </span>

                <span
                  class="activity-name"
                  :title="fila.nombre"
                >
                  {{ fila.nombre }}
                </span>

                <span class="date-cell">
                  {{ fila.inicio }}
                </span>

                <span class="date-cell">
                  {{ fila.fin }}
                </span>

                <span class="estado-cell">

                  <i
                    :class="
                      badgeClass(
                        fila.estado
                      )
                    "
                  >
                    {{ fila.estado }}
                  </i>

                  <!-- SOLO EDITAR -->

                  <button
                    type="button"
                    class="action-btn edit-btn"
                    title="Editar actividad"
                    @click="
                      abrirEditarActividad(
                        fila.raw
                      )
                    "
                  >
                    <i class="ti ti-pencil"></i>
                  </button>

                </span>

              </div>

              <!-- BARRA -->

              <div class="gantt-timeline activity-track">

                <span
                  v-for="week in weeks"
                  :key="week"
                  class="week-cell"
                ></span>

                <!-- PROGRAMADO -->

                <div
                  class="planned-bar"
                  :style="
                    ganttStyle(fila)
                  "
                  :title="
                    `${fila.nombre} · ${fila.inicio} - ${fila.fin} · Programado ${fila.p}%`
                  "
                >

                  <span
                    v-if="fila.p >= 8"
                  >
                    {{ fila.p }}%
                  </span>

                </div>

                <!-- REAL -->

                <div
                  v-if="fila.r > 0"
                  class="actual-bar"
                  :style="
                    realStyle(fila)
                  "
                  :title="
                    `Avance real: ${fila.r}%`
                  "
                >

                  <span
                    v-if="fila.r >= 12"
                  >
                    {{ fila.r }}%
                  </span>

                </div>

              </div>

            </template>

            <!-- =================================================
                 SIN ACTIVIDADES
            ================================================== -->

            <div
              v-if="!actividades.length"
              class="empty-gantt"
            >

              <div class="empty-icon">
                <i class="ti ti-calendar-off"></i>
              </div>

              <div>
                <strong>
                  No hay actividades todavía
                </strong>

                <small>
                  Haz clic en "Nueva actividad" para comenzar a construir el cronograma.
                </small>
              </div>

            </div>

          </div>

        </div>

        <!-- FOOTER -->

        <footer class="gantt-note">

          <div>
            <i class="ti ti-info-circle"></i>
          </div>

          <span>
            La barra clara representa el avance programado y la barra sólida representa el avance real.
            <template v-if="ultimaActualizacion">
              Última actualización:
              <strong>{{ ultimaActualizacion }}</strong>.
            </template>
          </span>

        </footer>

      </section>

    </template>

    <!-- =====================================================
         MODAL
    ====================================================== -->

    <div
      v-if="mostrarModal"
      class="modal-overlay"
      @click.self="cerrarModal"
    >

      <div class="modal-actividad">

        <!-- MODAL HEADER -->

        <div class="modal-header">

          <div class="modal-title">

            <div class="modal-icon">

              <i
                :class="
                  modoEdicion
                    ? 'ti ti-pencil'
                    : 'ti ti-plus'
                "
              ></i>

            </div>

            <div>

              <h3>
                {{
                  modoEdicion
                    ? 'Editar actividad'
                    : 'Nueva actividad'
                }}
              </h3>

              <p>
                {{
                  modoEdicion
                    ? 'Modifica los datos de la actividad.'
                    : 'Agrega una actividad al cronograma del proyecto.'
                }}
              </p>

            </div>

          </div>

          <button
            type="button"
            class="modal-close"
            @click="cerrarModal"
          >
            <i class="ti ti-x"></i>
          </button>

        </div>

        <!-- FORMULARIO -->

        <form
          class="form-actividad"
          @submit.prevent="guardarActividad"
        >

          <!-- ERROR -->

          <div
            v-if="errorFormulario"
            class="form-error"
          >

            <i class="ti ti-alert-circle"></i>

            <span>
              {{ errorFormulario }}
            </span>

          </div>

          <div class="form-grid">

            <!-- NUMERO -->

            <div class="form-group">

              <label>
                N° de actividad
              </label>

              <div class="input-wrap">

                <i class="ti ti-hash"></i>

                <input
                  v-model.number="
                    actividadFormulario.numero
                  "
                  type="number"
                  min="1"
                  required
                />

              </div>

            </div>

            <!-- ESTADO -->

            <div class="form-group">

              <label>
                Estado
              </label>

              <div class="input-wrap">

                <i class="ti ti-flag"></i>

                <select
                  v-model="
                    actividadFormulario.estado
                  "
                  required
                >

                  <option value="Pendiente">
                    Pendiente
                  </option>

                  <option value="En curso">
                    En curso
                  </option>

                  <option value="Concluida">
                    Concluida
                  </option>

                  <option value="Retrasada">
                    Retrasada
                  </option>

                </select>

              </div>

            </div>

            <!-- ACTIVIDAD -->

            <div class="form-group form-full">

              <label>
                Actividad
              </label>

              <div class="input-wrap">

                <i class="ti ti-clipboard-text"></i>

                <input
                  v-model="
                    actividadFormulario.actividad
                  "
                  type="text"
                  maxlength="255"
                  placeholder="Ej. Excavación y preparación del terreno"
                  required
                />

              </div>

            </div>

            <!-- INICIO -->

            <div class="form-group">

              <label>
                Fecha de inicio
              </label>

              <div class="input-wrap">

                <i class="ti ti-calendar"></i>

                <input
                  v-model="
                    actividadFormulario.fecha_inicio
                  "
                  type="date"
                  required
                />

              </div>

            </div>

            <!-- FIN -->

            <div class="form-group">

              <label>
                Fecha de finalización
              </label>

              <div class="input-wrap">

                <i class="ti ti-calendar-check"></i>

                <input
                  v-model="
                    actividadFormulario.fecha_fin
                  "
                  type="date"
                  :min="
                    actividadFormulario.fecha_inicio
                  "
                  required
                />

              </div>

            </div>

            <!-- PROGRAMADO -->

            <div class="form-group">

              <label>
                Cumplimiento programado
              </label>

              <div class="input-wrap percentage-input">

                <i class="ti ti-chart-bar"></i>

                <input
                  v-model.number="
                    actividadFormulario
                      .porcentaje_cumplimiento_programado
                  "
                  type="number"
                  min="0"
                  max="100"
                  step="0.01"
                />

                <span>%</span>

              </div>

            </div>

            <!-- REAL -->

            <div class="form-group">

              <label>
                Cumplimiento real
              </label>

              <div class="input-wrap percentage-input">

                <i class="ti ti-chart-line"></i>

                <input
                  v-model.number="
                    actividadFormulario
                      .porcentaje_cumplimiento_real
                  "
                  type="number"
                  min="0"
                  max="100"
                  step="0.01"
                />

                <span>%</span>

              </div>

            </div>

          </div>

          <!-- ACCIONES -->

          <div class="modal-actions">

            <button
              type="button"
              class="btn-cancelar"
              @click="cerrarModal"
              :disabled="guardando"
            >
              Cancelar
            </button>

            <button
              type="submit"
              class="btn-guardar"
              :disabled="guardando"
            >

              <i
                v-if="guardando"
                class="ti ti-loader-2 spin"
              ></i>

              <i
                v-else
                :class="
                  modoEdicion
                    ? 'ti ti-device-floppy'
                    : 'ti ti-plus'
                "
              ></i>

              {{
                guardando
                  ? 'Guardando...'
                  : (
                    modoEdicion
                      ? 'Actualizar actividad'
                      : 'Guardar actividad'
                  )
              }}

            </button>

          </div>

        </form>

      </div>

    </div>

  </div>
</template>

<style scoped>

/* ============================================================
   BASE
============================================================ */

.gantt-page {
  width: 100%;
  max-width: 1680px;
  margin: 0 auto;
  color: #d4e4f0;
}

/* ============================================================
   HEADER
============================================================ */

.cronograma-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  margin-bottom: 18px;
}

.header-title {
  display: flex;
  align-items: center;
  gap: 13px;
}

.header-icon {
  width: 42px;
  height: 42px;
  display: flex;
  justify-content: center;
  align-items: center;
  border: 1px solid rgba(0,201,167,.25);
  border-radius: 10px;
  background:
    linear-gradient(
      145deg,
      rgba(0,201,167,.18),
      rgba(0,201,167,.04)
    );
  color: #00c9a7;
  font-size: 21px;
  box-shadow:
    0 8px 25px
    rgba(0,0,0,.16);
}

.cronograma-header h2 {
  margin: 0;
  color: #e4f0f7;
  font-size: 1.08rem;
  font-weight: 750;
  letter-spacing: -.01em;
}

.cronograma-header p {
  margin: 5px 0 0;
  color: #7894aa;
  font-size: .74rem;
}

/* ============================================================
   NUEVA ACTIVIDAD
============================================================ */

.btn-nueva-actividad {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  min-height: 38px;
  border: 1px solid rgba(0,229,192,.45);
  border-radius: 8px;
  padding: 0 15px;
  background:
    linear-gradient(
      135deg,
      #00d3ae,
      #00b99b
    );
  color: #04151b;
  font-size: .74rem;
  font-weight: 800;
  cursor: pointer;
  transition:
    transform .2s ease,
    box-shadow .2s ease,
    filter .2s ease;
  white-space: nowrap;
  box-shadow:
    0 5px 18px
    rgba(0,201,167,.15);
}

.btn-nueva-actividad:hover {
  transform: translateY(-1px);
  filter: brightness(1.06);
  box-shadow:
    0 8px 25px
    rgba(0,201,167,.23);
}

.btn-nueva-actividad i {
  font-size: 17px;
}

/* ============================================================
   SUMMARY
============================================================ */

.gantt-summary {
  display: flex;
  align-items: stretch;
  min-height: 72px;
  margin-bottom: 15px;
  overflow: hidden;
  border: 1px solid #1b354a;
  border-radius: 11px;
  background:
    linear-gradient(
      135deg,
      #0d2233,
      #0b1d2d
    );
  box-shadow:
    0 8px 30px
    rgba(0,0,0,.10);
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 145px;
  padding: 11px 17px;
  border-right: 1px solid #19354a;
}

.summary-icon {
  width: 34px;
  height: 34px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 8px;
  background: rgba(0,201,167,.10);
  color: #00c9a7;
  font-size: 17px;
}

.summary-icon.success {
  background: rgba(0,201,167,.10);
  color: #00d0aa;
}

.summary-icon.blue {
  background: rgba(77,179,240,.10);
  color: #4db3f0;
}

.summary-icon.purple {
  background: rgba(164,130,255,.10);
  color: #a482ff;
}

.summary-item strong {
  display: block;
  color: #e0edf5;
  font-size: .95rem;
  line-height: 1;
}

.summary-item span {
  display: block;
  margin-top: 4px;
  color: #7793a8;
  font-size: .65rem;
}

.summary-legend {
  display: flex;
  align-items: center;
  gap: 17px;
  margin-left: auto;
  padding: 0 20px;
  color: #829caf;
  font-size: .67rem;
  white-space: nowrap;
}

.summary-legend span {
  display: flex;
  align-items: center;
  gap: 6px;
}

.summary-legend i {
  width: 17px;
  height: 7px;
  display: block;
  border-radius: 3px;
}

.planned-dot {
  background: rgba(0,201,167,.32);
  border: 1px solid rgba(0,229,192,.38);
}

.actual-dot {
  background: #00c9a7;
}

/* ============================================================
   GANTT CARD
============================================================ */

.gantt-card {
  overflow: hidden;
  border: 1px solid #1d3a51;
  border-radius: 12px;
  background: #0b1c2b;
  box-shadow:
    0 12px 35px
    rgba(0,0,0,.12);
}

.gantt-card-header {
  min-height: 62px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 11px 16px;
  border-bottom: 1px solid #1b3549;
  background:
    linear-gradient(
      135deg,
      #0e2436,
      #0b1d2d
    );
}

.gantt-card-header h3 {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 7px;
  color: #d8e8f1;
  font-size: .82rem;
}

.gantt-card-header h3 i {
  color: #00c9a7;
  font-size: 17px;
}

.gantt-card-header span {
  display: block;
  margin-top: 4px;
  color: #718ca1;
  font-size: .64rem;
}

.timeline-info {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 9px;
  border: 1px solid #24445a;
  border-radius: 6px;
  background: rgba(7,22,34,.65);
  color: #91aabd;
  font-size: .63rem;
}

.timeline-info i {
  color: #00c9a7;
}

/* ============================================================
   SCROLL
============================================================ */

.gantt-scroll {
  width: 100%;
  overflow-x: auto;
  overflow-y: hidden;
  scrollbar-width: thin;
  scrollbar-color: #31536b #091722;
}

.gantt-scroll::-webkit-scrollbar {
  height: 8px;
}

.gantt-scroll::-webkit-scrollbar-track {
  background: #091722;
}

.gantt-scroll::-webkit-scrollbar-thumb {
  border-radius: 10px;
  background: #31536b;
}

/* ============================================================
   GANTT TABLE
============================================================ */

.gantt-table {
  min-width: 1320px;
  display: grid;
  grid-template-columns:
    525px
    minmax(795px, 1fr);
  grid-template-rows:
    35px
    28px;
}

/* ============================================================
   SIDE
============================================================ */

.gantt-side {
  display: grid;
  grid-template-columns:
    38px
    1.7fr
    76px
    76px
    112px;
  align-items: center;
}

.header-side {
  background: #081520;
  border-right: 1px solid #244158;
  color: #8da6b8;
  font-size: .62rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .05em;
}

.header-side span {
  padding: 0 8px;
}

/* ============================================================
   MONTHS
============================================================ */

.gantt-timeline {
  position: relative;
  display: grid;
  grid-template-columns:
    repeat(
      var(--n-cols),
      minmax(16px, 1fr)
    );
}

.gantt-months {
  overflow: hidden;
  background:
    linear-gradient(
      135deg,
      #0879b9,
      #086da7
    );
  color: #fff;
}

.month {
  grid-column: span 4;
  display: flex;
  align-items: center;
  justify-content: center;
  border-right: 1px solid rgba(255,255,255,.19);
  font-size: .61rem;
  font-weight: 800;
  letter-spacing: .04em;
}

/* ============================================================
   WEEKS
============================================================ */

.week-side {
  background: #091926;
  border-top: 1px solid #1a3448;
  border-right: 1px solid #244158;
}

.week-row {
  background: #0a1927;
  border-top: 1px solid #1a3448;
}

.week-row span {
  display: flex;
  justify-content: center;
  align-items: center;
  border-right: 1px solid #1b354a;
  color: #6f8b9e;
  font-size: .56rem;
  font-weight: 700;
}

.week-row span:nth-child(4n) {
  border-right-color: #35556a;
}

/* ============================================================
   TODAY
============================================================ */

.today-marker {
  position: absolute;
  top: 0;
  bottom: -3000px;
  width: 2px;
  z-index: 20;
  background: #ff5577;
  pointer-events: none;
  box-shadow:
    0 0 7px
    rgba(255,85,119,.45);
}

.today-marker::before {
  content: '';
  position: absolute;
  top: -1px;
  left: -4px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #ff5577;
  box-shadow:
    0 0 0 3px
    rgba(255,85,119,.12);
}

.today-marker span {
  position: absolute;
  top: 11px;
  left: 5px;
  padding: 2px 4px;
  border-radius: 3px;
  background: #ff5577;
  color: white;
  font-size: .46rem;
  font-weight: 800;
}

/* ============================================================
   ACTIVITY INFO
============================================================ */

.activity-info {
  min-height: 50px;
  border-top: 1px solid #152d41;
  border-right: 1px solid #244158;
  background: #0b1d2c;
  color: #829bae;
  font-size: .67rem;
  transition: background .15s ease;
}

.activity-info:hover {
  background: #0e2435;
}

.activity-info span {
  padding: 0 8px;
}

.number {
  color: #00c9a7;
  font-weight: 800;
  text-align: center;
}

.activity-name {
  color: #c9dce8;
  font-size: .72rem;
  font-weight: 550;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.date-cell {
  color: #7893a7;
  font-size: .61rem;
}

.estado-cell {
  display: flex;
  align-items: center;
  gap: 5px;
  min-width: 0;
}

/* ============================================================
   EDITAR
============================================================ */

.action-btn {
  width: 25px;
  height: 25px;
  flex: 0 0 25px;
  display: inline-flex;
  justify-content: center;
  align-items: center;
  border: 1px solid transparent;
  border-radius: 6px;
  cursor: pointer;
  font-size: 13px;
  transition:
    background .15s ease,
    border-color .15s ease,
    transform .15s ease;
}

.edit-btn {
  margin-left: 2px;
  background: rgba(77,179,240,.08);
  border-color: rgba(77,179,240,.16);
  color: #55b8ef;
}

.edit-btn:hover {
  background: rgba(77,179,240,.18);
  border-color: rgba(77,179,240,.3);
  transform: translateY(-1px);
}

/* ============================================================
   TRACK
============================================================ */

.activity-track {
  min-height: 50px;
  border-top: 1px solid #152d41;
  background: #0a1b29;
  isolation: isolate;
}

.activity-track::after {
  content: '';
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  background:
    linear-gradient(
      90deg,
      transparent,
      rgba(255,255,255,.012),
      transparent
    );
}

.week-cell {
  border-right: 1px solid #19374d;
  opacity: .85;
}

.week-cell:nth-child(4n) {
  border-right-color: #35576b;
}

/* ============================================================
   BARRAS
============================================================ */

.planned-bar,
.actual-bar {
  position: absolute;
  left: 0;
  border-radius: 4px;
  transition:
    filter .2s ease,
    transform .2s ease;
}

.planned-bar {
  top: 14px;
  height: 22px;
  min-width: 5px;
  z-index: 2;
  background:
    linear-gradient(
      90deg,
      rgba(0,201,167,.20),
      rgba(0,201,167,.32)
    );
  border: 1px solid rgba(0,229,192,.38);
  box-shadow:
    inset 0 1px 0
    rgba(255,255,255,.04);
}

.planned-bar:hover {
  filter: brightness(1.18);
}

.planned-bar span {
  position: absolute;
  top: 50%;
  right: 5px;
  transform: translateY(-50%);
  color: #bff8ed;
  font-size: .55rem;
  font-weight: 800;
  white-space: nowrap;
}

.actual-bar {
  top: 20px;
  height: 10px;
  min-width: 3px;
  z-index: 4;
  overflow: hidden;
  background:
    linear-gradient(
      90deg,
      #00ae94,
      #00d0ae
    );
  border: 1px solid rgba(0,229,192,.5);
  box-shadow:
    0 1px 8px
    rgba(0,201,167,.28);
}

.actual-bar span {
  position: absolute;
  left: 4px;
  top: 50%;
  transform: translateY(-50%);
  color: #e8fffa;
  font-size: .48rem;
  font-weight: 800;
  line-height: 1;
  white-space: nowrap;
}

/* ============================================================
   BADGES
============================================================ */

.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 3px 8px;
  border-radius: 20px;
  font-size: .57rem;
  font-weight: 800;
  font-style: normal;
  line-height: 1.1;
  white-space: nowrap;
}

.badge-success {
  background: rgba(0,201,167,.13);
  border: 1px solid rgba(0,201,167,.18);
  color: #00d2ad;
}

.badge-info {
  background: rgba(77,179,240,.12);
  border: 1px solid rgba(77,179,240,.18);
  color: #55b8ef;
}

.badge-danger {
  background: rgba(242,139,130,.12);
  border: 1px solid rgba(242,139,130,.18);
  color: #f28b82;
}

.badge-muted {
  background: rgba(142,169,191,.10);
  border: 1px solid rgba(142,169,191,.13);
  color: #91a9ba;
}

/* ============================================================
   EMPTY
============================================================ */

.empty-gantt {
  grid-column: 1 / -1;
  min-height: 190px;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 13px;
  border-top: 1px solid #152d41;
  background:
    radial-gradient(
      circle at center,
      rgba(0,201,167,.045),
      transparent 45%
    );
  color: #8ea9bb;
}

.empty-icon {
  width: 45px;
  height: 45px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 10px;
  background: rgba(0,201,167,.08);
  color: #00c9a7;
  font-size: 23px;
}

.empty-gantt strong {
  display: block;
  color: #c9dce8;
  font-size: .76rem;
}

.empty-gantt small {
  display: block;
  margin-top: 4px;
  color: #718da2;
  font-size: .65rem;
}

/* ============================================================
   FOOTER
============================================================ */

.gantt-note {
  display: flex;
  align-items: center;
  gap: 8px;
  min-height: 42px;
  padding: 7px 14px;
  border-top: 1px solid #19354b;
  background: #0a1a28;
  color: #728da2;
  font-size: .63rem;
}

.gantt-note > div {
  width: 24px;
  height: 24px;
  display: flex;
  justify-content: center;
  align-items: center;
  flex: 0 0 24px;
  border-radius: 6px;
  background: rgba(0,201,167,.08);
}

.gantt-note i {
  color: #00c9a7;
  font-size: 14px;
}

.gantt-note strong {
  color: #a3baca;
  font-weight: 700;
}

/* ============================================================
   ESTADOS
============================================================ */

.gantt-state {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  min-height: 120px;
  padding: 25px;
  border: 1px solid #1e3a52;
  border-radius: 11px;
  background: #0d1f30;
  color: #8ea9bf;
}

.state-icon {
  width: 40px;
  height: 40px;
  display: flex;
  justify-content: center;
  align-items: center;
  flex: 0 0 40px;
  border-radius: 10px;
  background: rgba(0,201,167,.08);
  color: #00c9a7;
  font-size: 19px;
}

.gantt-state strong {
  display: block;
  color: #cbdde8;
  font-size: .78rem;
}

.gantt-state span {
  display: block;
  margin-top: 3px;
  color: #718ca1;
  font-size: .66rem;
}

.gantt-state-error {
  border-color: rgba(242,139,130,.18);
}

.error-icon {
  background: rgba(242,139,130,.08);
  color: #f28b82;
}

.btn-reintentar {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  margin-top: 8px;
  padding: 6px 10px;
  border: 1px solid #31516a;
  border-radius: 6px;
  background: transparent;
  color: #a9c0d0;
  font-size: .65rem;
  cursor: pointer;
}

.btn-reintentar:hover {
  background: rgba(255,255,255,.04);
}

/* ============================================================
   MODAL
============================================================ */

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
  background: rgba(1,8,14,.78);
  backdrop-filter: blur(5px);
}

.modal-actividad {
  width: min(680px, 100%);
  max-height: 92vh;
  overflow-y: auto;
  border: 1px solid #29485e;
  border-radius: 13px;
  background:
    linear-gradient(
      145deg,
      #0d2233,
      #0b1c2b
    );
  box-shadow:
    0 25px 90px
    rgba(0,0,0,.58);
  scrollbar-width: thin;
  scrollbar-color: #31536b transparent;
}

/* ============================================================
   MODAL HEADER
============================================================ */

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 15px;
  padding: 19px 20px;
  border-bottom: 1px solid #1b394e;
  background:
    linear-gradient(
      135deg,
      rgba(0,201,167,.045),
      transparent
    );
}

.modal-title {
  display: flex;
  align-items: center;
  gap: 11px;
}

.modal-icon {
  width: 38px;
  height: 38px;
  display: flex;
  justify-content: center;
  align-items: center;
  border: 1px solid rgba(0,201,167,.2);
  border-radius: 9px;
  background: rgba(0,201,167,.09);
  color: #00c9a7;
  font-size: 18px;
}

.modal-header h3 {
  margin: 0;
  color: #dceaf2;
  font-size: .92rem;
}

.modal-header p {
  margin: 4px 0 0;
  color: #718da2;
  font-size: .65rem;
}

.modal-close {
  width: 30px;
  height: 30px;
  display: flex;
  justify-content: center;
  align-items: center;
  border: 1px solid transparent;
  border-radius: 6px;
  background: transparent;
  color: #7893a7;
  font-size: 18px;
  cursor: pointer;
  transition: .15s;
}

.modal-close:hover {
  border-color: #29465c;
  background: rgba(255,255,255,.04);
  color: #e0edf5;
}

/* ============================================================
   FORM
============================================================ */

.form-actividad {
  padding: 20px;
}

.form-error {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-bottom: 16px;
  padding: 10px 12px;
  border: 1px solid rgba(242,139,130,.25);
  border-radius: 7px;
  background: rgba(242,139,130,.07);
  color: #f28b82;
  font-size: .69rem;
  line-height: 1.4;
}

.form-error i {
  margin-top: 1px;
  font-size: 15px;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-full {
  grid-column: 1 / -1;
}

.form-group label {
  color: #9cb5c6;
  font-size: .65rem;
  font-weight: 750;
}

.input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.input-wrap > i {
  position: absolute;
  left: 10px;
  z-index: 2;
  color: #5f7c91;
  font-size: 15px;
  pointer-events: none;
}

.form-group input,
.form-group select {
  width: 100%;
  box-sizing: border-box;
  min-height: 38px;
  padding: 9px 10px 9px 32px;
  border: 1px solid #29465c;
  border-radius: 7px;
  outline: none;
  background: #081a29;
  color: #d4e4f0;
  font-size: .7rem;
  transition:
    border-color .15s ease,
    box-shadow .15s ease,
    background .15s ease;
}

.form-group select {
  appearance: auto;
}

.form-group input::placeholder {
  color: #536e82;
}

.form-group input:focus,
.form-group select:focus {
  border-color: rgba(0,201,167,.65);
  background: #091d2c;
  box-shadow:
    0 0 0 3px
    rgba(0,201,167,.07);
}

.percentage-input input {
  padding-right: 30px;
}

.percentage-input > span {
  position: absolute;
  right: 11px;
  color: #6e8a9d;
  font-size: .67rem;
  pointer-events: none;
}

/* ============================================================
   MODAL ACTIONS
============================================================ */

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 9px;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid #19374c;
}

.btn-cancelar,
.btn-guardar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  min-height: 37px;
  padding: 0 14px;
  border-radius: 7px;
  font-size: .68rem;
  font-weight: 750;
  cursor: pointer;
  transition: .15s;
}

.btn-cancelar {
  border: 1px solid #31516a;
  background: transparent;
  color: #91aabd;
}

.btn-cancelar:hover:not(:disabled) {
  background: rgba(255,255,255,.04);
  border-color: #45657b;
}

.btn-guardar {
  border: 1px solid rgba(0,229,192,.4);
  background:
    linear-gradient(
      135deg,
      #00d0aa,
      #00b99b
    );
  color: #04151b;
  box-shadow:
    0 5px 15px
    rgba(0,201,167,.12);
}

.btn-guardar:hover:not(:disabled) {
  filter: brightness(1.07);
  transform: translateY(-1px);
}

.btn-cancelar:disabled,
.btn-guardar:disabled {
  opacity: .55;
  cursor: not-allowed;
}

/* ============================================================
   SPINNER
============================================================ */

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* ============================================================
   RESPONSIVE
============================================================ */

@media (max-width: 950px) {

  .gantt-summary {
    flex-wrap: wrap;
  }

  .summary-item {
    flex: 1 1 160px;
    min-width: 0;
  }

  .summary-legend {
    width: 100%;
    min-height: 42px;
    margin-left: 0;
    padding: 0 17px;
    border-top: 1px solid #19354a;
  }
}

@media (max-width: 800px) {

  .cronograma-header {
    align-items: stretch;
    flex-direction: column;
  }

  .header-title {
    align-items: flex-start;
  }

  .btn-nueva-actividad {
    width: 100%;
  }

  .gantt-card-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .timeline-info {
    width: fit-content;
  }

  .gantt-summary {
    border-radius: 9px;
  }

  .summary-item {
    flex: 1 1 50%;
    border-bottom: 1px solid #19354a;
  }

  .summary-legend {
    padding-bottom: 10px;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-full {
    grid-column: auto;
  }

  .modal-overlay {
    align-items: flex-end;
    padding: 8px;
  }

  .modal-actividad {
    max-height: 94vh;
    border-radius: 12px 12px 8px 8px;
  }
}

@media (max-width: 520px) {

  .cronograma-header h2 {
    font-size: .95rem;
  }

  .cronograma-header p {
    font-size: .67rem;
  }

  .header-icon {
    width: 37px;
    height: 37px;
    flex-basis: 37px;
  }

  .summary-item {
    min-width: 50%;
    padding: 10px 12px;
  }

  .summary-icon {
    width: 30px;
    height: 30px;
    font-size: 15px;
  }

  .summary-item strong {
    font-size: .82rem;
  }

  .summary-item span {
    font-size: .59rem;
  }

  .summary-legend {
    gap: 12px;
    font-size: .6rem;
  }

  .modal-header {
    padding: 15px;
  }

  .form-actividad {
    padding: 15px;
  }

  .modal-actions {
    flex-direction: column-reverse;
  }

  .btn-cancelar,
  .btn-guardar {
    width: 100%;
  }
}

</style>