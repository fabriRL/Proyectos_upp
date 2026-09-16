<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { useUtils } from '@/composables/useUtils.js'
import { useToast } from '@/composables/useToast.js'
import NuevoContrato from './NuevoContrato.vue'
import EditarContrato from './EditarContrato.vue'

const route = useRoute()
const codigoProyecto = route.params.codigo

const { badgeClass, fillClass, fmtBs } = useUtils()
const { showToast } = useToast()

const rows = ref([])
const rawContratos = ref([])
const cargando = ref(true)
const error = ref(null)
const mostrarModal = ref(false)
const contratoEditando = ref(null)

const meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic']
const fmtFecha = (fecha) => {
  if (!fecha) return '—'
  const soloFecha = String(fecha).split('T')[0]
  const d = new Date(soloFecha + 'T00:00:00')
  if (isNaN(d.getTime())) return '—'
  return `${d.getDate()}-${meses[d.getMonth()]}-${String(d.getFullYear()).slice(2)}`
}

const mapContrato = (c) => ({
  id: c.id_contrato,
  n: c.numero,
  paquete: c.tipo_contrato,
  empresa: c.contratista,
  minuta: c.numero_minuta || '—',
  fechaFirma: fmtFecha(c.fecha_firma_contrato),
  fechaOrdenProceder: fmtFecha(c.fecha_orden_proceder),
  plazoDias: c.plazo_dias ?? null,
  pdfUrl: c.archivo_orden_proceder_url || null,
  monto: Number(c.monto_vigente),
  anticipo: Number(c.anticipo),
  amortizacion: Number(c.amortizacion_acumulada),
  ejecutado: Number(c.monto_ejecutado_acumulado),
  liquido: Number(c.liquido_pagable_acumulado),
  multas: Number(c.multas),
  retencion: Number(c.retencion_gcc),
  descuentos: Number(c.total_descuentos),
  saldo: Number(c.saldo_por_pagar),
  contractual: c.estado_contractual,
  conclusion: fmtFecha(c.fecha_conclusion_prevista),
  provisional: fmtFecha(c.fecha_entrega_provisional),
  definitiva: fmtFecha(c.fecha_entrega_definitiva),
  af: Number(c.avance_fisico),
  afin: Number(c.avance_financiero),
  estado: c.estado_fisico,
  activo: c.activo,
})

const cargar = async () => {
  cargando.value = true
  error.value = null
  try {
    const { data } = await axios.get(`/api/proyectos/${codigoProyecto}/contratos`)
    rawContratos.value = data.contratos
    rows.value = data.contratos.map(mapContrato)
  } catch (e) {
    error.value = 'No se pudieron cargar los contratos.'
    console.error(e)
  } finally {
    cargando.value = false
  }
}

onMounted(cargar)

function abrirEditar(idContrato) {
  contratoEditando.value = rawContratos.value.find(c => c.id_contrato === idContrato) ?? null
}

async function toggleActivoContrato(idContrato, activoActual) {
  const accion = activoActual ? 'desactivar' : 'activar'
  if (!confirm(`¿Seguro que quieres ${accion} este contrato?`)) return
  try {
    await axios.patch(`/api/contratos/${idContrato}/activo`)
    await cargar()
    showToast(`Contrato ${activoActual ? 'desactivado' : 'activado'} correctamente.`, 'success')
  } catch (e) {
    console.error(e)
    showToast('No se pudo cambiar el estado del contrato.', 'error')
  }
}

const sum = key => rows.value.reduce((total, row) => total + row[key], 0)
const montoVigente = computed(() => sum('monto'))
const afPromedio = computed(() =>
  rows.value.length ? (sum('af') / rows.value.length).toFixed(2) : '0.00'
)
const afinPromedio = computed(() =>
  rows.value.length ? (sum('afin') / rows.value.length).toFixed(2) : '0.00'
)
</script>

