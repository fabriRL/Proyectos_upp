<script setup>
import { reactive, ref, watch } from 'vue'
import axios from '@/lib/axios'

const props = defineProps({
  show: Boolean,
  codigoProyecto: String,
})
const emit = defineEmits(['close', 'created'])

const form = reactive({
  numero_decreto: '',
  monto_inicial: null,
  incremento: 0,
  monto_puesta_marcha_insumos: 0,
  monto_auditoria_interna: 0,
})

const errors = ref({})
const enviando = ref(false)
const cargandoProyecto = ref(false)

function resetForm() {
  Object.assign(form, {
    numero_decreto: '',
    monto_inicial: null,
    incremento: 0,
    monto_puesta_marcha_insumos: 0,
    monto_auditoria_interna: 0,
  })
  errors.value = {}
}

// Al abrir el modal, trae el número de decreto ("norma_financiador")
// y el monto inicial ("monto_decreto") ya guardados en el proyecto.
// Ambos quedan bloqueados en el formulario — si hay que cambiarlos,
// se cambian editando el proyecto, no desde aquí, para que los dos
// lugares nunca queden desincronizados.
async function precargarDatosProyecto() {
  if (!props.codigoProyecto) return

  cargandoProyecto.value = true
  try {
    const { data } = await axios.get(`/api/proyectos/${props.codigoProyecto}`)
    form.numero_decreto = data?.norma_financiador ?? ''
    form.monto_inicial = data?.monto_decreto !== null && data?.monto_decreto !== undefined
      ? Number(data.monto_decreto)
      : null
  } catch (e) {
    console.error('No se pudieron precargar los datos del proyecto:', e)
  } finally {
    cargandoProyecto.value = false
  }
}

watch(
  () => props.show,
  (visible) => {
    if (visible) precargarDatosProyecto()
  }
)

async function guardar() {
  enviando.value = true
  errors.value = {}
  try {
    await axios.post(`/api/proyectos/${props.codigoProyecto}/decretos`, form)
    resetForm()
    emit('created')
    emit('close')
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors
    else console.error(e)
  } finally {
    enviando.value = false
  }
}

function cerrar() {
  resetForm()
  emit('close')
}

const sOverlay = { position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.75)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 999999 }
const sBox = { background: '#0d1f30', border: '1px solid #1e3a52', borderRadius: '12px', padding: '24px', width: '460px', maxWidth: '92vw', color: '#c8dae7', maxHeight: '90vh', overflowY: 'auto' }
const sLabel = { display: 'block', fontSize: '0.72rem', color: '#8ea9bf', margin: '14px 0 4px', display: 'flex', alignItems: 'center', gap: '6px' }
const sInput = { width: '100%', boxSizing: 'border-box', background: '#091520', border: '1px solid #1e3a52', borderRadius: '6px', padding: '9px 10px', color: '#c8dae7', fontSize: '0.85rem' }
const sInputLocked = { ...sInput, background: '#091622', color: '#00c9a7', fontWeight: '600', cursor: 'not-allowed', borderColor: 'rgba(0,201,167,0.25)' }
const sTagLocked = { fontSize: '0.6rem', fontWeight: '700', padding: '1px 6px', borderRadius: '4px', background: 'rgba(0,201,167,0.12)', color: '#00c9a7', textTransform: 'none' }
const sError = { color: '#f87171', fontSize: '0.65rem', marginTop: '4px', display: 'block' }
const sHint = { color: '#647a8e', fontSize: '0.65rem', marginTop: '4px', lineHeight: '1.4' }
const sRow = { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }
const sActions = { display: 'flex', justifyContent: 'flex-end', gap: '8px', marginTop: '24px' }
const sBtnCancel = { padding: '9px 18px', borderRadius: '6px', border: '1px solid #1e3a52', background: 'transparent', color: '#8ea9bf', cursor: 'pointer', fontSize: '0.82rem' }
const sBtnSave = { padding: '9px 18px', borderRadius: '6px', border: 'none', background: '#00c9a7', color: '#04211c', fontWeight: '600', cursor: 'pointer', fontSize: '0.82rem' }
</script>

<template>
  <Teleport to="body">
    <Transition name="modal-fade">
    <div v-if="show" :style="sOverlay" @click.self="cerrar">
      <div :style="sBox">
        <h2 style="margin:0 0 4px;font-size:1.1rem;">Nuevo Decreto Supremo</h2>
        <p style="margin:0 0 8px;font-size:0.72rem;color:#8ea9bf;">
          Registro de financiamiento del proyecto.
        </p>

        <form @submit.prevent="guardar">
          <label :style="sLabel">
            Número de Decreto Supremo
            <span :style="sTagLocked">Del proyecto</span>
          </label>
          <input
            :value="cargandoProyecto ? 'Cargando...' : form.numero_decreto"
            type="text"
            :style="sInputLocked"
            disabled
          />
          <span :style="sError" v-if="errors.numero_decreto">{{ errors.numero_decreto[0] }}</span>
          <div :style="sHint">Viene del campo "Norma financiador" del proyecto. Para cambiarlo, edita el proyecto.</div>

          <label :style="sLabel">
            Monto inicial del D.S. (Bs)
            <span :style="sTagLocked">Del proyecto</span>
          </label>
          <input
            :value="cargandoProyecto ? 'Cargando...' : (form.monto_inicial !== null ? form.monto_inicial : '—')"
            type="text"
            :style="sInputLocked"
            disabled
          />
          <span :style="sError" v-if="errors.monto_inicial">{{ errors.monto_inicial[0] }}</span>
          <div :style="sHint">Viene del campo "Monto del decreto" del proyecto. Para cambiarlo, edita el proyecto.</div>

          <label :style="sLabel">Incremento al D.S. (Bs)</label>
          <input v-model.number="form.incremento" type="number" step="0.01" min="0" :style="sInput" />
          <div :style="sHint">Solo si hubo ampliación presupuestaria. El monto vigente se calcula solo.</div>

          <div :style="sRow">
            <div>
              <label :style="sLabel">Puesta en marcha / Insumos (Bs)</label>
              <input v-model.number="form.monto_puesta_marcha_insumos" type="number" step="0.01" min="0" :style="sInput" />
            </div>
            <div>
              <label :style="sLabel">Auditoría interna (Bs)</label>
              <input v-model.number="form.monto_auditoria_interna" type="number" step="0.01" min="0" :style="sInput" />
            </div>
          </div>

          <div :style="sActions">
            <button type="button" :style="sBtnCancel" @click="cerrar">Cancelar</button>
            <button type="submit" :style="sBtnSave" :disabled="enviando || cargandoProyecto || form.monto_inicial === null">
              {{ enviando ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
    </Transition>
  </Teleport>
</template>