<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from '@/lib/axios'
import { useToast } from '@/composables/useToast.js'
import { useConfirm } from '@/composables/useConfirm.js'
import FormDecretoSupremo from './FormDecretoSupremo.vue'

const { showToast } = useToast()
const { confirmar } = useConfirm()

const decretos = ref([])
const cargando = ref(true)
const error = ref(null)
const busqueda = ref('')
const mostrarForm = ref(false)
const decretoEditando = ref(null)
const eliminandoId = ref(null)

const filtrados = computed(() => {
  const t = busqueda.value.toLowerCase().trim()
  if (!t) return decretos.value
  return decretos.value.filter(d =>
    (d.numero_decreto || '').toLowerCase().includes(t) ||
    (d.descripcion || '').toLowerCase().includes(t)
  )
})

function fmtBs(v) {
  return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(v) || 0)
}
function fmtFecha(v) {
  if (!v) return '—'
  const [y, m, d] = String(v).slice(0, 10).split('-')
  return `${d}/${m}/${y}`
}

async function cargar() {
  cargando.value = true
  error.value = null
  try {
    const { data } = await axios.get('/api/decretos-supremos')
    decretos.value = Array.isArray(data) ? data : []
  } catch (e) {
    console.error(e)
    error.value = 'No se pudieron cargar los decretos supremos.'
  } finally {
    cargando.value = false
  }
}

function nuevo() {
  decretoEditando.value = null
  mostrarForm.value = true
}
function editar(d) {
  decretoEditando.value = d
  mostrarForm.value = true
}

async function eliminar(d) {
  const ok = await confirmar({
    title: '¿Eliminar este Decreto Supremo?',
    message: `Se eliminará "${d.numero_decreto}" del catálogo general.`,
    confirmText: 'Eliminar',
    variant: 'danger',
  })
  if (!ok) return

  eliminandoId.value = d.id_decreto_supremo
  try {
    await axios.delete(`/api/decretos-supremos/${d.id_decreto_supremo}`)
    decretos.value = decretos.value.filter(x => x.id_decreto_supremo !== d.id_decreto_supremo)
    showToast('Decreto Supremo eliminado correctamente.', 'success')
  } catch (e) {
    console.error(e)
    showToast(e.response?.data?.message || 'No se pudo eliminar el Decreto Supremo.', 'error')
  } finally {
    eliminandoId.value = null
  }
}

onMounted(cargar)
</script>

<template>
  <div class="p-5 ds-page">
    <div class="page-header">
      <div>
        <h1>Decretos Supremos</h1>
        <p>Catálogo general. Cada proyecto elige uno de estos decretos y registra sus propios montos.</p>
      </div>
      <button class="btn-nuevo" @click="nuevo"><i class="ti ti-plus"></i> Nuevo decreto</button>
    </div>

    <div class="buscador">
      <i class="ti ti-search"></i>
      <input v-model="busqueda" type="text" placeholder="Buscar por número o descripción..." />
    </div>

    <div v-if="cargando" class="estado"><i class="ti ti-loader-2 spin"></i> Cargando decretos...</div>
    <div v-else-if="error" class="estado error">
      <i class="ti ti-alert-triangle"></i> {{ error }}
      <button class="btn-link" @click="cargar">Reintentar</button>
    </div>
    <div v-else-if="!filtrados.length" class="estado">
      <i class="ti ti-file-off"></i>
      {{ busqueda ? 'No se encontraron decretos con esa búsqueda.' : 'Aún no hay Decretos Supremos registrados.' }}
    </div>

    <div v-else class="tabla-card">
      <div class="tabla-scroll">
        <table>
          <thead>
            <tr>
              <th>N°</th>
              <th class="izq">Número de Decreto Supremo</th>
              <th>Monto (Bs)</th>
              <th>Fecha</th>
              <th class="izq">Descripción</th>
              <th>Proyectos</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(d, i) in filtrados" :key="d.id_decreto_supremo">
              <td>{{ i + 1 }}</td>
              <td class="izq strong">{{ d.numero_decreto }}</td>
              <td class="money">{{ fmtBs(d.monto) }}</td>
              <td>{{ fmtFecha(d.fecha_decreto) }}</td>
              <td class="izq desc">{{ d.descripcion || '—' }}</td>
              <td><span class="badge" :class="d.proyectos_count ? 'badge-uso' : 'badge-libre'">{{ d.proyectos_count }}</span></td>
              <td class="acciones">
                <button class="btn-icon edit" title="Editar" @click="editar(d)"><i class="ti ti-pencil"></i></button>
                <button
                  class="btn-icon del"
                  :title="d.proyectos_count ? 'En uso por proyectos: no se puede eliminar' : 'Eliminar'"
                  :disabled="eliminandoId === d.id_decreto_supremo"
                  @click="eliminar(d)"
                >
                  <i class="ti" :class="eliminandoId === d.id_decreto_supremo ? 'ti-loader-2 spin' : 'ti-trash'"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Transition name="modal-fade">
      <FormDecretoSupremo
        v-if="mostrarForm"
        :key="decretoEditando?.id_decreto_supremo ?? 'nuevo'"
        :decreto="decretoEditando"
        @close="mostrarForm = false"
        @saved="cargar"
      />
    </Transition>
  </div>
