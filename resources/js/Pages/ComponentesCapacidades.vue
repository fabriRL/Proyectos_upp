<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/lib/axios'
import { useToast } from '@/composables/useToast.js'
import { useConfirm } from '@/composables/useConfirm.js'
import NuevoComponente from './NuevoComponente.vue'

const route = useRoute()
const router = useRouter()
const codigoProyecto = route.params.codigo
const { showToast } = useToast()
const { confirmar } = useConfirm()

const proyecto = ref(null)
const componentes = ref([])
const cargando = ref(true)
const error = ref('')
const mostrarModal = ref(false)

const nuevosProductos = reactive({})
const guardandoProducto = reactive({})

function formularioVacio() {
  return { nombre: '', cantidad: null, unidad: '' }
}

async function cargar() {
  cargando.value = true
  error.value = ''
  try {
    const [resProyecto, resComponentes] = await Promise.all([
      axios.get(`/api/proyectos/${codigoProyecto}`),
      axios.get(`/api/proyectos/${codigoProyecto}/componentes`),
    ])
    proyecto.value = resProyecto.data
    componentes.value = resComponentes.data

    componentes.value.forEach((c) => {
      if (!nuevosProductos[c.id_componente]) {
        nuevosProductos[c.id_componente] = formularioVacio()
      }
    })
  } catch (e) {
    console.error(e)
    error.value = 'No se pudo cargar la información del proyecto.'
  } finally {
    cargando.value = false
  }
}

async function eliminarComponente(idComponente) {
  const ok = await confirmar({ title: '¿Eliminar este componente?', message: 'Se eliminará el componente junto con todos sus productos.', confirmText: 'Eliminar', variant: 'danger' })
  if (!ok) return
  try {
    await axios.delete(`/api/componentes/${idComponente}`)
    showToast('Componente eliminado correctamente.', 'success')
    await cargar()
  } catch (e) {
    console.error(e)
    showToast('No se pudo eliminar el componente.', 'error')
  }
}

async function agregarProducto(idComponente) {
  const datos = nuevosProductos[idComponente]
  if (!datos?.nombre?.trim()) return

  guardandoProducto[idComponente] = true
  try {
    await axios.post(`/api/componentes/${idComponente}/productos`, datos)
    nuevosProductos[idComponente] = formularioVacio()
    showToast('Producto agregado correctamente.', 'success')
    await cargar()
  } catch (e) {
    console.error(e)
    showToast('No se pudo agregar el producto.', 'error')
  } finally {
    guardandoProducto[idComponente] = false
  }
}

async function eliminarProducto(idProducto) {
  const ok = await confirmar({ title: '¿Eliminar este producto?', message: 'Esta acción quitará el producto del componente.', confirmText: 'Eliminar', variant: 'danger' })
  if (!ok) return
  try {
    await axios.delete(`/api/productos/${idProducto}`)
    showToast('Producto eliminado correctamente.', 'success')
    await cargar()
  } catch (e) {
    console.error(e)
    showToast('No se pudo eliminar el producto.', 'error')
  }
}

function irADetalleProyecto() {
  router.push({ name: 'datos', params: { codigo: codigoProyecto } })
}

onMounted(cargar)

const sCard = { background: '#0d1f30', border: '1px solid #1e3a52', borderRadius: '12px', overflow: 'hidden', marginBottom: '16px' }
const sCardHeader = { display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '14px 18px', borderBottom: '1px solid #19354d' }
const sCardBody = { display: 'grid', gridTemplateColumns: '1fr 1fr' }
const sColLeft = { padding: '16px 18px', borderRight: '1px solid #19354d' }
const sColRight = { padding: '16px 18px' }
const sBullets = { whiteSpace: 'pre-line', color: '#c8dae7', fontSize: '0.82rem', lineHeight: '1.7', margin: 0 }
const sProductoRow = { display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '7px 0', borderBottom: '1px solid #152a3e', fontSize: '0.82rem' }
const sInputSm = { background: '#091520', border: '1px solid #1e3a52', borderRadius: '6px', padding: '7px 9px', color: '#c8dae7', fontSize: '0.78rem' }
</script>

