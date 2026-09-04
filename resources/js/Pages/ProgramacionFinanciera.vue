<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { useUtils } from '@/composables/useUtils.js'
import { useToast } from '@/composables/useToast.js'
import NuevaPartida from './NuevaPartida.vue'
import NuevoObjetoGasto from './NuevoObjetoGasto.vue'
import EditarObjetoGasto from './EditarObjetoGasto.vue'

const route = useRoute()
const codigoProyecto = route.params.codigo
const { badgeClass, fmtBs } = useUtils()
const { showToast } = useToast()

const partidas = ref([])
const cargando = ref(true)
const error = ref(null)

const mostrarNuevaPartida = ref(false)
const idPartidaAgregandoObjeto = ref(null)
const objetoEditando = ref(null)

const monthNames = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic']
const camposMes = ['monto_ene','monto_feb','monto_mar','monto_abr','monto_may','monto_jun','monto_jul','monto_ago','monto_sep','monto_oct','monto_nov','monto_dic']

function money(valor) {
  return fmtBs(Number(valor) || 0)
}

async function cargar() {
  cargando.value = true
  error.value = null
  try {
    const { data } = await axios.get(`/api/proyectos/${codigoProyecto}/programacion-financiera`)
    partidas.value = data
  } catch (e) {
    error.value = 'No se pudo cargar la programación financiera.'
    console.error(e)
  } finally {
    cargando.value = false
  }
}

onMounted(cargar)

async function eliminarPartida(idPartida) {
  if (!confirm('¿Eliminar esta partida y todos sus objetos de gasto?')) return
  try {
    await axios.delete(`/api/partidas/${idPartida}`)
    await cargar()
    showToast('Partida eliminada correctamente.', 'success')
  } catch (e) {
    console.error(e)
    showToast('No se pudo eliminar la partida.', 'error')
  }
}

async function eliminarObjeto(idObjeto) {
  if (!confirm('¿Eliminar este objeto de gasto?')) return
  try {
    await axios.delete(`/api/objetos-gasto/${idObjeto}`)
    await cargar()
    showToast('Objeto de gasto eliminado correctamente.', 'success')
  } catch (e) {
    console.error(e)
    showToast('No se pudo eliminar el objeto de gasto.', 'error')
  }
}

// Desviación = 100% − Cumplimiento% (misma fórmula que ya tenías en el mock).
function desviacion(objeto) {
  if (objeto.cumplimiento_financiero === null || objeto.cumplimiento_financiero === undefined) return null
  return 1 - objeto.cumplimiento_financiero
}

// ⚠️ SUPUESTO — falta confirmar la fórmula real de "Estado Financiero".
// Mientras tanto, uso umbrales razonables sobre el cumplimiento:
//   >= 90%  → Óptimo
//   50-89%  → Aceptable
//   < 50%   → Crítico
//   sin programación acumulada aún → Pendiente
// Cambiar esta función en cuanto tengas la fórmula real.
function estadoFinanciero(objeto) {
  if (objeto.cumplimiento_financiero === null || objeto.cumplimiento_financiero === undefined) return 'Pendiente'
  if (objeto.cumplimiento_financiero >= 0.9) return 'Óptimo'
  if (objeto.cumplimiento_financiero >= 0.5) return 'Aceptable'
  return 'Crítico'
}

function fmtPct(fraccion) {
  if (fraccion === null || fraccion === undefined) return '—'
  return (fraccion * 100).toFixed(2) + '%'
}

// --- Totales globales, cuidando de no duplicar el presupuesto por partida ---
const totalPresupuesto = computed(() =>
  partidas.value.reduce((sum, p) => sum + Number(p.presupuesto_aprobado), 0)
)
const totalEjecutado = computed(() =>
  partidas.value.reduce((sum, p) => sum + p.objetos.reduce((s, o) => s + Number(o.monto_ejecutado), 0), 0)
)
const totalProgramado = computed(() =>
  partidas.value.reduce((sum, p) => sum + p.objetos.reduce((s, o) => s + Number(o.total_anual), 0), 0)
)
const totalAcumulado = computed(() =>
  partidas.value.reduce((sum, p) => sum + p.objetos.reduce((s, o) => s + Number(o.programacion_acumulada), 0), 0)
)
const totalPorMes = computed(() =>
  monthNames.map((_, i) =>
    partidas.value.reduce((sum, p) => sum + p.objetos.reduce((s, o) => s + Number(o.meses[camposMes[i]]), 0), 0)
  )
)
const totalCumplimiento = computed(() =>
  totalAcumulado.value > 0 ? Math.min(totalEjecutado.value / totalAcumulado.value, 1) : null
)
</script>