<template>
  <div class="p-5 contracts-page">
    <div v-if="cargando" class="text-center py-10" style="color:#8ea9bf;">
      Cargando contratos...
    </div>

    <div v-else-if="error" class="text-center py-10" style="color:#f87171;">
      {{ error }}
      <button class="btn btn-sm btn-ghost ml-2" @click="cargar">Reintentar</button>
    </div>

    <template v-else>
      <div class="grid grid-cols-3 gap-3 mb-5">
        <div
          v-for="[label, value, color] in [
            ['Monto vigente', fmtBs(montoVigente), '#d0dde8'],
            ['Monto ejecutado', fmtBs(sum('ejecutado')), '#00c9a7'],
            ['Saldo por pagar', fmtBs(sum('saldo')), '#fbbf24'],
          ]"
          :key="label"
          class="rounded-xl p-4"
          style="background-color:#0d1f30; border:1px solid #1e3a52;"
        >
          <div class="field-label">{{ label }}</div>
          <div class="font-bold text-xl mt-1" :style="{ color }">{{ value }}</div>
        </div>
      </div>

      <div class="rounded-xl overflow-hidden" style="background-color:#0d1f30; border:1px solid #1e3a52;">
        <div class="px-5 py-3 flex justify-between items-center gap-3" style="border-bottom:1px solid #19354d;">
          <div>
            <div class="section-title" style="margin-bottom:0;">Resumen de gestión del proyecto por contrato</div>
            <p class="table-subtitle">
              Ejecución financiera, retenciones, saldos e hitos de recepción por paquete. Solo los contratos activos suman al resumen financiero.
            </p>
            <p class="table-legend">
              <i class="ti ti-info-circle"></i>
              Multas, Retención G.C.C. y Total Descuentos se calculan automáticamente a partir de las Planillas registradas — no se editan directamente aquí.
            </p>
          </div>
          <div class="flex gap-2">
            <button
              class="btn btn-sm"
              style="background:#00c9a7;color:#04211c;font-weight:600;"
              @click="mostrarModal = true"
            >
              <i class="ti ti-plus"></i> Nuevo contrato
            </button>
            <button class="btn btn-sm btn-ghost"><i class="ti ti-download"></i> Exportar</button>
          </div>
        </div>

        <div v-if="rows.length === 0" class="text-center py-10" style="color:#8ea9bf;">
          No hay contratos registrados para este proyecto.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="contracts-table">
            <thead>
              <tr>
                <th class="col-acciones-th" rowspan="2">Acciones</th>
                <th rowspan="2">N°</th>
                <th rowspan="2">Contratista / supervisión</th>
                <th rowspan="2">Tipo de contrato</th>
                <th rowspan="2">N° Minuta</th>
                <th rowspan="2">Fecha Firma</th>
                <th rowspan="2">Orden de Proceder</th>
                <th rowspan="2">PDF</th>
                <th rowspan="2">Plazo (días)</th>
                <th rowspan="2">Monto vigente</th>
                <th rowspan="2">Anticipo</th>
                <th rowspan="2">Amort. Acumulada</th>
                <th rowspan="2">Monto Ejecutado</th>
                <th rowspan="2">Líquido Pagable</th>
                <th colspan="3" class="group-header-planillas">
                  Retenciones y Descuentos
                  <span class="group-tag">Desde Planillas</span>
                </th>
                <th rowspan="2">Saldo por Pagar</th>
                <th rowspan="2">Estado Contractual</th>
                <th rowspan="2">Conclusión Prevista</th>
                <th rowspan="2">Entrega Provisional</th>
                <th rowspan="2">Entrega Definitiva</th>
                <th rowspan="2">Avance Físico</th>
                <th rowspan="2">Avance Financiero</th>
                <th rowspan="2">Estado Físico</th>
              </tr>
              <tr>
                <th class="sub-header-planillas">Multas (Bs)</th>
                <th class="sub-header-planillas">Retención G.C.C. (Bs)</th>
                <th class="sub-header-planillas">Total Descuentos (Bs)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in rows" :key="c.id" :class="{ 'fila-inactiva': !c.activo }">
                <td class="col-acciones">
                  <button class="btn-accion btn-editar" title="Editar contrato" @click="abrirEditar(c.id)">
                    <i class="ti ti-pencil"></i>
                  </button>
                  <button
                    class="btn-accion"
                    :class="c.activo ? 'btn-desactivar' : 'btn-activar'"
                    :title="c.activo ? 'Desactivar contrato' : 'Activar contrato'"
                    @click="toggleActivoContrato(c.id, c.activo)"
                  >
                    <i :class="c.activo ? 'ti ti-toggle-right' : 'ti ti-toggle-left'"></i>
                  </button>
                </td>
                <td class="col-numero">
                  {{ c.n }}
                  <span v-if="!c.activo" class="badge-inactivo">Inactivo</span>
                </td>
                <td class="text-left col-empresa">{{ c.empresa }}</td>
                <td class="text-left">{{ c.paquete }}</td>
                <td>{{ c.minuta }}</td>
                <td>{{ c.fechaFirma }}</td>
                <td>{{ c.fechaOrdenProceder }}</td>
                <td>
                  <a v-if="c.pdfUrl" :href="c.pdfUrl" target="_blank" class="pdf-link">
                    <i class="ti ti-file-type-pdf"></i> Ver
                  </a>
                  <span v-else style="color:#4d6478;">—</span>
                </td>
                <td class="col-plazo">{{ c.plazoDias !== null ? c.plazoDias + ' días' : '—' }}</td>

                <td v-for="key in ['monto','anticipo','amortizacion','ejecutado','liquido']" :key="key" class="money">
                  {{ fmtBs(c[key]) }}
                </td>

                <td class="money col-planilla">{{ fmtBs(c.multas) }}</td>
                <td class="money col-planilla">{{ fmtBs(c.retencion) }}</td>
                <td class="money col-planilla">{{ fmtBs(c.descuentos) }}</td>

                <td class="money" :class="{ negative: c.saldo < 0 }">{{ fmtBs(c.saldo) }}</td>

                <td><span :class="badgeClass(c.contractual)">{{ c.contractual }}</span></td>
                <td>{{ c.conclusion }}</td>
                <td>{{ c.provisional }}</td>
                <td>{{ c.definitiva }}</td>

                <td class="percent">
                  <b>{{ c.af.toFixed(2) }}%</b>
                  <div class="prog-bar"><div class="prog-fill" :class="fillClass(c.af)" :style="{ width: c.af + '%' }" /></div>
                </td>
                <td class="percent">
                  <b>{{ c.afin.toFixed(2) }}%</b>
                  <div class="prog-bar"><div class="prog-fill" :class="fillClass(c.afin)" :style="{ width: c.afin + '%' }" /></div>
                </td>
                <td><span :class="badgeClass(c.estado)">{{ c.estado }}</span></td>
              </tr>

              <tr class="total-row">
                <td colspan="9">TOTAL</td>
                <td v-for="key in ['monto','anticipo','amortizacion','ejecutado','liquido']" :key="key" class="money">
                  {{ fmtBs(sum(key)) }}
                </td>
                <td class="money col-planilla">{{ fmtBs(sum('multas')) }}</td>
                <td class="money col-planilla">{{ fmtBs(sum('retencion')) }}</td>
                <td class="money col-planilla">{{ fmtBs(sum('descuentos')) }}</td>
                <td class="money">{{ fmtBs(sum('saldo')) }}</td>
                <td colspan="4"></td>
                <td>{{ afPromedio }}%</td>
                <td>{{ afinPromedio }}%</td>
                <td></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <NuevoContrato
      :show="mostrarModal"
      :codigo-proyecto="codigoProyecto"
      @close="mostrarModal = false"
      @created="cargar"
    />

    <EditarContrato
      v-if="contratoEditando"
      :key="contratoEditando.id_contrato"
      :contrato="contratoEditando"
      @close="contratoEditando = null"
      @updated="cargar"
    />
  </div>