</template>

<style scoped>
.ds-page { max-width: 1300px; margin: auto; }
.page-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
.page-header h1 { margin: 0; font-size: 1.25rem; font-weight: 800; color: #f2fbff; }
.page-header p { margin: 3px 0 0; color: #8ea9bf; font-size: .8rem; }
.btn-nuevo { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border: none; border-radius: 8px; background: linear-gradient(135deg, #00d0ae, #00aa91); color: #052029; font-weight: 700; font-size: .82rem; cursor: pointer; }
.buscador { position: relative; max-width: 380px; margin-bottom: 16px; }
.buscador i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #5f7c91; }
.buscador input { width: 100%; box-sizing: border-box; padding: 9px 10px 9px 32px; background: #081a29; border: 1px solid #29465c; border-radius: 8px; color: #d4e4f0; font-size: .8rem; outline: none; }
.buscador input:focus { border-color: rgba(0,201,167,.65); }
.estado { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 44px 20px; color: #8ea9bf; font-size: .85rem; background: #0d1f30; border: 1px solid #1e3a52; border-radius: 12px; }
.estado.error { color: #fca5a5; }
.btn-link { background: none; border: none; color: #55b8ef; cursor: pointer; text-decoration: underline; }
.spin { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.tabla-card { background: #0d1f30; border: 1px solid #1e3a52; border-radius: 12px; overflow: hidden; }
.tabla-scroll { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th { padding: 11px 12px; background: #081a29; color: #8da6b8; font-size: .64rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; border-bottom: 1px solid #1e3a52; text-align: center; white-space: nowrap; }
td { padding: 11px 12px; border-bottom: 1px solid #152a3e; color: #c9dce8; font-size: .78rem; text-align: center; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover { background: rgba(0,201,167,.03); }
.izq { text-align: left; }
.strong { color: #e4f0f7; font-weight: 700; }
.money { text-align: right; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; white-space: nowrap; }
.desc { max-width: 320px; color: #8ea9bf; }
.badge { display: inline-block; min-width: 24px; padding: 2px 9px; border-radius: 20px; font-size: .68rem; font-weight: 800; }
.badge-uso { background: rgba(77,179,240,.12); border: 1px solid rgba(77,179,240,.25); color: #55b8ef; }
.badge-libre { background: rgba(142,169,191,.1); border: 1px solid rgba(142,169,191,.2); color: #91a9ba; }
.acciones { white-space: nowrap; }
.btn-icon { width: 30px; height: 30px; margin: 0 2px; border: 1px solid #1e3a52; border-radius: 6px; background: transparent; cursor: pointer; transition: .15s; }
.btn-icon.edit { color: #55b8ef; }
.btn-icon.edit:hover { background: rgba(77,179,240,.1); border-color: rgba(77,179,240,.4); }
.btn-icon.del { color: #f87171; }
.btn-icon.del:hover:not(:disabled) { background: rgba(248,113,113,.08); border-color: #f87171; }
.btn-icon:disabled { opacity: .5; cursor: not-allowed; }
</style>
