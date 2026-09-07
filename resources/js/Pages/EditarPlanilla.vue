<script setup>
import { reactive, ref } from 'vue'
import axios from 'axios'
import { useToast } from '@/composables/useToast.js'

const props = defineProps({
  planilla: { type: Object, required: true },
})
const emit = defineEmits(['close', 'updated'])
const { showToast } = useToast()

function soloFecha(valor) {
  if (!valor) return ''
  return String(valor).split('T')[0]
}

const form = reactive({
  periodo_desde: soloFecha(props.planilla.periodo_desde),
  periodo_hasta: soloFecha(props.planilla.periodo_hasta),
  monto_certificado: props.planilla.monto_certificado,
  retencion_gcc: props.planilla.retencion_gcc,
  dias_atraso: props.planilla.dias_atraso ?? 0,
  importe_pagado_sigep: props.planilla.importe_pagado_sigep,
  numero_c31: props.planilla.numero_c31 ?? '',
  monto_c31: props.planilla.monto_c31,
  fecha_aprobacion_fiscal: soloFecha(props.planilla.fecha_aprobacion_fiscal),
  fecha_elaboracion_planilla: soloFecha(props.planilla.fecha_elaboracion_planilla),
  fecha_desembolso: soloFecha(props.planilla.fecha_desembolso),
})

const errors = ref({})
const enviando = ref(false)

async function guardar() {
  enviando.value = true
  errors.value = {}
  try {
    await axios.put(`/api/planillas/${props.planilla.id_planilla}`, form)
    showToast('Planilla actualizada correctamente.', 'success')
    emit('updated')
    emit('close')
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors ?? {}
    } else {
      console.error(e)
      showToast('No se pudo actualizar la planilla.', 'error')
    }
  } finally {
    enviando.value = false
  }
}

function cerrar() {
  emit('close')
}

const sOverlay = { position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.75)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 999999 }
const sBox = { background: '#0d1f30', border: '1px solid #1e3a52', borderRadius: '12px', padding: '24px', width: '700px', maxWidth: '94vw', color: '#c8dae7', maxHeight: '90vh', overflowY: 'auto' }
const sLabel = { display: 'block', fontSize: '0.72rem', color: '#8ea9bf', margin: '0 0 4px' }
const sInput = { width: '100%', boxSizing: 'border-box', background: '#091520', border: '1px solid #1e3a52', borderRadius: '6px', padding: '8px 9px', color: '#c8dae7', fontSize: '0.85rem' }
const sError = { color: '#f87171', fontSize: '0.65rem', marginTop: '4px', display: 'block' }
const sHint = { color: '#647a8e', fontSize: '0.65rem', marginTop: '4px', display: 'block', lineHeight: '1.4' }
const sField = { display: 'flex', flexDirection: 'column' }
const sGrid = { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px 16px' }
const sActions = { display: 'flex', justifyContent: 'flex-end', gap: '8px', marginTop: '20px' }
const sBtnCancel = { padding: '9px 18px', borderRadius: '6px', border: '1px solid #1e3a52', background: 'transparent', color: '#8ea9bf', cursor: 'pointer', fontSize: '0.82rem' }
const sBtnSave = { padding: '9px 18px', borderRadius: '6px', border: 'none', background: '#00c9a7', color: '#04211c', fontWeight: '600', cursor: 'pointer', fontSize: '0.82rem' }
</script>

<template>
  <Teleport to="body">
    <div :style="sOverlay" @click.self="cerrar">
      <div :style="sBox">
        <h2 style="margin:0 0 4px;font-size:1.1rem;">Editar planilla</h2>
        <p style="margin:0 0 16px;font-size:0.72rem;color:#8ea9bf;">N°{{ planilla.numero }}</p>

        <form @submit.prevent="guardar">
          <div :style="sGrid">
            <div :style="sField">
              <label :style="sLabel">Periodo desde</label>
              <input v-model="form.periodo_desde" type="date" :style="sInput" />
              <span :style="sError" v-if="errors.periodo_desde">{{ errors.periodo_desde[0] }}</span>
            </div>
            <div :style="sField">
              <label :style="sLabel">Periodo hasta</label>
              <input v-model="form.periodo_hasta" type="date" :style="sInput" />
              <span :style="sError" v-if="errors.periodo_hasta">{{ errors.periodo_hasta[0] }}</span>
            </div>

            <div :style="sField">
              <label :style="sLabel">Importe del trabajo ejecutado (Bs)</label>
              <input v-model.number="form.monto_certificado" type="number" step="0.01" min="0" :style="sInput" />
              <span :style="sError" v-if="errors.monto_certificado">{{ errors.monto_certificado[0] }}</span>
            </div>
            <div :style="sField">
              <label :style="sLabel">Retenciones (Bs)</label>
              <input v-model.number="form.retencion_gcc" type="number" step="0.01" min="0" :style="sInput" />
              <div :style="sHint">Opcional.</div>
            </div>

            <div :style="sField">
              <label :style="sLabel">Días de atraso</label>
              <input v-model.number="form.dias_atraso" type="number" min="0" :style="sInput" />
            </div>
            <div :style="sField">
              <label :style="sLabel">Importe pagado según SIGEP (Bs)</label>
              <input v-model.number="form.importe_pagado_sigep" type="number" step="0.01" min="0" :style="sInput" />
            </div>

            <div :style="sField">
              <label :style="sLabel">N° de C-31</label>
              <input v-model="form.numero_c31" type="text" :style="sInput" />
            </div>
            <div :style="sField">
              <label :style="sLabel">Monto C-31 (Bs)</label>
              <input v-model.number="form.monto_c31" type="number" step="0.01" min="0" :style="sInput" />
            </div>

            <div :style="sField">
              <label :style="sLabel">Fecha aprobación del fiscal</label>
              <input v-model="form.fecha_aprobacion_fiscal" type="date" :style="sInput" />
            </div>
            <div :style="sField">
              <label :style="sLabel">Fecha elaboración de planilla</label>
              <input v-model="form.fecha_elaboracion_planilla" type="date" :style="sInput" />
            </div>

            <div :style="sField">
              <label :style="sLabel">Fecha de desembolso</label>
              <input v-model="form.fecha_desembolso" type="date" :style="sInput" />
              <div :style="sHint">Los días de demora se recalculan solos.</div>
            </div>
          </div>

          <div :style="sActions">
            <button type="button" :style="sBtnCancel" @click="cerrar">Cancelar</button>
            <button type="submit" :style="sBtnSave" :disabled="enviando">
              {{ enviando ? 'Guardando...' : 'Actualizar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>