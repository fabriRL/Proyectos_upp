<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/lib/axios'
import NuevoDecreto from './NuevoDecreto.vue'
import EditarDecreto from './EditarDecreto.vue'
import { useToast } from '@/composables/useToast.js'
import { useConfirm } from '@/composables/useConfirm.js'

const route = useRoute()
const { showToast } = useToast()
const { confirmar } = useConfirm()
const codigoProyecto = route.params.codigo

const decretos = ref([])
const resumen = ref({
  monto_vigente_decreto_total: 0,
  monto_vigente_contratos: 0,
  monto_puesta_marcha_total: 0,
  monto_auditoria_total: 0,
  monto_comprometido: 0,
  utilizacion_ds_pct: 0,
  saldo_disponible: 0,
})
const cargando = ref(true)
const error = ref(null)
const mostrarModal = ref(false)
const decretoEditando = ref(null)

function fmtBs(valor) {
  return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(valor) || 0)
}

async function cargar() {
  cargando.value = true
  error.value = null
  try {
    const { data } = await axios.get(`/api/proyectos/${codigoProyecto}/decretos`)
    decretos.value = data.decretos
    resumen.value = data.resumen
  } catch (e) {
    error.value = 'No se pudieron cargar los decretos supremos.'
    console.error(e)
  } finally {
    cargando.value = false
  }
}

async function eliminarDecreto(idDecreto) {
  const ok = await confirmar({ title: '¿Eliminar este registro?', message: 'Se eliminará este registro de Decreto Supremo del proyecto. El Decreto Supremo original no se modifica.', confirmText: 'Eliminar', variant: 'danger' })
  if (!ok) return
  try {
    await axios.delete(`/api/decretos/${idDecreto}`)
    await cargar()
    showToast('Decreto Supremo eliminado correctamente.', 'success')
  } catch (e) {
    console.error(e)
    showToast('No se pudo eliminar el registro.', 'error')
  }
}
onMounted(cargar)
</script>

<template>
  <div class="p-5" style="max-width:1680px;margin:auto;">
    <div v-if="cargando" class="text-center py-10" style="color:#8ea9bf;">
      Cargando información del decreto...
    </div>

    <div v-else-if="error" class="text-center py-10" style="color:#f87171;">
      {{ error }}
      <button class="btn btn-sm btn-ghost ml-2" @click="cargar">Reintentar</button>
    </div>

    <template v-else>
      <div class="grid grid-cols-4 gap-3 mb-5">
        <div class="rounded-xl p-4" style="background-color:#0d1f30; border:1px solid #1e3a52;">
          <div class="field-label">Monto Vigente del D.S.</div>
          <div class="font-bold text-xl mt-1" style="color:#d0dde8;">{{ fmtBs(resumen.monto_vigente_decreto_total) }}</div>
        </div>
        <div class="rounded-xl p-4" style="background-color:#0d1f30; border:1px solid #1e3a52;">
          <div class="field-label">Monto Comprometido</div>
          <div class="font-bold text-xl mt-1" style="color:#00c9a7;">{{ fmtBs(resumen.monto_comprometido) }}</div>
        </div>
        <div class="rounded-xl p-4" style="background-color:#0d1f30; border:1px solid #1e3a52;">
          <div class="field-label">Utilización del D.S.</div>
          <div class="font-bold text-xl mt-1" style="color:#fbbf24;">{{ resumen.utilizacion_ds_pct.toFixed(2) }}%</div>
        </div>
        <div class="rounded-xl p-4" style="background-color:#0d1f30; border:1px solid #1e3a52;">
          <div class="field-label">Saldo Disponible</div>
          <div class="font-bold text-xl mt-1" :style="{ color: resumen.saldo_disponible < 0 ? '#f87171' : '#00c9a7' }">{{ fmtBs(resumen.saldo_disponible) }}</div>
        </div>
      </div>

      <div class="rounded-xl overflow-hidden" style="background-color:#0d1f30; border:1px solid #1e3a52;">
        <div class="px-5 py-3 flex justify-between items-center gap-3" style="border-bottom:1px solid #19354d;">
          <div>
            <div class="section-title" style="margin-bottom:0;">7. Montos ejecutados según Decreto Supremo vs Contratos</div>
            <p class="table-subtitle">Comparativo entre el presupuesto decretado y lo comprometido en contratos.</p>
          </div>
          <button
            class="btn btn-sm"
            style="background:#00c9a7;color:#04211c;font-weight:600;"
            @click="mostrarModal = true"
          >
            + Nuevo registro
          </button>
        </div>

        <div v-if="decretos.length === 0" class="text-center py-10" style="color:#8ea9bf;">
          No hay Decretos Supremos registrados para este proyecto.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="table table-xs decreto-table">
            <thead>
              <tr>
                <th>N°</th><th>Número de Decreto Supremo</th>
                <th>Monto Inicial del D.S.</th><th>Incremento al D.S.</th><th>Monto Vigente del D.S.</th>
                <th>Monto Vigente de Contratos</th>
                <th>Puesta en Marcha/Insumos</th><th>Auditoría Interna</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in decretos" :key="d.id_decreto">
                <td>{{ d.numero }}</td>
                <td class="text-left">{{ d.numero_decreto }}</td>
                <td class="money">{{ fmtBs(d.monto_inicial) }}</td>
                <td class="money">{{ fmtBs(d.incremento) }}</td>
                <td class="money" style="font-weight:700;color:#d0dde8;">{{ fmtBs(d.monto_vigente) }}</td>
                <td class="money" style="font-weight:700;color:#00c9a7;">{{ fmtBs(resumen.monto_vigente_contratos) }}</td>
                <td class="money">{{ fmtBs(d.monto_puesta_marcha_insumos) }}</td>
                <td class="money">{{ fmtBs(d.monto_auditoria_interna) }}</td>
                <td>
                  <button style="color:#55b8ef;background:none;border:none;cursor:pointer;font-size:.9rem;padding:4px 6px;" @click="decretoEditando = d" title="Editar registro">
                    <i class="ti ti-pencil"></i>
                  </button>
                  <button style="color:#f87171;background:none;border:none;cursor:pointer;font-size:.9rem;padding:4px 6px;" @click="eliminarDecreto(d.id_decreto)" title="Eliminar registro">
                    <i class="ti ti-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <NuevoDecreto
      :show="mostrarModal"
      :codigo-proyecto="codigoProyecto"
      @close="mostrarModal = false"
      @created="cargar"
    />

    <Transition name="modal-fade">
      <EditarDecreto
        v-if="decretoEditando"
        :key="decretoEditando.id_decreto"
        :decreto="decretoEditando"
        @close="decretoEditando = null"
        @updated="cargar"
      />
    </Transition>
  </div>
</template>

<style scoped>
.table-subtitle{font-size:.7rem;color:#8ea9bf;margin:4px 0 0}
.decreto-table{font-size:.75rem;min-width:1300px}
.decreto-table th{white-space:normal;line-height:1.2;text-align:center;padding:8px 6px;color:#8ea9bf;font-size:.65rem;text-transform:uppercase}
.decreto-table td{padding:10px 8px;text-align:center;color:#8ea9bf;white-space:nowrap}
.decreto-table td.text-left{text-align:left;color:#c8dae7}
.money{text-align:right!important;font-family:ui-monospace,SFMono-Regular,Consolas,monospace;color:#c8dae7!important}
</style>