<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/lib/axios'
import { useToast } from '@/composables/useToast.js'
import { useConfirm } from '@/composables/useConfirm.js'
import NuevaModificacion from './NuevaModificacion.vue'
import EditarModificacion from './EditarModificacion.vue'

const route = useRoute()
const codigoProyecto = route.params.codigo
const { showToast } = useToast()
const { confirmar } = useConfirm()

const contratos = ref([])
const idContratoSeleccionado = ref(null)
const modificaciones = ref([])
const cargandoContratos = ref(true)
const cargandoModificaciones = ref(false)
const error = ref(null)
const mostrarModal = ref(false)
const modificacionEditando = ref(null)

const contratoSeleccionado = computed(() =>
  contratos.value.find(c => c.id_contrato === idContratoSeleccionado.value) ?? null
)

const fechaConclusionVigente = computed(() =>
  contratoSeleccionado.value?.fecha_conclusion_prevista ?? null
)

const numeroMasReciente = computed(() =>
  modificaciones.value.length ? Math.max(...modificaciones.value.map(m => m.numero)) : null
)

const meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic']
function fmtFecha(fecha) {
  if (!fecha) return '—'
  const soloFecha = String(fecha).split('T')[0]
  const d = new Date(soloFecha + 'T00:00:00')
  if (isNaN(d.getTime())) return '—'
  return `${d.getDate()}-${meses[d.getMonth()]}-${String(d.getFullYear()).slice(2)}`
}

function fmtBs(valor) {
  if (valor === null || valor === undefined) return '—'
  return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(valor))
}

function fmtPlazo(dias) {
  if (dias === null || dias === undefined) return { texto: '—', color: '#8ea9bf' }
  if (dias === 0) return { texto: '0 días', color: '#8ea9bf' }
  const signo = dias > 0 ? '+' : ''
  const color = dias > 0 ? '#fbbf24' : '#00c9a7'
  return { texto: `${signo}${dias} días`, color }
}

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

const cargarModificaciones = async () => {
  if (!idContratoSeleccionado.value) {
    modificaciones.value = []
    return
  }
  cargandoModificaciones.value = true
  error.value = null
  try {
    const { data } = await axios.get(`/api/contratos/${idContratoSeleccionado.value}/modificaciones`)
    modificaciones.value = data
  } catch (e) {
    error.value = 'No se pudieron cargar las modificaciones.'
    console.error(e)
  } finally {
    cargandoModificaciones.value = false
  }
}

const eliminarModificacion = async (id) => {
  const ok = await confirmar({
    title: 'Eliminar modificación',
    message: '¿Eliminar esta modificación contractual? También se borrará el PDF adjunto. Esta acción no se puede deshacer.',
    confirmText: 'Sí, eliminar',
  })
  if (!ok) return

  try {
    await axios.delete(`/api/modificaciones/${id}`)
    await cargarModificaciones()
    showToast('Modificación eliminada correctamente.', 'success')
  } catch (e) {
    console.error(e)
    showToast('No se pudo eliminar la modificación.', 'error')
  }
}

function abrirEditar(m) {
  modificacionEditando.value = m
}

function badgeColor(estado) {
  if (estado === 'Vigente' || estado === 'Registrado') return '#00c9a7'
  if (estado === 'Anulado') return '#f87171'
  return '#fbbf24'
}

async function alGuardarModificacion() {
  await cargarContratos()
  await cargarModificaciones()
}

