<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { useUtils } from '@/composables/useUtils.js'
import { useToast } from '@/composables/useToast.js'
import { useConfirm } from '@/composables/useConfirm.js'
import NuevaPlanilla from './NuevaPlanilla.vue'
import EditarPlanilla from './EditarPlanilla.vue'

const route = useRoute()
const codigoProyecto = route.params.codigo
const { fmtBs } = useUtils()
const { showToast } = useToast()
const { confirmar } = useConfirm()

const contratos = ref([])
const idContratoSeleccionado = ref(null)
const planillas = ref([])
const cargandoContratos = ref(true)
const cargandoPlanillas = ref(false)
const error = ref(null)
const mostrarModal = ref(false)
const planillaEditando = ref(null)

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

const demoraPromedio = computed(() => {
  const conDemora = planillas.value.filter(p => p.dias_demora !== null && p.dias_demora !== undefined)
  if (!conDemora.length) return null
  const total = conDemora.reduce((sum, p) => sum + p.dias_demora, 0)
  return Math.round(total / conDemora.length)
})

const totalCertificado = computed(() => planillas.value.reduce((s, p) => s + p.monto_certificado, 0))
const totalRetencion = computed(() => planillas.value.reduce((s, p) => s + p.retencion_gcc, 0))
const totalMultas = computed(() => planillas.value.reduce((s, p) => s + p.multa, 0))
const totalAmortizacion = computed(() => planillas.value.reduce((s, p) => s + p.amortizacion, 0))
const totalLiquido = computed(() => planillas.value.reduce((s, p) => s + p.liquido_pagable, 0))
const totalPagadoSigep = computed(() => planillas.value.reduce((s, p) => s + p.importe_pagado_sigep, 0))
const totalDiferenciaLpF = computed(() => planillas.value.reduce((s, p) => s + p.diferencia_lp_f, 0))
const totalMontoC31 = computed(() => planillas.value.reduce((s, p) => s + p.monto_c31, 0))
const totalDiferenciaSigepC31 = computed(() => planillas.value.reduce((s, p) => s + p.diferencia_sigep_c31, 0))

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
  const ok = await confirmar({
    title: 'Eliminar planilla',
    message: '¿Eliminar esta planilla? Esto recalculará los totales del contrato. Esta acción no se puede deshacer.',
    confirmText: 'Sí, eliminar',
  })
  if (!ok) return

  try {
    await axios.delete(`/api/planillas/${idPlanilla}`)
    await Promise.all([cargarPlanillas(), cargarContratos()])
    showToast('Planilla eliminada correctamente.', 'success')
  } catch (e) {
    console.error(e)
    showToast('No se pudo eliminar la planilla.', 'error')
  }
}

function abrirEditar(p) {
  planillaEditando.value = p
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
                  <th class="col-acciones-th">Acciones</th>
                  <th>N°</th><th>Periodo</th>
                  <th>Importe Ejecutado (A)</th><th>Retenciones (B)</th><th>Multas (C)</th>
                  <th>Amort. Anticipo (E)</th><th>Líquido Pagable (LP)</th>
                  <th>Saldo Anticipo por Amortizar</th>
                  <th>Días atraso</th><th>Avance físico</th>
                  <th>Pagado SIGEP (F)</th><th>Dif. (LP−F)</th>
                  <th>N° C-31</th><th>Monto C-31 (G)</th><th>Dif. (SIGEP−C31)</th>
                  <th>Aprob. Fiscal</th><th>Elab. Planilla</th><th>Desembolso</th>
                  <th>Días demora</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in planillas" :key="p.id_planilla" style="border-bottom:1px solid #152a3e;">
                  <td class="col-acciones">
                    <button class="btn-accion btn-editar" title="Editar planilla" @click="abrirEditar(p)">
                      <i class="ti ti-pencil"></i>
                    </button>
                    <button class="btn-accion btn-eliminar" title="Eliminar planilla" @click="eliminarPlanilla(p.id_planilla)">
                      <i class="ti ti-trash"></i>
                    </button>
                  </td>
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
                </tr>

                <tr class="total-row">
                  <td colspan="3">TOTAL</td>
                  <td class="font-mono text-[11px]">{{ fmtBs(totalCertificado) }}</td>
                  <td class="font-mono text-[11px]">{{ totalRetencion > 0 ? fmtBs(totalRetencion) : '—' }}</td>
                  <td class="font-mono text-[11px]">{{ totalMultas > 0 ? fmtBs(totalMultas) : '—' }}</td>
                  <td class="font-mono text-[11px]">{{ totalAmortizacion > 0 ? fmtBs(totalAmortizacion) : '—' }}</td>
                  <td class="font-mono font-bold text-[11px]" style="color:#00c9a7;">{{ fmtBs(totalLiquido) }}</td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td class="font-mono text-[11px]">{{ totalPagadoSigep > 0 ? fmtBs(totalPagadoSigep) : '—' }}</td>
                  <td class="font-mono text-[11px]">{{ fmtBs(totalDiferenciaLpF) }}</td>
                  <td></td>
                  <td class="font-mono text-[11px]">{{ totalMontoC31 > 0 ? fmtBs(totalMontoC31) : '—' }}</td>
                  <td class="font-mono text-[11px]">{{ fmtBs(totalDiferenciaSigepC31) }}</td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td class="font-bold">{{ demoraPromedio !== null ? demoraPromedio + ' d.' : '—' }}</td>
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

    <EditarPlanilla
      v-if="planillaEditando"
      :key="planillaEditando.id_planilla"
      :planilla="planillaEditando"
      @close="planillaEditando = null"
      @updated="alGuardarPlanilla"
    />
  </div>
</template>

<style scoped>
.planillas-table { min-width: 2350px; }
.planillas-table th { white-space: normal; line-height: 1.2; text-align: center; padding: 8px 6px; color: #8ea9bf; font-size: .62rem; text-transform: uppercase; }
.planillas-table td { padding: 8px 6px; text-align: center; white-space: nowrap; }
.col-acciones-th { position: sticky; left: 0; z-index: 2; background: #0d1f30; }
.col-acciones {
  position: sticky;
  left: 0;
  z-index: 1;
  background: #0d1f30;
  display: flex;
  gap: 6px;
  justify-content: center;
}
.btn-accion {
  width: 26px;
  height: 26px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  border: 1px solid transparent;
  cursor: pointer;
  font-size: 12px;
  transition: .15s;
}
.btn-editar { background: rgba(77, 179, 240, .10); border-color: rgba(77, 179, 240, .22); color: #55b8ef; }
.btn-editar:hover { background: rgba(77, 179, 240, .2); border-color: rgba(77, 179, 240, .35); }
.btn-eliminar { background: rgba(248, 113, 113, .08); border-color: rgba(248, 113, 113, .18); color: #f87171; }
.btn-eliminar:hover { background: rgba(248, 113, 113, .16); border-color: rgba(248, 113, 113, .3); }
.total-row td {
  background: #091520 !important;
  border-top: 2px solid #1e3a52;
  color: #f2fbff !important;
  font-weight: 800;
}
</style>