</template>

<style scoped>
.contracts-page { max-width: 1780px; margin: auto; }

.table-subtitle { font-size: .75rem; color: #8ea9bf; margin: 4px 0 0; }

.table-legend {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: .7rem;
  color: #55b8ef;
  margin: 6px 0 0;
}
.table-legend i { font-size: .85rem; flex-shrink: 0; }

.contracts-table {
  width: 100%;
  min-width: 2850px;
  border-collapse: collapse;
  font-size: .82rem;
}

.contracts-table thead tr { background: #0a1826; border-bottom: 1px solid #1e3a52; }

.contracts-table th {
  white-space: nowrap;
  text-align: center;
  padding: 12px 12px;
  color: #9cb5c6;
  font-size: .68rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .04em;
  vertical-align: middle;
}

.col-acciones-th { position: sticky; left: 0; z-index: 2; background: #0a1826; }

/* --- Grupo "Retenciones y Descuentos" (viene de Planillas) --- */
.group-header-planillas {
  background: rgba(85, 184, 239, .12) !important;
  color: #55b8ef !important;
  border-left: 1px solid rgba(85, 184, 239, .3);
  border-right: 1px solid rgba(85, 184, 239, .3);
}
.group-tag {
  display: block;
  margin-top: 3px;
  font-size: .58rem;
  font-weight: 700;
  text-transform: none;
  letter-spacing: 0;
  color: #55b8ef;
  opacity: .85;
}
.sub-header-planillas {
  background: rgba(85, 184, 239, .07) !important;
  color: #7fc4ef !important;
  border-left: 1px solid rgba(85, 184, 239, .18);
  border-right: 1px solid rgba(85, 184, 239, .18);
}

.contracts-table tbody tr { border-bottom: 1px solid #152a3e; transition: background .15s, opacity .15s; }
.contracts-table tbody tr:hover { background: rgba(0, 201, 167, .045); }
.contracts-table tbody tr:last-child { border-bottom: 0; }

.fila-inactiva { opacity: .5; }
.fila-inactiva:hover { opacity: .75; }

.contracts-table td {
  padding: 13px 12px;
  text-align: center;
  color: #b9cadb;
  white-space: nowrap;
  font-size: .85rem;
}

.contracts-table td.text-left { text-align: left; }

/* Celdas del cuerpo que pertenecen al grupo de Planillas */
.col-planilla {
  background: rgba(85, 184, 239, .045);
  border-left: 1px solid rgba(85, 184, 239, .12);
  border-right: 1px solid rgba(85, 184, 239, .12);
}
.total-row .col-planilla {
  background: rgba(85, 184, 239, .1) !important;
}

.col-acciones {
  position: sticky;
  left: 0;
  z-index: 1;
  background: #0d1f30;
  display: flex;
  gap: 6px;
  justify-content: center;
}

.fila-inactiva .col-acciones { background: #0d1f30; }
.contracts-table tbody tr:hover .col-acciones { background: #0f2536; }

.btn-accion {
  width: 28px;
  height: 28px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  border: 1px solid transparent;
  cursor: pointer;
  font-size: 13px;
  transition: .15s;
}

.btn-editar { background: rgba(77, 179, 240, .10); border-color: rgba(77, 179, 240, .22); color: #55b8ef; }
.btn-editar:hover { background: rgba(77, 179, 240, .2); border-color: rgba(77, 179, 240, .35); }

.btn-desactivar { background: rgba(251, 191, 36, .1); border-color: rgba(251, 191, 36, .25); color: #fbbf24; }
.btn-desactivar:hover { background: rgba(251, 191, 36, .2); border-color: rgba(251, 191, 36, .4); }

.btn-activar { background: rgba(0, 201, 167, .1); border-color: rgba(0, 201, 167, .25); color: #00c9a7; }
.btn-activar:hover { background: rgba(0, 201, 167, .2); border-color: rgba(0, 201, 167, .4); }

.col-numero { font-weight: 800; color: #00c9a7; font-size: .9rem; }

.badge-inactivo {
  display: block;
  margin-top: 3px;
  padding: 1px 6px;
  border-radius: 4px;
  background: rgba(251, 191, 36, .12);
  border: 1px solid rgba(251, 191, 36, .3);
  color: #fbbf24;
  font-size: .58rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .03em;
}

.col-empresa {
  color: #e4f0f7;
  font-weight: 700;
  min-width: 180px;
  white-space: normal;
  line-height: 1.3;
}

.col-plazo { font-weight: 700; color: #fbbf24; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; }

.pdf-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: #00c9a7;
  font-weight: 700;
  text-decoration: none;
  font-size: .82rem;
}
.pdf-link:hover { text-decoration: underline; }

.money {
  text-align: right !important;
  font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
  font-size: .84rem;
  color: #e4f0f7 !important;
  font-weight: 600;
}

.negative { color: #f87171 !important; }

.percent { min-width: 100px; }
.percent b { display: block; font-size: .82rem; color: #e4f0f7; margin-bottom: 5px; font-weight: 700; }
.percent .prog-bar { width: 84px; height: 6px; margin: 0 auto; border-radius: 999px; background: #152a3e; overflow: hidden; }

.total-row td {
  background: #091520 !important;
  border-top: 2px solid #1e3a52;
  color: #f2fbff !important;
  font-weight: 800;
  font-size: .86rem;
}

@media (max-width: 700px) {
  .contracts-page { padding: 14px; }
  .grid { grid-template-columns: 1fr !important; }
}
</style>