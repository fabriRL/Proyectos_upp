<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/lib/axios'

const route = useRoute()
const codigoProyecto = route.params.codigo

const cargando = ref(true)
const error = ref(null)
const datos = ref(null)

const config = [
  { key: 'cumplimiento_cronograma', label: 'Cumplimiento del cronograma', icon: 'ti-calendar-check', detail: 'Avance de actividades programadas' },
  { key: 'gestion_problemas', label: 'Gestión de problemas', icon: 'ti-alert-triangle', detail: 'Problemas atendidos oportunamente' },
  { key: 'indice_desempeno_fiscal', label: 'Índice de desempeño del fiscal', icon: 'ti-user-check', detail: 'Avance físico frente al financiero' },
  { key: 'riesgo_contractual', label: 'Riesgo contractual', icon: 'ti-file-alert', detail: 'Exposición por contratos vigentes', inverse: true },
  { key: 'cumplimiento_financiero', label: 'Cumplimiento financiero', icon: 'ti-cash', detail: 'Ejecución frente a lo contratado' },
  { key: 'ejecucion_presupuestaria', label: 'Ejecución presupuestaria', icon: 'ti-chart-pie', detail: 'Presupuesto ejecutado sobre el decreto' },
  { key: 'utilizacion_decreto_supremo', label: 'Utilización del Decreto Supremo', icon: 'ti-file-invoice', detail: 'Uso de recursos aprobados' },
  { key: 'nivel_riesgo', label: 'Nivel de riesgo', icon: 'ti-shield-exclamation', detail: 'Riesgo consolidado del proyecto', inverse: true },
]

const indicators = computed(() => {
  if (!datos.value) return []
  return config.map(c => ({
    ...c,
    value: Number(datos.value[c.key] ?? 0),
  }))
})

// Un indicador fuera de 0-100 casi siempre significa un dato mal cargado
// en el proyecto (ej. Monto del Decreto), no un nivel de riesgo real —
// se marca aparte en vez de mostrarlo como "Favorable" por accidente.
function tone(indicator) {
  if (indicator.value > 100 || indicator.value < 0) {
    return { label: 'Dato a revisar', color: '#a78bfa', bg: 'rgba(167,139,250,.11)' }
  }
  const alertValue = indicator.inverse ? 100 - indicator.value : indicator.value
  if (alertValue < 35) return { label: 'Crítico', color: '#f87171', bg: 'rgba(248,113,113,.11)' }
  if (alertValue < 65) return { label: 'En observación', color: '#fbbf24', bg: 'rgba(251,191,36,.11)' }
  return { label: 'Favorable', color: '#00c9a7', bg: 'rgba(0,201,167,.11)' }
}

// Promedio Consolidado: cada indicador aporta como máximo 100 puntos al
// promedio, aunque su valor real esté inflado por un dato mal cargado —
// así un solo KPI con un error de datos no arrastra el consolidado a
// números absurdos (ej. 387%). El valor CRUDO sigue mostrándose tal cual
// en su propia tarjeta, para que el problema de datos siga siendo visible
// y se pueda corregir en el proyecto correspondiente.
const average = computed(() => {
  if (!indicators.value.length) return '0,00'
  const val = indicators.value.reduce((sum, item) => sum + Math.min(100, Math.max(0, item.value)), 0) / indicators.value.length
  return val.toFixed(2).replace('.', ',')
})

const critical = computed(() => indicators.value.filter(item => tone(item).label === 'Crítico').length)
const conDatosSospechosos = computed(() => indicators.value.filter(item => tone(item).label === 'Dato a revisar').length)

const fechaActual = computed(() =>
  new Date().toLocaleDateString('es-BO', { day: '2-digit', month: 'long', year: 'numeric' })
)