<template>
  <div class="p-5 financial-page">
    <header class="financial-header">
      <div>
        <p class="eyebrow">SEGUIMIENTO SIGEP</p>
        <h1>Programación financiera</h1>
        <p>Programación mensual y ejecución acumulada por objeto de gasto.</p>
      </div>
      <button class="btn btn-sm" style="background:#00c9a7;color:#04211c;font-weight:600;" @click="mostrarNuevaPartida = true">
        <i class="ti ti-plus"></i> Nueva partida
      </button>
    </header>

    <div v-if="cargando" class="text-center py-10" style="color:#8ea9bf;">
      Cargando programación financiera...
    </div>

    <div v-else-if="error" class="text-center py-10" style="color:#f87171;">
      {{ error }}
      <button class="btn btn-sm btn-ghost ml-2" @click="cargar">Reintentar</button>
    </div>

    <template v-else>
      <div class="grid grid-cols-3 gap-3 mb-5">
        <div
          v-for="[label, value, color] in [
            ['Presupuesto aprobado SIGEP', money(totalPresupuesto), '#d0dde8'],
            ['Programación acumulada', money(totalAcumulado), '#00c9a7'],
            ['Monto ejecutado', money(totalEjecutado), '#fbbf24'],
          ]"
          :key="label" class="rounded-xl p-4" style="background-color:#0d1f30;border:1px solid #1e3a52;"
        >
          <div class="field-label">{{ label }}</div>
          <div class="font-bold text-xl mt-1" :style="{ color }">{{ value }}</div>
        </div>
      </div>

      <div v-if="!partidas.length" class="text-center py-10 rounded-xl" style="background-color:#0d1f30;border:1px solid #1e3a52;color:#8ea9bf;">
        Este proyecto todavía no tiene partidas presupuestarias registradas.
      </div>

      <section v-else class="rounded-xl overflow-hidden" style="background-color:#0d1f30;border:1px solid #1e3a52;">
        <div class="matrix-title">
          <div class="section-title" style="margin-bottom:0;">Programación financiera por objeto de gasto</div>
        </div>

        <div class="overflow-x-auto">
          <table class="table table-xs financial-table">
            <thead>
              <tr>
                <th>N°</th><th>Objeto</th><th>Descripción objeto de gasto</th>
                <th>Presupuesto aprobado SIGEP</th>
                <th v-for="month in monthNames" :key="month">{{ month }}</th>
                <th>Total programado</th><th>Monto ejecutado</th><th>Saldo por ejecutar</th>
                <th>Programación acumulada</th><th>Cumplimiento financiero</th>
                <th>Desviación</th><th>Estado financiero</th><th></th>
              </tr>
            </thead>
            <tbody>
              <template v-for="partida in partidas" :key="partida.id_partida">
                <!-- Cabecera de grupo (equivale a la celda combinada del Excel) -->
                <tr class="group-row">
                  <td :colspan="4 + monthNames.length + 6" class="group-header">
                    <span class="group-badge">Partida</span>
                    Presupuesto aprobado: <strong>{{ money(partida.presupuesto_aprobado) }}</strong>
                    <span class="group-sep">·</span>
                    Saldo por ejecutar: <strong :style="{ color: partida.saldo_por_ejecutar < 0 ? '#f87171' : '#fbbf24' }">{{ money(partida.saldo_por_ejecutar) }}</strong>
                  </td>
                  <td class="group-actions">
                    <button class="btn-icon" style="color:#00c9a7;" title="Agregar objeto de gasto" @click="idPartidaAgregandoObjeto = partida.id_partida">
                      <i class="ti ti-plus"></i>
                    </button>
                    <button class="btn-icon" style="color:#f87171;" title="Eliminar partida" @click="eliminarPartida(partida.id_partida)">
                      <i class="ti ti-trash"></i>
                    </button>
                  </td>
                </tr>

                <tr v-if="!partida.objetos.length">
                  <td :colspan="5 + monthNames.length + 6" class="text-center py-4" style="color:#647a8e;">
                    Sin objetos de gasto en esta partida.
                  </td>
                </tr>

                <tr v-for="o in partida.objetos" :key="o.id_objeto" class="item-row">
                  <td>{{ o.numero }}</td>
                  <td class="object">{{ o.codigo_objeto }}</td>
                  <td>{{ o.descripcion }}</td>
                  <td class="money">{{ money(partida.presupuesto_aprobado) }}</td>
                  <td v-for="campo in camposMes" :key="campo" class="money">
                    {{ o.meses[campo] > 0 ? money(o.meses[campo]) : '—' }}
                  </td>
                  <td class="money strong">{{ money(o.total_anual) }}</td>
                  <td class="money">{{ money(o.monto_ejecutado) }}</td>
                  <td class="money">{{ money(o.saldo_por_ejecutar) }}</td>
                  <td class="money">{{ money(o.programacion_acumulada) }}</td>
                  <td class="metric">{{ fmtPct(o.cumplimiento_financiero) }}</td>
                  <td class="metric deviation">{{ fmtPct(desviacion(o)) }}</td>
                  <td><span :class="badgeClass(estadoFinanciero(o))">{{ estadoFinanciero(o) }}</span></td>
                  <td>
                    <button class="btn-icon" style="color:#55b8ef;" title="Editar" @click="objetoEditando = o">
                      <i class="ti ti-pencil"></i>
                    </button>
                    <button class="btn-icon" style="color:#f87171;" title="Eliminar" @click="eliminarObjeto(o.id_objeto)">
                      <i class="ti ti-trash"></i>
                    </button>
                  </td>
                </tr>
              </template>

              <tr class="total-row">
                <td colspan="3">TOTAL</td>
                <td class="money">{{ money(totalPresupuesto) }}</td>
                <td v-for="(valor, idx) in totalPorMes" :key="idx" class="money">{{ money(valor) }}</td>
                <td class="money">{{ money(totalProgramado) }}</td>
                <td class="money">{{ money(totalEjecutado) }}</td>
                <td class="money">{{ money(totalPresupuesto - totalEjecutado) }}</td>
                <td class="money">{{ money(totalAcumulado) }}</td>
                <td class="metric">{{ fmtPct(totalCumplimiento) }}</td>
                <td class="metric deviation">{{ fmtPct(totalCumplimiento !== null ? 1 - totalCumplimiento : null) }}</td>
                <td></td>
                <td></td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </template>

    <NuevaPartida
      :show="mostrarNuevaPartida"
      :codigo-proyecto="codigoProyecto"
      @close="mostrarNuevaPartida = false"
      @created="cargar"
    />

    <NuevoObjetoGasto
      :show="!!idPartidaAgregandoObjeto"
      :id-partida="idPartidaAgregandoObjeto"
      @close="idPartidaAgregandoObjeto = null"
      @created="cargar"
    />

    <EditarObjetoGasto
      v-if="objetoEditando"
      :key="objetoEditando.id_objeto"
      :objeto="objetoEditando"
      @close="objetoEditando = null"
      @updated="cargar"
    />
  </div>
