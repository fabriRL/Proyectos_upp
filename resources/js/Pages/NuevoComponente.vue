<script setup>
import { reactive, ref } from 'vue'
import axios from '@/lib/axios'

const props = defineProps({
  show: Boolean,
  codigoProyecto: String,
})
const emit = defineEmits(['close', 'created'])

const form = reactive({ nombre: '', descripcion: '' })
const errors = ref({})
const enviando = ref(false)

function resetForm() {
  Object.assign(form, { nombre: '', descripcion: '' })
  errors.value = {}
}

async function guardar() {
  enviando.value = true
  errors.value = {}
  try {
    await axios.post(`/api/proyectos/${props.codigoProyecto}/componentes`, form)
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
const sLabel = { display: 'block', fontSize: '0.72rem', color: '#8ea9bf', margin: '14px 0 4px' }
const sInput = { width: '100%', boxSizing: 'border-box', background: '#091520', border: '1px solid #1e3a52', borderRadius: '6px', padding: '9px 10px', color: '#c8dae7', fontSize: '0.85rem' }
const sTextarea = { ...sInput, minHeight: '160px', resize: 'vertical', fontFamily: 'inherit', lineHeight: '1.5' }
const sError = { color: '#f87171', fontSize: '0.65rem', marginTop: '4px', display: 'block' }
const sHint = { color: '#647a8e', fontSize: '0.65rem', marginTop: '4px', lineHeight: '1.4' }
const sActions = { display: 'flex', justifyContent: 'flex-end', gap: '8px', marginTop: '20px' }
const sBtnCancel = { padding: '9px 18px', borderRadius: '6px', border: '1px solid #1e3a52', background: 'transparent', color: '#8ea9bf', cursor: 'pointer', fontSize: '0.82rem' }
const sBtnSave = { padding: '9px 18px', borderRadius: '6px', border: 'none', background: '#00c9a7', color: '#04211c', fontWeight: '600', cursor: 'pointer', fontSize: '0.82rem' }
</script>

<template>
  <Teleport to="body">
    <div v-if="show" :style="sOverlay" @click.self="cerrar">
      <div :style="sBox">
        <h2 style="margin:0 0 4px;font-size:1.1rem;">Nuevo componente / línea</h2>
        <p style="margin:0 0 8px;font-size:0.72rem;color:#8ea9bf;">Ej. "MATADERO", "CONFINAMIENTO"</p>

        <form @submit.prevent="guardar">
          <label :style="sLabel">Nombre del componente</label>
          <input v-model="form.nombre" type="text" :style="sInput" placeholder="Ej. MATADERO" />
          <span :style="sError" v-if="errors.nombre">{{ errors.nombre[0] }}</span>

          <label :style="sLabel">Capacidades (una por línea)</label>
          <textarea v-model="form.descripcion" :style="sTextarea" placeholder="250 cabezas día, para la producción de 50 Ton/día de carne de alta calidad
3.239 Ton/año cortes especiales
131 Ton/año de harina de sangre"></textarea>
          <div :style="sHint">Cada línea se mostrará como un punto separado.</div>

          <div :style="sActions">
            <button type="button" :style="sBtnCancel" @click="cerrar">Cancelar</button>
            <button type="submit" :style="sBtnSave" :disabled="enviando">{{ enviando ? 'Guardando...' : 'Guardar' }}</button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>