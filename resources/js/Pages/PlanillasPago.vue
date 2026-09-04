<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { useUtils } from '@/composables/useUtils.js'
import NuevaPlanilla from './NuevaPlanilla.vue'

const route = useRoute()
const codigoProyecto = route.params.codigo
const { fmtBs } = useUtils()

const contratos = ref([])
const idContratoSeleccionado = ref(null)
const planillas = ref([])
const cargandoContratos = ref(true)
const cargandoPlanillas = ref(false)
const error = ref(null)
const mostrarModal = ref(false)

const meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic']
function fmtFecha(fecha) {
  if (!fecha) return '—'
  const soloFecha = String(fecha).split('T')[0]
  const d = new Date(soloFecha + 'T00:00:00')
  if (isNaN(d.getTime())) return '—'
  return `${d.getDate()}-${meses[d.getMonth()]}-${String(d.getFullYear()).slice(2)}`
}

const contratoSeleccionado = computed(() =>
  contratos.value.find(c => c.id_contrato === idContratoSeleccionado.value) ?? null
)

// Demora promedio de PAGO real (fecha_desembolso - fecha_aprobacion_fiscal),
// no confundir con "días de atraso" (que es el atraso de ejecución de obra,
// usado para calcular la multa). Solo cuenta planillas ya desembolsadas.
const demoraPromedio = computed(() => {
  const conDemora = planillas.value.filter(p => p.dias_demora !== null && p.dias_demora !== undefined)
  if (!conDemora.length) return null
  const total = conDemora.reduce((sum, p) => sum + p.dias_demora, 0)
  return Math.round(total / conDemora.length)
})

const cargarContratos = async () => {
  cargandoContratos.value = true
  error.value = null
  try {
    const { data } = await axios.get(`/api/proyectos/${codigoProyecto}/contratos`)
    contratos.value = data.contratos
    if (contratos.value.length && !idContratoSeleccionado.value) {
      idContratoSeleccionado.value = contratos.value[0].id_contrato
    }
  } catch (e) {
    error.value = 'No se pudieron cargar los contratos.'
    console.error(e)
  } finally {
    cargandoContratos.value = false
  }
}

const cargarPlanillas = async () => {
  if (!idContratoSeleccionado.value) {
    planillas.value = []
    return
  }
  cargandoPlanillas.value = true
  error.value = null
  try {
    const { data } = await axios.get(`/api/contratos/${idContratoSeleccionado.value}/planillas`)
    planillas.value = data.map(p => ({
      id_planilla: p.id_planilla,
      numero: p.numero,
      periodo_desde: p.periodo_desde,
      periodo_hasta: p.periodo_hasta,
      monto_certificado: Number(p.monto_certificado),
      retencion_gcc: Number(p.retencion_gcc),
      dias_atraso: p.dias_atraso,
      multa: Number(p.multa),
      amortizacion: Number(p.amortizacion),
      liquido_pagable: Number(p.liquido_pagable),
      avance_fisico: p.avance_fisico !== null ? Number(p.avance_fisico) : null,
      saldo_anticipo_por_amortizar: Number(p.saldo_anticipo_por_amortizar),
      importe_pagado_sigep: Number(p.importe_pagado_sigep),
      diferencia_lp_f: Number(p.diferencia_lp_f),
      numero_c31: p.numero_c31,
      monto_c31: Number(p.monto_c31),
      diferencia_sigep_c31: Number(p.diferencia_sigep_c31),
      fecha_aprobacion_fiscal: p.fecha_aprobacion_fiscal,
      fecha_elaboracion_planilla: p.fecha_elaboracion_planilla,
      fecha_desembolso: p.fecha_desembolso,
      dias_demora: p.dias_demora,
    }))
  } catch (e) {
    error.value = 'No se pudieron cargar las planillas.'
    console.error(e)
  } finally {
    cargandoPlanillas.value = false
  }
}

const eliminarPlanilla = async (idPlanilla) => {
  if (!confirm('¿Eliminar esta planilla? Esto recalculará los totales del contrato.')) return
  try {
    await axios.delete(`/api/planillas/${idPlanilla}`)
    await Promise.all([cargarPlanillas(), cargarContratos()])
  } catch (e) {
    console.error(e)
    alert('No se pudo eliminar la planilla.')
  }
}

