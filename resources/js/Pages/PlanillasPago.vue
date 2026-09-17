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
  <div class="p-5 planillas-page">

    <!-- ENCABEZADO -->
    <div class="page-header">
      <div class="page-header-icon">
        <i class="ti ti-receipt"></i>
      </div>
      <div>
        <h1>Planillas de Pago</h1>
        <p>Certificaciones, amortizaciones y desembolsos por contrato.</p>
      </div>
    </div>

    <div v-if="cargandoContratos" class="state-message">
      <i class="ti ti-loader-2 spinner"></i>
      <span>Cargando contratos...</span>
    </div>

    <div v-else-if="error" class="state-message state-error">
      <i class="ti ti-alert-circle"></i>
      <span>{{ error }}</span>
    </div>

    <div v-else-if="!contratos.length" class="state-message">
      <i class="ti ti-folder-off"></i>
      <span>Este proyecto todavía no tiene contratos registrados.</span>
    </div>

    <template v-else>

      <!-- SELECTOR DE CONTRATO -->
      <div class="selector-card">
        <div class="selector-label">
          <i class="ti ti-file-invoice"></i>
          Contrato / paquete
        </div>
        <select v-model="idContratoSeleccionado" class="selector-input">
          <option v-for="c in contratos" :key="c.id_contrato" :value="c.id_contrato">
            N°{{ c.numero }} — {{ c.tipo_contrato }} — {{ c.contratista }}
          </option>
        </select>
      </div>

      <template v-if="contratoSeleccionado">

        <!-- TARJETAS DE RESUMEN -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon" style="color:#00c9a7;background:rgba(0,201,167,.1);">
              <i class="ti ti-cash"></i>
            </div>
            <div>
              <div class="stat-label">Monto ejecutado acum.</div>
              <div class="stat-value">{{ fmtBs(Number(contratoSeleccionado.monto_ejecutado_acumulado)) }}</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon" style="color:#55b8ef;background:rgba(77,179,240,.1);">
              <i class="ti ti-wallet"></i>
            </div>
            <div>
              <div class="stat-label">Anticipo otorgado</div>
              <div class="stat-value">{{ fmtBs(Number(contratoSeleccionado.anticipo)) }}</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon" :style="{ color: demoraPromedio > 0 ? '#f87171' : '#8ea9bf', background: demoraPromedio > 0 ? 'rgba(248,113,113,.1)' : 'rgba(142,169,191,.1)' }">
              <i class="ti ti-clock-exclamation"></i>
            </div>
            <div>
              <div class="stat-label">Demora promedio de pago</div>
              <div class="stat-value" :style="{ color: demoraPromedio > 0 ? '#f87171' : '#c8dae7' }">
                {{ demoraPromedio !== null ? `${demoraPromedio} días` : '—' }}
              </div>
            </div>
          </div>
        </div>

        <!-- TABLA DE PLANILLAS -->
        <div class="table-card">
          <div class="table-card-header">
            <div>
              <div class="table-card-title">
                <i class="ti ti-list-details"></i>
                Paquete {{ contratoSeleccionado.numero }} — {{ contratoSeleccionado.contratista }}
              </div>
              <div class="table-card-legend">
                (A) Importe Ejecutado &nbsp;·&nbsp; (B) Retenciones &nbsp;·&nbsp; (C) Multas &nbsp;·&nbsp;
                (E) Amort. Anticipo &nbsp;·&nbsp; (F) Pagado SIGEP &nbsp;·&nbsp; (G) Monto C-31
              </div>
            </div>
            <button class="btn-nueva" @click="mostrarModal = true">
              <i class="ti ti-plus"></i> Nueva planilla
            </button>
          </div>

          <div v-if="cargandoPlanillas" class="state-message">
            <i class="ti ti-loader-2 spinner"></i>
            <span>Cargando planillas...</span>
          </div>

          <div v-else-if="!planillas.length" class="state-message">
            <i class="ti ti-file-off"></i>
            <span>Este contrato todavía no tiene planillas registradas.</span>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="planillas-table">
              <thead>
                <tr>
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
                <tr v-for="p in planillas" :key="p.id_planilla">
                  <td class="col-acciones">
                    <button class="btn-accion btn-editar" title="Editar planilla" @click="abrirEditar(p)">
                      <i class="ti ti-pencil"></i>
                    </button>
                    <button class="btn-accion btn-eliminar" title="Eliminar planilla" @click="eliminarPlanilla(p.id_planilla)">
                      <i class="ti ti-trash"></i>
                    </button>
                  </td>
                  <td class="col-numero">{{ p.numero }}</td>
                  <td class="col-fecha">{{ fmtFecha(p.periodo_desde) }} → {{ fmtFecha(p.periodo_hasta) }}</td>
                  <td class="money">{{ fmtBs(p.monto_certificado) }}</td>
                  <td class="money" :class="{ 'valor-alerta': p.retencion_gcc > 0 }">{{ p.retencion_gcc > 0 ? fmtBs(p.retencion_gcc) : '—' }}</td>
                  <td class="money" :class="{ 'valor-alerta': p.multa > 0 }">{{ p.multa > 0 ? fmtBs(p.multa) : '—' }}</td>
                  <td class="money">{{ p.amortizacion > 0 ? fmtBs(p.amortizacion) : '—' }}</td>
                  <td class="money col-destacada">{{ fmtBs(p.liquido_pagable) }}</td>
                  <td class="money">{{ fmtBs(p.saldo_anticipo_por_amortizar) }}</td>
                  <td class="col-centro" :class="{ 'valor-alerta': p.dias_atraso > 0 }">{{ p.dias_atraso > 0 ? p.dias_atraso : '—' }}</td>
                  <td class="col-fecha">{{ p.avance_fisico !== null ? p.avance_fisico.toFixed(2) + '%' : '—' }}</td>
                  <td class="money">{{ p.importe_pagado_sigep > 0 ? fmtBs(p.importe_pagado_sigep) : '—' }}</td>
                  <td class="money" :class="{ 'valor-warning': p.diferencia_lp_f !== 0 }">{{ fmtBs(p.diferencia_lp_f) }}</td>
                  <td class="col-fecha">{{ p.numero_c31 || '—' }}</td>
                  <td class="money">{{ p.monto_c31 > 0 ? fmtBs(p.monto_c31) : '—' }}</td>
                  <td class="money" :class="{ 'valor-warning': p.diferencia_sigep_c31 !== 0 }">{{ fmtBs(p.diferencia_sigep_c31) }}</td>
                  <td class="col-fecha">{{ fmtFecha(p.fecha_aprobacion_fiscal) }}</td>
                  <td class="col-fecha">{{ fmtFecha(p.fecha_elaboracion_planilla) }}</td>
                  <td class="col-fecha">{{ fmtFecha(p.fecha_desembolso) }}</td>
                  <td
                    class="col-centro"
                    :class="{ 'valor-alerta': p.dias_demora > 90, 'valor-warning': p.dias_demora > 0 && p.dias_demora <= 90 }"
                  >
                    {{ p.dias_demora !== null && p.dias_demora !== undefined ? p.dias_demora : '—' }}
                  </td>
                </tr>

                <tr class="total-row">
                  <td colspan="3">TOTAL</td>
                  <td class="money">{{ fmtBs(totalCertificado) }}</td>
                  <td class="money">{{ totalRetencion > 0 ? fmtBs(totalRetencion) : '—' }}</td>
                  <td class="money">{{ totalMultas > 0 ? fmtBs(totalMultas) : '—' }}</td>
                  <td class="money">{{ totalAmortizacion > 0 ? fmtBs(totalAmortizacion) : '—' }}</td>
                  <td class="money" style="color:#00c9a7;">{{ fmtBs(totalLiquido) }}</td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td class="money">{{ totalPagadoSigep > 0 ? fmtBs(totalPagadoSigep) : '—' }}</td>
                  <td class="money">{{ fmtBs(totalDiferenciaLpF) }}</td>
                  <td></td>
                  <td class="money">{{ totalMontoC31 > 0 ? fmtBs(totalMontoC31) : '—' }}</td>
                  <td class="money">{{ fmtBs(totalDiferenciaSigepC31) }}</td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td class="col-centro">{{ demoraPromedio !== null ? demoraPromedio + ' d.' : '—' }}</td>
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

    <Transition name="modal-fade">
      <EditarPlanilla
        v-if="planillaEditando"
        :key="planillaEditando.id_planilla"
        :planilla="planillaEditando"
        @close="planillaEditando = null"
        @updated="alGuardarPlanilla"
      />
    </Transition>
  </div>
