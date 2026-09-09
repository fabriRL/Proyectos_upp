<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/lib/axios'

const route = useRoute()

const proyecto = ref(null)
const dashboard = ref(null)
const cargando = ref(true)
const error = ref('')

async function cargarDatos() {
  cargando.value = true
  error.value = ''
  try {
    const codigo = route.params.codigo
    
    const [resProyecto, resDashboard] = await Promise.all([
      axios.get(`/api/proyectos/${codigo}`),
      axios.get(`/api/proyectos/${codigo}/resumen-general`),
    ])


    proyecto.value = resProyecto.data
    dashboard.value = resDashboard.data
  } catch (e) {
    console.error('Error al cargar el proyecto:', e)
    if (e.response?.status === 404) {
      error.value = 'No se encontró un proyecto con ese código.'
    } else {
      error.value = 'No se pudo cargar la información del proyecto.'
    }
  } finally {
    cargando.value = false
  }
}

onMounted(cargarDatos)

/* ---------------------------------------------------------
 * Helpers de formato
 * --------------------------------------------------------- */
function formatearFecha(fecha) {
  if (!fecha) return '—'
  const d = new Date(fecha)
  if (isNaN(d)) return '—'
  return d.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatearNumero(n) {
  if (n === null || n === undefined) return '—'
  return Number(n).toLocaleString('es-BO')
}

function formatearMonto(monto) {
  if (monto === null || monto === undefined || monto === '' || Number(monto) === 0) {
    return '—'
  }
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number(monto))
}

// Los beneficiarios llegan como filas sueltas [{categoria, tipo, cantidad}, ...]
// (así los guarda ProyectoController@store), hay que buscarlos por categoria+tipo.
function buscarBeneficiario(categoria, tipo) {
  if (!proyecto.value?.beneficiarios) return null
  const fila = proyecto.value.beneficiarios.find(
    (b) => b.categoria === categoria && b.tipo === tipo
  )
  return fila ? fila.cantidad : null
}

/* ---------------------------------------------------------
 * Días transcurridos (no viene del backend, se calcula aquí
 * a partir de fecha_inicio_contractual)
 * --------------------------------------------------------- */
const diasTranscurridos = computed(() => {
  if (!proyecto.value?.fecha_inicio_contractual) return null
  const inicio = new Date(proyecto.value.fecha_inicio_contractual)
  if (isNaN(inicio)) return null
  const hoy = new Date()
  return Math.max(0, Math.floor((hoy - inicio) / 86400000))
})

/* ---------------------------------------------------------
 * Semáforo
 * NOTA: DashboardController todavía NO calcula esto (no existe
 * un campo "semaforo" en la respuesta). Esta es una regla
 * PROPUESTA en el frontend: compara avance físico real contra
 * el avance esperado según el % de tiempo transcurrido del
 * plazo vigente. Ajusta los umbrales (-5 / -15) o mueve esta
 * lógica al DashboardController cuando definan la regla oficial.
 * --------------------------------------------------------- */
const semaforo = computed(() => {
  if (!dashboard.value || !proyecto.value) return { texto: '—', color: '#8ea9bf' }

  const stats = dashboard.value.stats
  const plazoVigente = proyecto.value.plazo_contractual_actual_dias

  if (proyecto.value.fecha_conclusion_actual && stats.dias_restantes <= 0) {
    return { texto: 'VENCIDO', color: '#f87171' }
  }

  if (!plazoVigente || diasTranscurridos.value === null) {
    return { texto: 'SIN DATOS', color: '#8ea9bf' }
  }

  const avanceEsperado = Math.min(100, (diasTranscurridos.value / plazoVigente) * 100)
  const brecha = stats.avance_fisico - avanceEsperado

  if (brecha <= -15) return { texto: 'CRÍTICO', color: '#f87171' }
  if (brecha <= -5) return { texto: 'EN RIESGO', color: '#f59e0b' }
  return { texto: 'EN TIEMPO', color: '#00c9a7' }
})

/* ---------------------------------------------------------
 * Tarjetas superiores
 * --------------------------------------------------------- */
