<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/lib/axios'
import { useUtils } from '@/composables/useUtils.js'

const route = useRoute()
const { badgeClass, fillClass, kpiColor } = useUtils()

const cargando = ref(true)
const error = ref(null)

const proyecto = ref(null)
const stats = ref({
  avance_fisico: 0,
  avance_financiero: 0,
  dias_restantes: 0,
  alertas_activas: 0,
})
const contratos = ref([])
const kpis = ref([])
const problemas = ref([])

const alertas = computed(() => problemas.value.filter(p => p.estado !== 'Resuelto'))

const statCards = computed(() => [
  ['Avance físico', stats.value.avance_fisico + '%', '#00c9a7'],
  ['Avance financiero', stats.value.avance_financiero + '%', '#00c9a7'],
  ['Días restantes', stats.value.dias_restantes, '#f59e0b'],
  ['Alertas activas', stats.value.alertas_activas, '#f87171'],
])

async function cargarDashboard() {
  cargando.value = true
  error.value = null

  // El dashboard general todavía no depende de un proyecto seleccionado.
  // Cuando exista el selector de proyecto, aquí se pasará el código real.
  const codigoProyecto = route.params.codigo

  if (!codigoProyecto) {
    cargando.value = false
    return
  }

  try {
    const { data } = await axios.get(`/api/proyectos/${codigoProyecto}/dashboard`)

    proyecto.value = data.proyecto
    stats.value = data.stats
    contratos.value = data.contratos
    kpis.value = data.kpis
    problemas.value = data.problemas
  } catch (e) {
    error.value = 'No se pudo cargar el dashboard.'
    console.error(e)
  } finally {
    cargando.value = false
  }
}

onMounted(cargarDashboard)
</script>

<template>
  <div class="p-5">

    <div v-if="cargando" class="text-sm" style="color:#8ea9bf;">Cargando dashboard…</div>
    <div v-else-if="error" class="text-sm" style="color:#f87171;">{{ error }}</div>

    <template v-else>
      <!-- Stats principales -->
      <div class="grid grid-cols-4 gap-3 mb-5">
        <div v-for="[l, v, color] in statCards" :key="l"
          class="rounded-xl p-4"
          style="background-color:#0d1f30; border:1px solid #1e3a52;">
          <div class="field-label">{{ l }}</div>
          <div class="text-2xl font-bold mt-1" :style="{ color }">{{ v }}</div>
        </div>
      </div>

      <!-- Gráfico + KPIs -->
      <div class="grid grid-cols-2 gap-4 mb-4">

        <!-- Gráfico de avance por contrato -->
        <div class="rounded-xl p-4" style="background-color:#0d1f30; border:1px solid #1e3a52;">
          <div class="section-title">Avance por contrato (%)</div>
          <div class="flex gap-1 items-end" style="height:120px">
            <div v-for="c in contratos" :key="c.n" class="flex-1 flex flex-col items-center">
              <div class="flex gap-0.5 w-full items-end" style="height:108px">
                <div class="flex-1 rounded-t-sm" :style="{ height: Math.max(2, c.af * 1.08) + 'px', background:'rgba(0,201,167,0.7)' }" />
                <div class="flex-1 rounded-t-sm" :style="{ height: Math.max(2, c.afin * 1.08) + 'px', background:'rgba(0,229,192,0.45)' }" />
              </div>
              <div class="text-[9px] text-center truncate w-full mt-1" style="color:#8ea9bf;">{{ c.short }}</div>
            </div>
          </div>
          <div class="flex gap-4 mt-2">
            <span class="flex items-center gap-1 text-[11px]" style="color:#8ea9bf;">
              <span class="w-2.5 h-2.5 rounded-sm inline-block" style="background:rgba(0,201,167,0.7)" />Físico
            </span>
            <span class="flex items-center gap-1 text-[11px]" style="color:#8ea9bf;">
              <span class="w-2.5 h-2.5 rounded-sm inline-block" style="background:rgba(0,229,192,0.45)" />Financiero
            </span>
          </div>
        </div>

        <!-- Indicadores de desempeño -->
        <div class="rounded-xl p-4" style="background-color:#0d1f30; border:1px solid #1e3a52;">
          <div class="section-title">Indicadores de desempeño</div>
          <div class="flex flex-col gap-2.5">
            <div v-for="k in kpis" :key="k.label">
              <div class="flex justify-between mb-1" style="font-size:0.75rem;">
                <span style="color:#c8dae7;">{{ k.label }}</span>
                <span :class="kpiColor(k.v)" class="font-semibold">{{ k.v.toFixed(1) }}%</span>
              </div>
              <div class="prog-bar">
                <div class="prog-fill" :class="fillClass(k.v)" :style="{ width: k.v + '%' }" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Alertas activas -->
      <div class="rounded-xl p-4" style="background-color:#0d1f30; border:1px solid #1e3a52;">
        <div class="section-title">Alertas activas</div>
        <div class="overflow-x-auto">
          <table class="table table-xs">
            <thead>
              <tr style="border-bottom:2px solid #1e3a52;">
                <th>Problema</th><th>Impacto</th><th>Responsable</th><th>Estado</th><th>Días</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in alertas" :key="p.n" style="border-bottom:1px solid #152a3e;">
                <td class="max-w-xs">{{ p.prob }}</td>
                <td><span :class="badgeClass(p.impacto)">{{ p.impacto }}</span></td>
                <td style="color:#8ea9bf; font-size:0.75rem;">{{ p.resp }}</td>
                <td><span :class="badgeClass(p.estado)">{{ p.estado }}</span></td>
                <td class="font-bold" :style="{ color: p.dias > 30 ? '#f87171' : '#f59e0b' }">{{ p.dias }}d</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

  </div>
</template>