<script setup>
import { reactive, ref } from 'vue'
import axios from 'axios'
import { useToast } from '@/composables/useToast.js'

const props = defineProps({
  show: Boolean,
  codigoProyecto: String,
})
const emit = defineEmits(['close', 'created'])
const { showToast } = useToast()

const form = reactive({ presupuesto_aprobado: null })
const errors = ref({})
const enviando = ref(false)

function resetForm() {
  Object.assign(form, { presupuesto_aprobado: null })
  errors.value = {}
}

async function guardar() {
  enviando.value = true
  errors.value = {}
  try {
    await axios.post(`/api/proyectos/${props.codigoProyecto}/partidas`, form)
    showToast('Partida presupuestaria creada correctamente.', 'success')
    resetForm()
    emit('created')
    emit('close')
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors
    else {
      console.error(e)
      showToast('No se pudo crear la partida.', 'error')
    }
  } finally {
    enviando.value = false
  }
}

function cerrar() {
  resetForm()
  emit('close')
}

const sOverlay = { position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.75)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 999999 }
const sBox = { background: '#0d1f30', border: '1px solid #1e3a52', borderRadius: '12px', padding: '24px', width: '420px', maxWidth: '94vw', color: '#c8dae7' }
const sLabel = { display: 'block', fontSize: '0.72rem', color: '#8ea9bf', margin: '0 0 4px' }
const sInput = { width: '100%', boxSizing: 'border-box', background: '#091520', border: '1px solid #1e3a52', borderRadius: '6px', padding: '9px' }
const sHint = { color: '#647a8e', fontSize: '0.65rem', marginTop: '4px', lineHeight: '1.4' }
const sError = { color: '#f87171', fontSize: '0.65rem', display: 'block', marginTop: '4px' }
const sActions = { display: 'flex', justifyContent: 'flex-end', gap: '8px', marginTop: '20px' }
const sBtnCancel = { padding: '8px 16px', borderRadius: '6px', border: '1px solid #1e3a52', background: 'transparent', color: '#8ea9bf', cursor: 'pointer' }
const sBtnSave = { padding: '8px 16px', borderRadius: '6px', border: 'none', background: '#00c9a7', color: '#04211c', fontWeight: '600', cursor: 'pointer' }
</script>

<template>
  <Teleport to="body">
    <div v-if="show" :style="sOverlay" @click.self="cerrar">
      <div :style="sBox">
        <h2 style="margin:0 0 4px;font-size:1.1rem;">Nueva partida presupuestaria</h2>
        <p style="margin:0 0 16px;font-size:0.72rem;color:#8ea9bf;">
          El presupuesto aprobado se comparte entre todos los objetos de gasto que agregues a esta partida.
        </p>

        <form @submit.prevent="guardar">
          <label :style="sLabel">Presupuesto Aprobado — SIGEP (Bs)</label>
          <input v-model.number="form.presupuesto_aprobado" type="number" step="0.01" min="0" :style="sInput" placeholder="0.00" />
          <span :style="sError" v-if="errors.presupuesto_aprobado">{{ errors.presupuesto_aprobado[0] }}</span>
          <div :style="sHint">Podrás agregar los objetos de gasto (Ene-Dic) después de crear la partida.</div>

          <div :style="sActions">
            <button type="button" :style="sBtnCancel" @click="cerrar">Cancelar</button>
            <button type="submit" :style="sBtnSave" :disabled="enviando">{{ enviando ? 'Guardando...' : 'Guardar' }}</button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>