const tarjetas = computed(() => {
  const stats = dashboard.value?.stats
  return [
    ['Avance físico', stats ? `${stats.avance_fisico}%` : '—', '#00c9a7'],
    ['Avance financiero', stats ? `${stats.avance_financiero}%` : '—', '#00c9a7'],
    ['Días restantes', stats ? formatearNumero(stats.dias_restantes) : '—', '#f59e0b'],
    ['Semáforo', semaforo.value.texto, semaforo.value.color],
  ]
})

/* ---------------------------------------------------------
 * Identificación del proyecto (ya SIN los campos de ubicación,
 * ahora tienen su propia sección más abajo)
 * --------------------------------------------------------- */
const datosList = computed(() => {
  const p = proyecto.value
  if (!p) return []

  return [
    ['Código del proyecto', p.codigo, true],
    ['Número SISIN Web', p.numero_sisin_web || '—', true],
    ['Nombre del proyecto', p.nombre, false],
    ['Entidad ejecutora', p.entidad_ejecutora || '—', false],
    ['Fiscal general', p.fiscal_general || '—', false],
    ['Fuente de financiamiento', p.fuente_financiamiento || '—', false],
    ['Norma del financiador', p.norma_financiador || '—', false],
    ['Monto del Decreto (Bs)', formatearMonto(p.monto_decreto), true],
  ]
})

/* ---------------------------------------------------------
 * Beneficiarios
 * --------------------------------------------------------- */
const beneList = computed(() => {
  if (!proyecto.value) return []
  return [
    ['Familias productoras', formatearNumero(buscarBeneficiario('Beneficiarios', 'Familias productoras'))],
    ['Total beneficiarios', formatearNumero(buscarBeneficiario('Beneficiarios', 'Total beneficiarios'))],
    ['Empleos directos (construcción)', formatearNumero(buscarBeneficiario('Empleo - Construcción', 'Directos'))],
    ['Empleos indirectos (construcción)', formatearNumero(buscarBeneficiario('Empleo - Construcción', 'Indirectos'))],
    ['Empleos directos (operación)', formatearNumero(buscarBeneficiario('Empleo - Operación', 'Directos'))],
    ['Empleos indirectos (operación)', formatearNumero(buscarBeneficiario('Empleo - Operación', 'Indirectos'))],
  ]
})

/* ---------------------------------------------------------
 * Plazos del proyecto
 * --------------------------------------------------------- */
const plazosList = computed(() => {
  const p = proyecto.value
  const stats = dashboard.value?.stats
  if (!p) return []

  return [
    ['Inicio contractual', formatearFecha(p.fecha_inicio_contractual)],
    ['Conclusión inicial', formatearFecha(p.fecha_conclusion_inicial_contractual)],
    ['Conclusión vigente', formatearFecha(p.fecha_conclusion_actual)],
    ['Plazo vigente (días)', formatearNumero(p.plazo_contractual_actual_dias)],
    ['Días transcurridos', formatearNumero(diasTranscurridos.value)],
    ['Días restantes', stats ? formatearNumero(stats.dias_restantes) : '—'],
  ]
})

/* ---------------------------------------------------------
 * Ubicaciones (una o más — proyecto.ubicaciones es un array,
 * gracias al load(['ubicaciones', ...]) del backend)
 * --------------------------------------------------------- */
const ubicacionesList = computed(() => proyecto.value?.ubicaciones ?? [])

/* ---------------------------------------------------------
 * Componentes y Productos
 * (proyecto.componentes viene con .productos ya cargados,
 * gracias al load(['componentes.productos']) del backend)
 * --------------------------------------------------------- */
const componentesList = computed(() => proyecto.value?.componentes ?? [])

function formatearCantidadProducto(producto) {
  if (producto.cantidad === null || producto.cantidad === undefined) return ''
  const cantidad = Number(producto.cantidad).toLocaleString('es-BO')
  return producto.unidad ? `${cantidad} ${producto.unidad}` : cantidad
}
</script>