watch(idContratoSeleccionado, cargarModificaciones)
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
        <div class="rounded-xl overflow-hidden" style="background-color:#0d1f30; border:1px solid #1e3a52;">
          <div class="px-5 py-3 flex justify-between items-center gap-3" style="border-bottom:1px solid #19354d;">
            <div>
              <div class="section-title" style="margin-bottom:0;">Modificaciones contractuales</div>
              <p style="font-size:.75rem;color:#8ea9bf;margin:4px 0 0;">
                Paquete {{ contratoSeleccionado.numero }} — {{ contratoSeleccionado.contratista }}
                <span style="color:#647a8e;">·</span>
                Conclusión vigente: <strong style="color:#fbbf24;">{{ fmtFecha(fechaConclusionVigente) }}</strong>
              </p>
            </div>
            <button
              class="btn btn-sm"
              style="background:#00c9a7;color:#04211c;font-weight:600;"
              @click="mostrarModal = true"
            >
              + Nueva modificación
            </button>
          </div>

          <div v-if="cargandoModificaciones" class="text-center py-10" style="color:#8ea9bf;">
            Cargando modificaciones...
          </div>

          <div v-else-if="!modificaciones.length" class="text-center py-10" style="color:#8ea9bf;">
            Este contrato todavía no tiene modificaciones registradas.
          </div>

          <div v-else class="overflow-x-auto">
            <table class="mod-table">
              <thead>
                <tr>
                  <th class="col-acciones-th">Acciones</th>
                  <th>N°</th>
                  <th>Tipo</th>
                  <th>N° Documento</th>
                  <th>CITE</th>
                  <th>Fecha Anterior</th>
                  <th>Nueva Fecha Conclusión</th>
                  <th>Plazo Modif.</th>
                  <th>Monto Modif. (Bs)</th>
                  <th>SICOES</th>
                  <th>Fecha Informe</th>
                  <th>Fecha Firma</th>
                  <th>Estado</th>
                  <th>PDF</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="m in modificaciones" :key="m.id_modificacion">
                  <td class="col-acciones">
                    <button class="btn-accion btn-editar" title="Editar modificación" @click="abrirEditar(m)">
                      <i class="ti ti-pencil"></i>
                    </button>
                    <button class="btn-accion btn-eliminar" title="Eliminar" @click="eliminarModificacion(m.id_modificacion)">
                      <i class="ti ti-trash"></i>
                    </button>
                  </td>
                  <td class="col-numero">{{ m.numero }}</td>
                  <td class="text-left col-tipo">{{ m.tipo_modificacion }}</td>
                  <td>{{ m.numero_documento_modificatorio || '—' }}</td>
                  <td>{{ m.cite_documento_aprobacion || '—' }}</td>
                  <td>{{ fmtFecha(m.fecha_anterior) }}</td>
                  <td class="col-fecha-destacada">{{ fmtFecha(m.nueva_fecha_conclusion) }}</td>
                  <td class="col-plazo" :style="{ color: fmtPlazo(m.plazo_modificado_dias).color }">
                    {{ fmtPlazo(m.plazo_modificado_dias).texto }}
                  </td>
                  <td class="money">{{ m.monto_modificacion !== null ? fmtBs(m.monto_modificacion) : '—' }}</td>
                  <td>
                    <span class="badge" :style="{ color: badgeColor(m.estado_registro_sicoes), borderColor: badgeColor(m.estado_registro_sicoes) }">
                      {{ m.estado_registro_sicoes || '—' }}
                    </span>
                  </td>
                  <td>{{ fmtFecha(m.fecha_informe_aprobacion) }}</td>
                  <td>{{ fmtFecha(m.fecha_firma_documento) }}</td>
                  <td>
                    <span class="badge" :style="{ color: badgeColor(m.estado_documento), borderColor: badgeColor(m.estado_documento) }">
                      {{ m.estado_documento || '—' }}
                    </span>
                  </td>
                  <td>
                    <a v-if="m.archivo_pdf_url" :href="m.archivo_pdf_url" target="_blank" class="pdf-link">
                      <i class="ti ti-file-type-pdf"></i> Ver
                    </a>
                    <span v-else style="color:#4d6478;">—</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </template>

    <NuevaModificacion
      :show="mostrarModal"
      :id-contrato="idContratoSeleccionado"
      :fecha-actual="fechaConclusionVigente"
      @close="mostrarModal = false"
      @created="alGuardarModificacion"
    />

    <EditarModificacion
      v-if="modificacionEditando"
      :key="modificacionEditando.id_modificacion"
      :modificacion="modificacionEditando"
      :es-ultima="modificacionEditando.numero === numeroMasReciente"
      :fecha-actual="fechaConclusionVigente"
      @close="modificacionEditando = null"
      @updated="alGuardarModificacion"
    />
  </div>
</template>

<style scoped>
.mod-table {
  width: 100%;
  min-width: 1900px;
  border-collapse: collapse;
  font-size: .82rem;
}
.mod-table thead tr { background: #0a1826; border-bottom: 1px solid #1e3a52; }
.mod-table th {
  white-space: nowrap;
  text-align: center;
  padding: 12px 12px;
  color: #9cb5c6;
  font-size: .68rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .04em;
}
.col-acciones-th { position: sticky; left: 0; z-index: 2; background: #0a1826; }
.mod-table tbody tr { border-bottom: 1px solid #152a3e; transition: background .15s; }
.mod-table tbody tr:hover { background: rgba(0, 201, 167, .045); }
.mod-table tbody tr:last-child { border-bottom: 0; }
.mod-table td {
  padding: 14px 12px;
  text-align: center;
  color: #b9cadb;
  white-space: nowrap;
  font-size: .85rem;
}
.mod-table td.text-left { text-align: left; }
.col-acciones {
  position: sticky;
  left: 0;
  z-index: 1;
  background: #0d1f30;
  display: flex;
  gap: 6px;
  justify-content: center;
}
.mod-table tbody tr:hover .col-acciones { background: #0f2536; }
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
.btn-eliminar { background: rgba(248, 113, 113, .08); border-color: rgba(248, 113, 113, .18); color: #f87171; }
.btn-eliminar:hover { background: rgba(248, 113, 113, .16); border-color: rgba(248, 113, 113, .3); }
.col-numero { font-weight: 800; color: #00c9a7; font-size: .9rem; }
.col-tipo { color: #e4f0f7; font-weight: 600; min-width: 160px; }
.col-fecha-destacada { color: #f2fbff; font-weight: 700; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; }
.col-plazo { font-weight: 800; font-size: .92rem; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; }
.money {
  text-align: right !important;
  font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
  color: #e4f0f7 !important;
  font-weight: 700;
  font-size: .88rem;
}
.badge { display: inline-block; padding: 4px 10px; border-radius: 6px; border: 1px solid; font-size: .74rem; font-weight: 700; }
.pdf-link { display: inline-flex; align-items: center; gap: 4px; color: #00c9a7; font-weight: 700; text-decoration: none; font-size: .82rem; }
.pdf-link:hover { text-decoration: underline; }
</style>