</template>

<style scoped>
.planillas-page { max-width: 1900px; margin: 0 auto; }

/* --- Encabezado --- */
.page-header {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 20px;
}
.page-header-icon {
  width: 42px;
  height: 42px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: rgba(0, 201, 167, .1);
  border: 1px solid rgba(0, 201, 167, .25);
  color: #00c9a7;
  font-size: 20px;
}
.page-header h1 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 800;
  color: #f2fbff;
}
.page-header p {
  margin: 2px 0 0;
  color: #8ea9bf;
  font-size: .8rem;
}

/* --- Estados (cargando / error / vacío) --- */
.state-message {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 44px 20px;
  color: #8ea9bf;
  font-size: .85rem;
  text-align: center;
}
.state-message i { font-size: 18px; }
.state-error { color: #fca5a5; }
.spinner { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* --- Selector de contrato --- */
.selector-card {
  border-radius: 12px;
  background-color: #0d1f30;
  border: 1px solid #1e3a52;
  padding: 16px;
  margin-bottom: 16px;
}
.selector-label {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #8ea9bf;
  font-size: .68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .04em;
  margin-bottom: 8px;
}
.selector-input {
  width: 100%;
  max-width: 520px;
  background: #091520;
  border: 1px solid #1e3a52;
  border-radius: 8px;
  padding: 10px 12px;
  color: #dcebf5;
  font-size: .85rem;
}

/* --- Tarjetas de resumen --- */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 20px;
}
.stat-card {
  display: flex;
  align-items: center;
  gap: 12px;
  border-radius: 12px;
  background-color: #0d1f30;
  border: 1px solid #1e3a52;
  padding: 14px 16px;
}
.stat-icon {
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  font-size: 18px;
}
.stat-label {
  color: #8ea9bf;
  font-size: .68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .04em;
}
.stat-value {
  color: #d0dde8;
  font-weight: 800;
  font-size: 1.15rem;
  margin-top: 2px;
}

/* --- Tarjeta de la tabla --- */
.table-card {
  border-radius: 12px;
  overflow: hidden;
  background-color: #0d1f30;
  border: 1px solid #1e3a52;
}
.table-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  padding: 16px 20px;
  border-bottom: 1px solid #19354d;
  flex-wrap: wrap;
}
.table-card-title {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #f2fbff;
  font-weight: 700;
  font-size: .92rem;
}
.table-card-legend {
  margin-top: 6px;
  color: #647a8e;
  font-size: .68rem;
  line-height: 1.5;
}