</template>

<style scoped>
.financial-page{max-width:1680px;margin:auto}
.financial-header{display:flex;align-items:end;justify-content:space-between;gap:16px;margin-bottom:18px}
.eyebrow{font-size:.68rem;letter-spacing:.12em;font-weight:700;color:#00c9a7;margin:0 0 5px}
.financial-header h1{font-size:1.3rem;font-weight:700;color:#d4e4f0;margin:0}
.financial-header p:not(.eyebrow){font-size:.78rem;color:#8ea9bf;margin:4px 0 0}
.matrix-title{display:flex;justify-content:space-between;align-items:center;padding:12px 18px;border-bottom:1px solid #19354d}
.financial-table{font-size:.65rem;min-width:2100px}
.financial-table th{white-space:normal;line-height:1.15;text-align:center;min-width:73px;padding:8px 5px;color:#8ea9bf;text-transform:uppercase;font-size:.6rem}
.financial-table th:nth-child(3){min-width:175px}
.financial-table td{padding:8px 5px;white-space:nowrap;color:#8ea9bf}
.financial-table td:nth-child(3){white-space:normal;line-height:1.2;color:#c8dae7}
.item-row{transition:background .15s}
.item-row:hover{background:rgba(0,201,167,.06)!important}
.object{font-family:ui-monospace,monospace;font-weight:700;color:#00c9a7!important}
.money{text-align:right!important;font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:.6rem;color:#c8dae7!important}
.strong{font-weight:700}
.metric{text-align:center!important;font-weight:700;color:#c8dae7!important}
.deviation{color:#f87171!important}
.total-row td{background:#091520!important;border-top:2px solid #1e3a52;color:#d0dde8!important;font-weight:700}
.group-row td{background:#0a1826!important;border-top:1px solid #1e3a52;padding:8px 14px!important}
.group-header{font-size:.68rem;color:#8ea9bf;text-align:left!important;white-space:normal!important}
.group-header strong{color:#d0dde8}
.group-badge{display:inline-block;padding:1px 7px;margin-right:8px;border-radius:4px;background:rgba(0,201,167,.12);color:#00c9a7;font-weight:700;font-size:.6rem;text-transform:uppercase}
.group-sep{margin:0 8px;color:#3a556b}
.group-actions{text-align:right!important;white-space:nowrap}
.btn-icon{background:none;border:none;cursor:pointer;font-size:13px;padding:4px 6px}
@media(max-width:700px){.financial-page{padding:14px}.financial-header{align-items:flex-start;flex-direction:column}.grid{grid-template-columns:1fr!important}.matrix-title{align-items:flex-start;flex-direction:column;gap:5px}}
</style>