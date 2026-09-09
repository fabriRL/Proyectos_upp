<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from '@/lib/axios'
import { useUtils } from '@/composables/useUtils.js'

const { badgeClass, fillClass, kpiColor } = useUtils()

const cargando = ref(true)
const error = ref(null)

const totalProyectos = ref(0)
const stats = ref({
  avance_fisico: 0,
  avance_financiero: 0,
  proyectos_vencidos: 0,
  alertas_activas: 0,
})
const contratos = ref([])
const kpis = ref([])
const problemas = ref([])

const alertas = computed(() => problemas.value.filter(p => p.estado !== 'Resuelto'))

const statCards = computed(() => [
  ['Avance físico', stats.value.avance_fisico + '%', '#00c9a7'],
  ['Avance financiero', stats.value.avance_financiero + '%', '#00c9a7'],
  ['Proyectos vencidos', stats.value.proyectos_vencidos, '#f59e0b'],
  ['Alertas activas', stats.value.alertas_activas, '#f87171'],
])

async function cargarDashboard() {
  cargando.value = true
  error.value = null

  try {
    const { data } = await axios.get('/api/dashboard')

    totalProyectos.value = data.total_proyectos
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
      <div class="flex justify-between items-center mb-4">
        <div class="section-title" style="margin-bottom:0;">Dashboard de seguimiento</div>
        <span style="font-size:.75rem;color:#8ea9bf;">{{ totalProyectos }} proyecto(s) en seguimiento</span>
      </div>

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

        <!-- Avance por proyecto (lista con barras horizontales) -->
        <div class="rounded-xl p-4" style="background-color:#0d1f30; border:1px solid #1e3a52;">
          <div class="flex justify-between items-center mb-1">
            <div class="section-title" style="margin-bottom:0;">Avance por proyecto</div>
            <div class="flex gap-3">
              <span class="flex items-center gap-1 text-[10px]" style="color:#8ea9bf;">
                <span class="w-2 h-2 rounded-sm inline-block" style="background:#00c9a7" />Físico
              </span>
              <span class="flex items-center gap-1 text-[10px]" style="color:#8ea9bf;">
                <span class="w-2 h-2 rounded-sm inline-block" style="background:#55b8ef" />Financiero
              </span>
            </div>
          </div>

          <div v-if="!contratos.length" class="text-sm text-center py-8" style="color:#647a8e;">
            Todavía no hay proyectos con contratos registrados.
          </div>

          <div v-else class="proyectos-avance-list">
            <div v-for="c in contratos" :key="c.n" class="proyecto-avance-row">
              <div class="proyecto-avance-nombre" :title="c.short">{{ c.short }}</div>

              <div class="proyecto-avance-barras">
                <div class="mini-bar-row">
                  <div class="mini-bar-track">
                    <div class="mini-bar-fill" :style="{ width: Math.min(100, c.af) + '%', background: '#00c9a7' }" />
                  </div>
                  <span class="mini-bar-valor" style="color:#00c9a7;">{{ c.af.toFixed(0) }}%</span>
                </div>
                <div class="mini-bar-row">
                  <div class="mini-bar-track">
                    <div class="mini-bar-fill" :style="{ width: Math.min(100, c.afin) + '%', background: '#55b8ef' }" />
                  </div>
                  <span class="mini-bar-valor" style="color:#55b8ef;">{{ c.afin.toFixed(0) }}%</span>
                </div>
              </div>
            </div>
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
        <div v-if="!alertas.length" class="text-sm text-center py-8" style="color:#647a8e;">
          No hay alertas activas en ningún proyecto por el momento.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="table table-xs">
            <thead>
              <tr style="border-bottom:2px solid #1e3a52;">
                <th>Proyecto</th><th>Problema</th><th>Impacto</th><th>Responsable</th><th>Estado</th><th>Días</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in alertas" :key="p.n" style="border-bottom:1px solid #152a3e;">
                <td style="color:#c8dae7; font-size:0.75rem; white-space:nowrap;">{{ p.proyecto }}</td>
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

<style scoped>
.proyectos-avance-list {
  max-height: 210px;
  overflow-y: auto;
  padding-right: 4px;
}

.proyecto-avance-row {
  display: grid;
  grid-template-columns: 72px 1fr;
  gap: 10px;
  align-items: center;
  padding: 7px 0;
  border-bottom: 1px solid #152a3e;
}

.proyecto-avance-row:last-child {
  border-bottom: 0;
}

.proyecto-avance-nombre {
  font-size: 0.68rem;
  color: #c8dae7;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.proyecto-avance-barras {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.mini-bar-row {
  display: flex;
  align-items: center;
  gap: 6px;
}

.mini-bar-track {
  flex: 1;
  height: 6px;
  border-radius: 999px;
  background: #152a3e;
  overflow: hidden;
}

.mini-bar-fill {
  height: 100%;
  border-radius: 999px;
  transition: width 0.3s ease;
}

.mini-bar-valor {
  font-size: 0.62rem;
  font-weight: 700;
  font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
  width: 30px;
  text-align: right;
  flex-shrink: 0;
}
</style>