.btn-nueva {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border: none;
  border-radius: 8px;
  background: linear-gradient(135deg, #00d0ae, #00aa91);
  color: #052029;
  font-weight: 700;
  font-size: .8rem;
  cursor: pointer;
  box-shadow: 0 6px 16px rgba(0, 201, 167, .16);
  transition: .15s;
  flex-shrink: 0;
}
.btn-nueva:hover { filter: brightness(1.07); transform: translateY(-1px); }

/* --- Tabla --- */
.planillas-table {
  width: 100%;
  min-width: 2350px;
  border-collapse: collapse;
  font-size: .78rem;
}
.planillas-table thead tr {
  background: #0a1826;
  border-bottom: 1px solid #1e3a52;
}
.planillas-table th {
  position: sticky;
  top: 0;
  z-index: 3;
  background: #0a1826;
  white-space: normal;
  line-height: 1.25;
  text-align: center;
  padding: 10px 8px;
  color: #9cb5c6;
  font-size: .64rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .03em;
}
.planillas-table tbody tr {
  border-bottom: 1px solid #152a3e;
  transition: background .15s;
}
.planillas-table tbody tr:hover {
  background: rgba(0, 201, 167, .04);
}
.planillas-table td {
  padding: 10px 8px;
  text-align: center;
  white-space: nowrap;
  color: #b9cadb;
}

.col-numero {
  font-weight: 800;
  color: #00c9a7;
  font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
}
.col-fecha {
  color: #8ea9bf;
  font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
  font-size: .74rem;
}
.col-centro {
  font-weight: 700;
  color: #8ea9bf;
}
.col-destacada {
  font-weight: 800;
  color: #00c9a7 !important;
}

.money {
  font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
  color: #e4f0f7;
  font-weight: 600;
  text-align: right !important;
}

.valor-alerta { color: #f87171 !important; }
.valor-warning { color: #fbbf24 !important; }

.col-acciones-th {
  position: sticky;
  left: 0;
  top: 0;
  z-index: 4;
  background: #0a1826;
}
.col-acciones {
  position: sticky;
  left: 0;
  z-index: 2;
  background: #0d1f30;
  display: flex;
  gap: 6px;
  justify-content: center;
}
.planillas-table tbody tr:hover .col-acciones {
  background: #0f2536;
}

.btn-accion {
  width: 27px;
  height: 27px;
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
  position: sticky;
  bottom: 0;
  background: #091520 !important;
  border-top: 2px solid #1e3a52;
  color: #f2fbff !important;
  font-weight: 800;
}

@media (max-width: 900px) {
  .stats-grid { grid-template-columns: 1fr; }
  .table-card-header { flex-direction: column; align-items: stretch; }
  .btn-nueva { justify-content: center; }
}
</style>