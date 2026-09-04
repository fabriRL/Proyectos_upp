<script setup>
import { reactive, ref } from 'vue'
import axios from 'axios'
import { useToast } from '@/composables/useToast.js'

const props = defineProps({
  objeto: { type: Object, required: true },
})
const emit = defineEmits(['close', 'updated'])
const { showToast } = useToast()

const meses = [
  ['monto_ene', 'Ene'], ['monto_feb', 'Feb'], ['monto_mar', 'Mar'], ['monto_abr', 'Abr'],
  ['monto_may', 'May'], ['monto_jun', 'Jun'], ['monto_jul', 'Jul'], ['monto_ago', 'Ago'],
  ['monto_sep', 'Sep'], ['monto_oct', 'Oct'], ['monto_nov', 'Nov'], ['monto_dic', 'Dic'],
]

const form = reactive({
  codigo_objeto: props.objeto.codigo_objeto,
  descripcion: props.objeto.descripcion,
  monto_ejecutado: props.objeto.monto_ejecutado,
  ...Object.fromEntries(meses.map(([campo]) => [campo, props.objeto.meses[campo]])),
})

const errors = ref({})
const enviando = ref(false)

async function guardar() {
  enviando.value = true
  errors.value = {}
  try {
    await axios.put(`/api/objetos-gasto/${props.objeto.id_objeto}`, form)
    showToast('Objeto de gasto actualizado correctamente.', 'success')
    emit('updated')
    emit('close')
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors
    else {
      console.error(e)
      showToast('No se pudo actualizar el objeto de gasto.', 'error')
    }
  } finally {
    enviando.value = false
  }
}

function cerrar() {
  emit('close')
}

const sOverlay = { position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.75)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 999999 }
const sBox = { background: '#0d1f30', border: '1px solid #1e3a52', borderRadius: '12px', padding: '24px', width: '740px', maxWidth: '96vw', color: '#c8dae7', maxHeight: '90vh', overflowY: 'auto' }
const sLabel = { display: 'block', fontSize: '0.72rem', color: '#8ea9bf', margin: '0 0 4px' }
const sInput = { width: '100%', boxSizing: 'border-box', background: '#091520', border: '1px solid #1e3a52', borderRadius: '6px', padding: '8px' }
const sError = { color: '#f87171', fontSize: '0.65rem', display: 'block', marginTop: '4px' }
const sField = { display: 'flex', flexDirection: 'column' }
const sFieldFull = { ...sField, gridColumn: '1 / -1' }
const sGrid = { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px 16px' }
const sMesesGrid = { display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '12px 14px', marginTop: '14px' }
const sSubcard = { border: '1px solid #1e3a52', borderRadius: '10px', background: '#0a1624', padding: '14px 16px', marginTop: '16px' }
const sSubcardHeader = { color: '#00c9a7', fontSize: '0.74rem', fontWeight: '700', textTransform: 'uppercase', letterSpacing: '0.03em', paddingBottom: '10px', marginBottom: '4px', borderBottom: '1px solid #1e3a52' }
const sActions = { display: 'flex', justifyContent: 'flex-end', gap: '8px', marginTop: '20px' }
const sBtnCancel = { padding: '8px 16px', borderRadius: '6px', border: '1px solid #1e3a52', background: 'transparent', color: '#8ea9bf', cursor: 'pointer' }
const sBtnSave = { padding: '8px 16px', borderRadius: '6px', border: 'none', background: '#00c9a7', color: '#04211c', fontWeight: '600', cursor: 'pointer' }
</script>

<template>
  <Teleport to="body">
    <div :style="sOverlay" @click.self="cerrar">
      <div :style="sBox">
        <h2 style="margin:0 0 4px;font-size:1.1rem;">Editar objeto de gasto</h2>
        <p style="margin:0 0 16px;font-size:0.72rem;color:#8ea9bf;">N°{{ objeto.numero }} — {{ objeto.codigo_objeto }}</p>

        <form @submit.prevent="guardar">
          <div :style="sGrid">
            <div :style="sField">
              <label :style="sLabel">Objeto (código)</label>
              <input v-model="form.codigo_objeto" type="text" :style="sInput" />
              <span :style="sError" v-if="errors.codigo_objeto">{{ errors.codigo_objeto[0] }}</span>
            </div>
            <div :style="sField">
              <label :style="sLabel">Monto Ejecutado (Bs)</label>
              <input v-model.number="form.monto_ejecutado" type="number" step="0.01" min="0" :style="sInput" />
            </div>

            <div :style="sFieldFull">
              <label :style="sLabel">Descripción Objeto de Gasto</label>
              <input v-model="form.descripcion" type="text" :style="sInput" />
              <span :style="sError" v-if="errors.descripcion">{{ errors.descripcion[0] }}</span>
            </div>
          </div>

          <div :style="sSubcard">
            <div :style="sSubcardHeader">Programación mensual (Bs)</div>
            <div :style="sMesesGrid">
              <div v-for="[campo, label] in meses" :key="campo" :style="sField">
                <label :style="sLabel">{{ label }}</label>
                <input v-model.number="form[campo]" type="number" step="0.01" min="0" :style="sInput" />
              </div>
            </div>
          </div>

          <div :style="sActions">
            <button type="button" :style="sBtnCancel" @click="cerrar">Cancelar</button>
            <button type="submit" :style="sBtnSave" :disabled="enviando">{{ enviando ? 'Guardando...' : 'Actualizar' }}</button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>