async function cargar() {
  cargando.value = true
  error.value = null
  try {
    const { data } = await axios.get(`/api/proyectos/${codigoProyecto}/indicadores`)
    datos.value = data
  } catch (e) {
    error.value = 'No se pudieron cargar los indicadores del proyecto.'
    console.error(e)
  } finally {
    cargando.value = false
  }
}

function exportarCsv() {
  if (!indicators.value.length) return

  const filas = [
    ['Indicador', 'Valor (%)', 'Estado'],
    ...indicators.value.map(i => [i.label, i.value.toFixed(2).replace('.', ','), tone(i).label]),
  ]

  const csv = filas
    .map(fila => fila.map(campo => `"${String(campo).replace(/"/g, '""')}"`).join(';'))
    .join('\n')

  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)

  const enlace = document.createElement('a')
  enlace.href = url
  enlace.download = `indicadores_${codigoProyecto}_${new Date().toISOString().split('T')[0]}.csv`
  document.body.appendChild(enlace)
  enlace.click()
  document.body.removeChild(enlace)
  URL.revokeObjectURL(url)
}

onMounted(cargar)
</script>

<template>
  <div class="p-5 max-w-6xl mx-auto">

    <div v-if="cargando" class="text-center py-10" style="color:#8ea9bf;">
      Calculando indicadores...
    </div>

    <div v-else-if="error" class="text-center py-10" style="color:#f87171;">
      {{ error }}
      <button class="btn btn-sm btn-ghost ml-2" @click="cargar">Reintentar</button>
    </div>

    <template v-else>
      <header class="mb-5">
        <p class="text-xs font-semibold uppercase tracking-wider" style="color:#00c9a7;">Proyecto {{ codigoProyecto }}</p>
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3 mt-1">
          <div>
            <h1 class="text-xl font-bold" style="color:#d4e4f0;">Resumen de indicadores del proyecto</h1>
            <p class="text-sm mt-1" style="color:#8ea9bf;">Consolidado de desempeño, ejecución y riesgos al {{ fechaActual }}.</p>
          </div>
          <button class="btn btn-sm btn-ghost" style="color:#b4c9d9;" @click="exportarCsv">
            <i class="ti ti-download"></i> Exportar resumen
          </button>
        </div>
      </header>

      <div v-if="conDatosSospechosos > 0" class="alerta-datos">
        <i class="ti ti-alert-triangle"></i>
        {{ conDatosSospechosos }} indicador(es) con un valor fuera de rango — probablemente un dato mal cargado en este proyecto (ej. Monto del Decreto). Revísalo en la pestaña "Datos generales".
      </div>

      <div class="grid sm:grid-cols-3 gap-3 mb-5">
        <div class="overview"><span>Indicadores evaluados</span><strong>{{ indicators.length }}</strong></div>
        <div class="overview"><span>Promedio consolidado</span><strong style="color:#fbbf24;">{{ average }}%</strong></div>
        <div class="overview"><span>Indicadores críticos</span><strong :style="{ color: critical ? '#f87171' : '#00c9a7' }">{{ critical }}</strong></div>
      </div>

      <section class="indicator-panel">
        <div class="panel-title">
          <div>
            <h2>4. Resumen de indicadores del proyecto</h2>
            <p>Cada indicador se representa en una escala de 0 a 100%.</p>
          </div>
          <div class="legend">
            <span><i style="background:#00c9a7;"></i> Favorable</span>
            <span><i style="background:#fbbf24;"></i> En observación</span>
            <span><i style="background:#f87171;"></i> Crítico</span>
            <span><i style="background:#a78bfa;"></i> Dato a revisar</span>
          </div>
        </div>
        <div class="indicator-grid">
          <article
            v-for="indicator in indicators"
            :key="indicator.label"
            class="indicator-card"
            :style="{ '--tone': tone(indicator).color, '--tone-bg': tone(indicator).bg }"
          >
            <div class="indicator-icon"><i :class="`ti ${indicator.icon}`"></i></div>
            <div class="indicator-label">{{ indicator.label }}</div>
            <div class="indicator-value">{{ String(indicator.value.toFixed(2)).replace('.', ',') }}<small>%</small></div>
            <div class="bar"><span :style="{ width: `${Math.min(100, Math.max(0, indicator.value))}%` }"></span></div>
            <div class="indicator-footer">
              <span>{{ indicator.detail }}</span>
              <b>{{ tone(indicator).label }}</b>
            </div>
          </article>
        </div>
      </section>

      <p class="method-note">
        <i class="ti ti-info-circle"></i> Los valores corresponden al consolidado del proyecto y se calculan a partir de actividades, problemas y contratos registrados.
      </p>
    </template>

  </div>