<template>
  <div class="p-5" style="max-width:1200px;margin:0 auto;color:#d4e4f0;">

    <div v-if="cargando" style="text-align:center;padding:60px 0;color:#8ea9bf;">
      Cargando información del proyecto...
    </div>

    <div v-else-if="error" style="text-align:center;padding:60px 0;color:#fca5a5;">
      {{ error }}
    </div>

    <template v-else>
      <div style="margin-bottom:20px;">
        <p style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;color:#00c9a7;font-weight:700;margin:0;">
          Paso 2 de 2 · Proyecto {{ codigoProyecto }}
        </p>
        <h1 style="font-size:1.35rem;font-weight:800;margin:4px 0 4px;color:#f2fbff;">
          Componentes o Líneas y Capacidades
        </h1>
        <p style="font-size:.82rem;color:#8ea9bf;margin:0;">
          Registra los componentes o líneas productivas del proyecto ({{ proyecto?.nombre }}) y los productos que genera cada uno.
        </p>
      </div>

      <button
        type="button"
        @click="mostrarModal = true"
        style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border:none;border-radius:8px;background:linear-gradient(135deg,#00d0ae,#00aa91);color:#052029;font-weight:700;font-size:.82rem;cursor:pointer;margin-bottom:18px;"
      >
        <i class="ti ti-plus"></i> Nuevo componente
      </button>

      <div v-if="!componentes.length" style="text-align:center;padding:50px 0;color:#8ea9bf;border:1px dashed #1e3a52;border-radius:12px;">
        Todavía no hay componentes registrados para este proyecto.
      </div>

      <div v-for="c in componentes" :key="c.id_componente" :style="sCard">
        <div :style="sCardHeader">
          <div style="font-weight:700;font-size:.95rem;color:#f2fbff;">{{ c.nombre }}</div>
          <button
            type="button"
            @click="eliminarComponente(c.id_componente)"
            style="background:none;border:none;color:#f87171;cursor:pointer;font-size:.85rem;"
            title="Eliminar componente"
          >
            <i class="ti ti-trash"></i>
          </button>
        </div>

        <div :style="sCardBody">
          <div :style="sColLeft">
            <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;color:#8ea9bf;font-weight:700;margin-bottom:10px;">
              Componentes / Líneas
            </div>
            <p v-if="c.descripcion" :style="sBullets">{{ c.descripcion }}</p>
            <p v-else style="color:#647a8e;font-size:.78rem;">Sin descripción de capacidades.</p>
          </div>

          <div :style="sColRight">
            <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;color:#8ea9bf;font-weight:700;margin-bottom:10px;">
              Productos
            </div>

            <div v-if="!c.productos?.length" style="color:#647a8e;font-size:.78rem;margin-bottom:10px;">
              Sin productos registrados.
            </div>

            <div v-for="p in c.productos" :key="p.id_producto" :style="sProductoRow">
              <span style="color:#c8dae7;">{{ p.nombre }}</span>
              <span style="display:flex;align-items:center;gap:10px;">
                <span style="color:#8ea9bf;font-family:ui-monospace,monospace;font-size:.76rem;">
                  {{ p.cantidad ? Number(p.cantidad).toLocaleString('es-BO') : '' }} {{ p.unidad || '' }}
                </span>
                <button
                  type="button"
                  @click="eliminarProducto(p.id_producto)"
                  style="background:none;border:none;color:#f87171;cursor:pointer;font-size:.75rem;"
                  title="Eliminar producto"
                >✕</button>
              </span>
            </div>

            <form
              @submit.prevent="agregarProducto(c.id_componente)"
              style="display:flex;gap:6px;margin-top:12px;flex-wrap:wrap;"
            >
              <input
                v-model="nuevosProductos[c.id_componente].nombre"
                type="text"
                placeholder="Nombre del producto"
                :style="{ ...sInputSm, flex: '2', minWidth: '140px' }"
              />
              <input
                v-model.number="nuevosProductos[c.id_componente].cantidad"
                type="number"
                step="0.01"
                placeholder="Cantidad"
                :style="{ ...sInputSm, flex: '1', minWidth: '80px' }"
              />
              <input
                v-model="nuevosProductos[c.id_componente].unidad"
                type="text"
                placeholder="Unidad"
                :style="{ ...sInputSm, flex: '1', minWidth: '80px' }"
              />
              <button
                type="submit"
                :disabled="guardandoProducto[c.id_componente]"
                style="padding:7px 12px;border:none;border-radius:6px;background:#00c9a7;color:#04211c;font-weight:600;font-size:.76rem;cursor:pointer;"
              >
                + Agregar
              </button>
            </form>
          </div>
        </div>
      </div>

      <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:24px;">
        <button
          type="button"
          @click="irADetalleProyecto"
          style="padding:10px 18px;border:1px solid #1e3a52;border-radius:8px;background:transparent;color:#8ea9bf;font-weight:600;font-size:.85rem;cursor:pointer;"
        >
          Omitir por ahora
        </button>
        <button
          type="button"
          @click="irADetalleProyecto"
          style="padding:10px 22px;border:none;border-radius:8px;background:linear-gradient(135deg,#00d0ae,#00aa91);color:#052029;font-weight:700;font-size:.85rem;cursor:pointer;"
        >
          Finalizar y ver proyecto
        </button>
      </div>
    </template>

    <NuevoComponente
      :show="mostrarModal"
      :codigo-proyecto="codigoProyecto"
      @close="mostrarModal = false"
      @created="cargar"
    />

  </div>
</template>