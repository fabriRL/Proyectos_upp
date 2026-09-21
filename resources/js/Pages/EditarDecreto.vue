<script setup>
import { reactive, ref } from 'vue'
import axios from '@/lib/axios'
import { useToast } from '@/composables/useToast.js'

const props = defineProps({
  decreto: { type: Object, required: true },
})
const emit = defineEmits(['close', 'updated'])
const { showToast } = useToast()

const form = reactive({
  incremento: Number(props.decreto.incremento) || 0,
  monto_puesta_marcha_insumos: Number(props.decreto.monto_puesta_marcha_insumos) || 0,
  monto_auditoria_interna: Number(props.decreto.monto_auditoria_interna) || 0,
})
const errors = ref({})
const enviando = ref(false)

async function guardar() {
  enviando.value = true
  errors.value = {}
  try {
    await axios.put(`/api/decretos/${props.decreto.id_decreto}`, form)
    showToast('Registro de Decreto Supremo actualizado correctamente.', 'success')
    emit('updated')
    emit('close')
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors
    else {
      console.error(e)
      showToast('No se pudo actualizar el registro.', 'error')
    }
  } finally {
    enviando.value = false
  }
}

function cerrar() {
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
    <div :style="sOverlay" @click.self="cerrar">
      <div :style="sBox">
        <h2 style="margin:0 0 4px;font-size:1.1rem;">Editar Decreto Supremo</h2>
        <p style="margin:0 0 8px;font-size:0.72rem;color:#8ea9bf;">
          Registro de financiamiento del proyecto.
        </p>

        <form @submit.prevent="guardar">
          <label :style="sLabel">
            Número de Decreto Supremo
            <span :style="sTagLocked">Del proyecto</span>
          </label>
          <input :value="decreto.numero_decreto" type="text" :style="sInputLocked" disabled />
          <div :style="sHint">El Decreto Supremo original no se modifica desde aquí.</div>

          <label :style="sLabel">
            Monto inicial del D.S. (Bs)
            <span :style="sTagLocked">Del proyecto</span>
          </label>
          <input :value="decreto.monto_inicial" type="text" :style="sInputLocked" disabled />

          <label :style="sLabel">Incremento al D.S. (Bs)</label>
          <input v-model.number="form.incremento" type="number" step="0.01" min="0" :style="sInput" />
          <span :style="sError" v-if="errors.incremento">{{ errors.incremento[0] }}</span>
          <div :style="sHint">Solo si hubo ampliación presupuestaria. El monto vigente se calcula solo.</div>

          <div :style="sRow">
            <div>
              <label :style="sLabel">Puesta en marcha / Insumos (Bs)</label>
              <input v-model.number="form.monto_puesta_marcha_insumos" type="number" step="0.01" min="0" :style="sInput" />
              <span :style="sError" v-if="errors.monto_puesta_marcha_insumos">{{ errors.monto_puesta_marcha_insumos[0] }}</span>
            </div>
            <div>
              <label :style="sLabel">Auditoría interna (Bs)</label>
              <input v-model.number="form.monto_auditoria_interna" type="number" step="0.01" min="0" :style="sInput" />
              <span :style="sError" v-if="errors.monto_auditoria_interna">{{ errors.monto_auditoria_interna[0] }}</span>
            </div>
          </div>

          <div :style="sActions">
            <button type="button" :style="sBtnCancel" @click="cerrar">Cancelar</button>
            <button type="submit" :style="sBtnSave" :disabled="enviando">
              {{ enviando ? 'Guardando...' : 'Guardar cambios' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