<template>
  <div class="p-5 details-page">

    <div v-if="cargando" class="rounded-xl p-4 text-sm" style="background-color:#0d1f30; border:1px solid #1e3a52; color:#8ea9bf;">
      Cargando información del proyecto...
    </div>

    <div v-else-if="error" class="rounded-xl p-4 text-sm" style="background-color:#2a1414; border:1px solid #5c2323; color:#fca5a5;">
      {{ error }}
    </div>

    <template v-else>
      <div class="grid grid-cols-4 gap-3 mb-5">
        <div v-for="[l, v, color] in tarjetas"
             :key="l" class="rounded-xl p-3" style="background-color:#0d1f30; border:1px solid #1e3a52;">
          <div class="field-label">{{ l }}</div>
          <div class="font-bold text-lg mt-1" :style="{ color }">{{ v }}</div>
        </div>
      </div>

      <div class="details-layout">
        <div class="rounded-xl overflow-hidden" style="background-color:#0d1f30; border:1px solid #1e3a52;">
          <div class="px-5 py-3" style="border-bottom:1px solid #19354d;"><div class="section-title" style="margin-bottom:0;">Identificación del proyecto</div></div>
          <div class="px-5 py-2">
            <div v-for="[l, v, mono] in datosList" :key="l" class="flex gap-3 py-2.5" style="border-bottom:1px solid #152a3e;">
              <span class="text-xs shrink-0 w-44" style="color:#8ea9bf;">{{ l }}</span>
              <span :class="mono ? 'font-mono text-xs' : 'text-sm'" style="color:#d4e4f0;">{{ v }}</span>
            </div>
          </div>
        </div>

        <div class="right-column">
          <div class="rounded-xl overflow-hidden" style="background-color:#0d1f30; border:1px solid #1e3a52;">
            <div class="px-4 py-3" style="border-bottom:1px solid #19354d;"><div class="section-title" style="margin-bottom:0;">Beneficiarios</div></div>
            <div class="px-4 py-2"><div v-for="[l, v] in beneList" :key="l" class="flex justify-between py-2" style="border-bottom:1px solid #152a3e;"><span class="text-xs" style="color:#8ea9bf;">{{ l }}</span><span class="font-semibold text-xs" style="color:#d4e4f0;">{{ v }}</span></div></div>
          </div>
          <div class="rounded-xl overflow-hidden" style="background-color:#0d1f30; border:1px solid #1e3a52;">
            <div class="px-4 py-3" style="border-bottom:1px solid #19354d;"><div class="section-title" style="margin-bottom:0;">Plazos del proyecto</div></div>
            <div class="px-4 py-2"><div v-for="[l, v] in plazosList" :key="l" class="flex justify-between py-2" style="border-bottom:1px solid #152a3e;"><span class="text-xs" style="color:#8ea9bf;">{{ l }}</span><span class="font-mono text-xs" style="color:#d4e4f0;">{{ v }}</span></div></div>
          </div>
        </div>
      </div>

      <!-- UBICACIONES -->
      <div v-if="ubicacionesList.length" class="rounded-xl overflow-hidden mt-4" style="background-color:#0d1f30; border:1px solid #1e3a52;">
        <div class="px-5 py-3" style="border-bottom:1px solid #19354d;">
          <div class="section-title" style="margin-bottom:0;">Ubicación geográfica</div>
          <p class="text-xs mt-1" style="color:#8ea9bf;">
            {{ ubicacionesList.length }} {{ ubicacionesList.length === 1 ? 'ubicación registrada' : 'ubicaciones registradas' }}.
          </p>
        </div>

        <div class="ubicaciones-grid px-5 py-4">
          <div
            v-for="(u, idx) in ubicacionesList"
            :key="u.id_ubicacion_proyecto"
            class="rounded-xl overflow-hidden"
            style="background-color:#0a1624; border:1px solid #1e3a52;"
          >
            <div class="px-4 py-3" style="border-bottom:1px solid #19354d; font-weight:700; font-size:.85rem; color:#f2fbff;">
              Ubicación {{ idx + 1 }}
            </div>

            <div class="px-4 py-3">
              <div class="flex justify-between py-1.5" style="border-bottom:1px solid #152a3e; font-size:.78rem;">
                <span style="color:#8ea9bf;">Departamento</span>
                <span style="color:#c8dae7;">{{ u.departamento || '—' }}</span>
              </div>
              <div class="flex justify-between py-1.5" style="border-bottom:1px solid #152a3e; font-size:.78rem;">
                <span style="color:#8ea9bf;">Provincia</span>
                <span style="color:#c8dae7;">{{ u.provincia || '—' }}</span>
              </div>
              <div class="flex justify-between py-1.5" style="border-bottom:1px solid #152a3e; font-size:.78rem;">
                <span style="color:#8ea9bf;">Municipio</span>
                <span style="color:#c8dae7;">{{ u.municipio || '—' }}</span>
              </div>
              <div class="flex justify-between py-1.5" style="border-bottom:1px solid #152a3e; font-size:.78rem;">
                <span style="color:#8ea9bf;">Comunidad / Localidad</span>
                <span style="color:#c8dae7;">{{ u.comunidad_localidad || '—' }}</span>
              </div>
              <div class="flex justify-between py-1.5" style="border-bottom:1px solid #152a3e; font-size:.78rem;">
                <span style="color:#8ea9bf;">Coordenada Norte</span>
                <span class="font-mono" style="color:#c8dae7;">{{ u.coordenada_norte || '—' }}</span>
              </div>
              <div class="flex justify-between py-1.5" style="border-bottom:1px solid #152a3e; font-size:.78rem;">
                <span style="color:#8ea9bf;">Coordenada Este</span>
                <span class="font-mono" style="color:#c8dae7;">{{ u.coordenada_este || '—' }}</span>
              </div>
              <div class="flex justify-between py-1.5" style="font-size:.78rem;">
                <span style="color:#8ea9bf;">Zona UTM</span>
                <span class="font-mono" style="color:#c8dae7;">{{ u.zona_utm || '—' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- COMPONENTES Y PRODUCTOS -->
      <div v-if="componentesList.length" class="rounded-xl overflow-hidden mt-4" style="background-color:#0d1f30; border:1px solid #1e3a52;">
        <div class="px-5 py-3" style="border-bottom:1px solid #19354d;">
          <div class="section-title" style="margin-bottom:0;">Componentes o Líneas y Capacidades</div>
        </div>

        <div class="componentes-grid px-5 py-4">
          <div
            v-for="c in componentesList"
            :key="c.id_componente"
            class="rounded-xl overflow-hidden"
            style="background-color:#0a1624; border:1px solid #1e3a52;"
          >
            <div class="px-4 py-3" style="border-bottom:1px solid #19354d; font-weight:700; font-size:.85rem; color:#f2fbff;">
              {{ c.nombre }}
            </div>

            <div class="componente-body">
              <div class="componente-col" style="border-right:1px solid #19354d;">
                <div class="col-label">Componentes / Líneas</div>
                <p v-if="c.descripcion" class="capacidades-text">{{ c.descripcion }}</p>
                <p v-else class="text-xs" style="color:#647a8e;">Sin descripción de capacidades.</p>
              </div>

              <div class="componente-col">
                <div class="col-label">Productos</div>
                <div v-if="!c.productos?.length" class="text-xs" style="color:#647a8e;">
                  Sin productos registrados.
                </div>
                <div
                  v-for="p in c.productos"
                  :key="p.id_producto"
                  class="flex justify-between py-1.5"
                  style="border-bottom:1px solid #152a3e; font-size:.78rem;"
                >
                  <span style="color:#c8dae7;">{{ p.nombre }}</span>
                  <span class="font-mono" style="color:#8ea9bf;">{{ formatearCantidadProducto(p) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

  </div>
</template>

<style scoped>
.details-page{width:100%;max-width:1680px;margin:0 auto}.details-layout{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(300px,1fr);gap:12px}.right-column{display:flex;flex-direction:column;gap:12px}@media(max-width:760px){.details-layout{grid-template-columns:1fr}.right-column{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:540px){.details-page{padding:14px}.right-column{grid-template-columns:1fr}.grid.grid-cols-4{grid-template-columns:repeat(2,minmax(0,1fr))!important}}@media(max-width:360px){.grid.grid-cols-4{grid-template-columns:1fr!important}}

.ubicaciones-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px}
.componentes-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:14px}
.componente-body{display:grid;grid-template-columns:1fr 1fr}
.componente-col{padding:14px 16px}
.col-label{font-size:.65rem;text-transform:uppercase;letter-spacing:.04em;color:#8ea9bf;font-weight:700;margin-bottom:8px}
.capacidades-text{white-space:pre-line;color:#c8dae7;font-size:.78rem;line-height:1.6;margin:0}
@media(max-width:600px){.componente-body{grid-template-columns:1fr}.componente-col:first-child{border-right:0!important;border-bottom:1px solid #19354d}}
</style>