</template>

<style scoped>
.overview,.indicator-panel{background:#0d1f30;border:1px solid #1e3a52;border-radius:12px}.overview{padding:14px 16px}.overview span{display:block;color:#8ea9bf;font-size:.7rem;font-weight:600;letter-spacing:.04em;text-transform:uppercase}.overview strong{display:block;color:#d4e4f0;font-size:1.25rem;margin-top:4px}.indicator-panel{overflow:hidden}.panel-title{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:17px 18px;border-bottom:1px solid #19354d}.panel-title h2{color:#d4e4f0;font-size:.92rem;font-weight:700}.panel-title p{color:#8ea9bf;font-size:.72rem;margin-top:4px}.legend{display:flex;flex-wrap:wrap;gap:10px;color:#8ea9bf;font-size:.67rem}.legend i{width:7px;height:7px;border-radius:50%;display:inline-block;margin-right:4px}.indicator-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr))}.indicator-card{min-height:205px;padding:16px;border-right:1px solid #19354d;border-bottom:1px solid #19354d}.indicator-card:nth-child(4n){border-right:0}.indicator-card:nth-last-child(-n+4){border-bottom:0}.indicator-icon{color:var(--tone);width:30px;height:30px;display:grid;place-items:center;background:var(--tone-bg);border-radius:7px}.indicator-icon .ti{font-size:16px}.indicator-label{color:#c8dae7;font-size:.76rem;font-weight:700;line-height:1.3;min-height:39px;margin-top:12px}.indicator-value{color:#d4e4f0;font-size:1.4rem;font-weight:800;margin-top:7px}.indicator-value small{color:#8ea9bf;font-size:.7rem;margin-left:2px}.bar{height:5px;background:#152a3e;border-radius:5px;overflow:hidden;margin-top:10px}.bar span{display:block;height:100%;border-radius:5px;background:var(--tone)}.indicator-footer{margin-top:11px;display:flex;flex-direction:column;gap:4px}.indicator-footer span{color:#58758d;font-size:.65rem;line-height:1.25}.indicator-footer b{color:var(--tone);font-size:.65rem}.method-note{color:#8ea9bf;font-size:.72rem;margin-top:12px}.method-note i{color:#00c9a7;margin-right:4px}
.alerta-datos{display:flex;align-items:center;gap:8px;padding:10px 14px;margin-bottom:16px;border-radius:8px;background:rgba(167,139,250,.1);border:1px solid rgba(167,139,250,.3);color:#c4b5fd;font-size:.78rem}
.alerta-datos i{font-size:1rem;flex-shrink:0}
@media(max-width:900px){.indicator-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.indicator-card:nth-child(4n){border-right:1px solid #19354d}.indicator-card:nth-child(2n){border-right:0}.indicator-card:nth-last-child(-n+4){border-bottom:1px solid #19354d}.indicator-card:nth-last-child(-n+2){border-bottom:0}}@media(max-width:600px){.panel-title{align-items:flex-start;flex-direction:column}.indicator-grid{grid-template-columns:1fr}.indicator-card,.indicator-card:nth-child(4n),.indicator-card:nth-child(2n){border-right:0;border-bottom:1px solid #19354d;min-height:0}.indicator-card:last-child{border-bottom:0}}
</style>