const alGuardarPlanilla = async () => {
  await Promise.all([cargarPlanillas(), cargarContratos()])
}

watch(idContratoSeleccionado, cargarPlanillas)
onMounted(cargarContratos)
</script>

<template>
  <div class="p-5">
    <div v-if="cargandoContratos" class="text-center py-10" style="color:#8ea9bf;">
      Cargando contratos...
    </div>

    <div v-else-if="!contratos.length" class="text-center py-10" style="color:#8ea9bf;">
      Este proyecto todavía no tiene contratos registrados.
    </div>

    <template v-else>
      <div class="rounded-xl p-4 mb-4" style="background-color:#0d1f30; border:1px solid #1e3a52;">
        <div class="field-label" style="margin-bottom:6px;">Contrato / paquete</div>
        <select
          v-model="idContratoSeleccionado"
          style="width:100%;max-width:480px;background:#091520;border:1px solid #1e3a52;border-radius:6px;padding:8px;color:#c8dae7;"
        >
          <option v-for="c in contratos" :key="c.id_contrato" :value="c.id_contrato">
            N°{{ c.numero }} — {{ c.tipo_contrato }} — {{ c.contratista }}
          </option>
        </select>
      </div>

      <template v-if="contratoSeleccionado">
        <div class="grid grid-cols-3 gap-3 mb-5">
          <div v-for="[l, v, color] in [
              ['Monto ejecutado acum.', fmtBs(Number(contratoSeleccionado.monto_ejecutado_acumulado)), '#d0dde8'],
              ['Anticipo otorgado', fmtBs(Number(contratoSeleccionado.anticipo)), '#d0dde8'],
              ['Demora promedio de pago', demoraPromedio !== null ? `${demoraPromedio} días` : '—', '#f87171'],
            ]"
            :key="l" class="rounded-xl p-4" style="background-color:#0d1f30; border:1px solid #1e3a52;">
            <div class="field-label">{{ l }}</div>
            <div class="font-bold text-xl mt-1" :style="{ color }">{{ v }}</div>
          </div>
        </div>

        <div class="rounded-xl overflow-hidden" style="background-color:#0d1f30; border:1px solid #1e3a52;">
          <div class="px-5 py-3 flex justify-between items-center gap-3" style="border-bottom:1px solid #19354d;">
            <div class="section-title" style="margin-bottom:0;">
              Paquete {{ contratoSeleccionado.numero }} — {{ contratoSeleccionado.contratista }}
            </div>
            <button
              class="btn btn-sm"
              style="background:#00c9a7;color:#04211c;font-weight:600;"
              @click="mostrarModal = true"
            >
              + Nueva planilla
            </button>
          </div>

          <div v-if="cargandoPlanillas" class="text-center py-10" style="color:#8ea9bf;">
            Cargando planillas...
          </div>

          <div v-else-if="!planillas.length" class="text-center py-10" style="color:#8ea9bf;">
            Este contrato todavía no tiene planillas registradas.
          </div>

          <div v-else class="overflow-x-auto">
            <table class="table table-xs planillas-table">
              <thead>
                <tr style="border-bottom:2px solid #1e3a52;">
                  <th>N°</th><th>Periodo</th>
                  <th>Importe Ejecutado (A)</th><th>Retenciones (B)</th><th>Multas (C)</th>
                  <th>Amort. Anticipo (E)</th><th>Líquido Pagable (LP)</th>
                  <th>Saldo Anticipo por Amortizar</th>
                  <th>Días atraso</th><th>Avance físico</th>
                  <th>Pagado SIGEP (F)</th><th>Dif. (LP−F)</th>
                  <th>N° C-31</th><th>Monto C-31 (G)</th><th>Dif. (SIGEP−C31)</th>
                  <th>Aprob. Fiscal</th><th>Elab. Planilla</th><th>Desembolso</th>
                  <th>Días demora</th><th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in planillas" :key="p.id_planilla" style="border-bottom:1px solid #152a3e;">
                  <td class="font-mono font-bold text-[11px]" style="color:#d0dde8;">{{ p.numero }}</td>
                  <td class="font-mono text-[11px]" style="color:#8ea9bf;">{{ fmtFecha(p.periodo_desde) }} → {{ fmtFecha(p.periodo_hasta) }}</td>

                  <td class="font-mono text-[11px]" style="color:#c8dae7;">{{ fmtBs(p.monto_certificado) }}</td>
                  <td class="font-mono text-[11px]" :style="{ color: p.retencion_gcc > 0 ? '#f87171' : '#8ea9bf' }">{{ p.retencion_gcc > 0 ? fmtBs(p.retencion_gcc) : '—' }}</td>
                  <td class="font-mono text-[11px]" :style="{ color: p.multa > 0 ? '#f87171' : '#8ea9bf' }">{{ p.multa > 0 ? fmtBs(p.multa) : '—' }}</td>

                  <td class="font-mono text-[11px]" style="color:#c8dae7;">{{ p.amortizacion > 0 ? fmtBs(p.amortizacion) : '—' }}</td>
                  <td class="font-mono font-bold text-[11px]" style="color:#00c9a7;">{{ fmtBs(p.liquido_pagable) }}</td>

                  <td class="font-mono text-[11px]" style="color:#c8dae7;">{{ fmtBs(p.saldo_anticipo_por_amortizar) }}</td>

                  <td class="font-bold" :style="{ color: p.dias_atraso > 0 ? '#f87171' : '#8ea9bf' }">{{ p.dias_atraso > 0 ? p.dias_atraso : '—' }}</td>
                  <td class="font-mono text-[11px]" style="color:#8ea9bf;">{{ p.avance_fisico !== null ? p.avance_fisico.toFixed(2) + '%' : '—' }}</td>

                  <td class="font-mono text-[11px]" style="color:#c8dae7;">{{ p.importe_pagado_sigep > 0 ? fmtBs(p.importe_pagado_sigep) : '—' }}</td>
                  <td class="font-mono text-[11px]" :style="{ color: p.diferencia_lp_f !== 0 ? '#fbbf24' : '#8ea9bf' }">{{ fmtBs(p.diferencia_lp_f) }}</td>

                  <td class="font-mono text-[11px]" style="color:#8ea9bf;">{{ p.numero_c31 || '—' }}</td>
                  <td class="font-mono text-[11px]" style="color:#c8dae7;">{{ p.monto_c31 > 0 ? fmtBs(p.monto_c31) : '—' }}</td>
                  <td class="font-mono text-[11px]" :style="{ color: p.diferencia_sigep_c31 !== 0 ? '#fbbf24' : '#8ea9bf' }">{{ fmtBs(p.diferencia_sigep_c31) }}</td>

                  <td class="font-mono text-[11px]" style="color:#8ea9bf;">{{ fmtFecha(p.fecha_aprobacion_fiscal) }}</td>
                  <td class="font-mono text-[11px]" style="color:#8ea9bf;">{{ fmtFecha(p.fecha_elaboracion_planilla) }}</td>
                  <td class="font-mono text-[11px]" style="color:#8ea9bf;">{{ fmtFecha(p.fecha_desembolso) }}</td>

                  <td class="font-bold" :style="{ color: p.dias_demora > 90 ? '#f87171' : (p.dias_demora > 0 ? '#f59e0b' : '#8ea9bf') }">{{ p.dias_demora !== null && p.dias_demora !== undefined ? p.dias_demora : '—' }}</td>

                  <td>
                    <button style="color:#f87171;background:none;border:none;cursor:pointer;font-size:.85rem;" @click="eliminarPlanilla(p.id_planilla)" title="Eliminar planilla">✕</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </template>

    <NuevaPlanilla
      :show="mostrarModal"
      :id-contrato="idContratoSeleccionado"
      @close="mostrarModal = false"
      @created="alGuardarPlanilla"
    />
  </div>
</template>

<style scoped>
.planillas-table { min-width: 2300px; }
.planillas-table th { white-space: normal; line-height: 1.2; text-align: center; padding: 8px 6px; color: #8ea9bf; font-size: .62rem; text-transform: uppercase; }
.planillas-table td { padding: 8px 6px; text-align: center; white-space: nowrap; }
</style>