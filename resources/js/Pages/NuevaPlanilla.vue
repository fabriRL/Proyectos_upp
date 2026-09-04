<script setup>
import { reactive, ref } from 'vue'
import axios from 'axios'
//ALERTA
import { useToast } from '@/composables/useToast.js'
const { showToast } = useToast()

const props = defineProps({
  show: Boolean,
  idContrato: [Number, String],
})
const emit = defineEmits(['close', 'created'])

const form = reactive({
  periodo_desde: '',
  periodo_hasta: '',
  monto_certificado: null,
  retencion_gcc: 0,
  dias_atraso: 0,
  importe_pagado_sigep: null,
  numero_c31: '',
  monto_c31: null,
  fecha_aprobacion_fiscal: '',
  fecha_elaboracion_planilla: '',
  fecha_desembolso: '',
})

const errors = ref({})
const enviando = ref(false)

function resetForm() {
  Object.assign(form, {
    periodo_desde: '',
    periodo_hasta: '',
    monto_certificado: null,
    retencion_gcc: 0,
    dias_atraso: 0,
    importe_pagado_sigep: null,
    numero_c31: '',
    monto_c31: null,
    fecha_aprobacion_fiscal: '',
    fecha_elaboracion_planilla: '',
    fecha_desembolso: '',
  })
  errors.value = {}
}

async function guardar() {
  enviando.value = true
  errors.value = {}
  try {
    const formData = new FormData()
    for (const key in form) {
      if (form[key] !== null && form[key] !== '') {
        formData.append(key, form[key])
      }
    }

    await axios.post(`/api/contratos/${props.idContrato}/planillas`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    showToast('Planilla registrada correctamente.', 'success')
    resetForm()
    emit('created')
    emit('close')

  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors
    } else {
      console.error(e)
      showToast('Ocurrió un error al guardar la planilla.', 'error')
    }
  } finally {
    enviando.value = false
  }
}

function cerrar() {
  resetForm()
  emit('close')
}

const sOverlay = {
  position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
  background: 'rgba(0,0,0,0.75)', display: 'flex',
  alignItems: 'center', justifyContent: 'center', zIndex: 999999,
}
const sBox = {
  background: '#0d1f30', border: '1px solid #1e3a52', borderRadius: '12px',
  padding: '24px', width: '700px', maxWidth: '94vw', color: '#c8dae7',
  maxHeight: '90vh', overflowY: 'auto',
}
const sLabel = { display: 'block', fontSize: '0.72rem', color: '#8ea9bf', margin: '0 0 4px' }
const sInput = {
  width: '100%', boxSizing: 'border-box', background: '#091520',
  border: '1px solid #1e3a52', borderRadius: '6px', padding: '8px 9px',
  color: '#c8dae7', fontSize: '0.85rem',
}
const sError = { color: '#f87171', fontSize: '0.65rem', marginTop: '4px', display: 'block' }
const sHint = { color: '#647a8e', fontSize: '0.65rem', marginTop: '4px', lineHeight: '1.4' }
const sField = { display: 'flex', flexDirection: 'column' }
const sFieldFull = { ...sField, gridColumn: '1 / -1' }
const sGrid = { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px 16px' }
const sGrid3 = { display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: '14px 16px' }
const sSubcard = { border: '1px solid #1e3a52', borderRadius: '10px', background: '#0a1624', padding: '14px 16px', marginTop: '18px' }
const sSubcardHeader = {
  display: 'flex', alignItems: 'center', gap: '8px', paddingBottom: '10px', marginBottom: '14px',
  borderBottom: '1px solid #1e3a52', color: '#00c9a7', fontSize: '0.74rem', fontWeight: '700',
  textTransform: 'uppercase', letterSpacing: '0.03em',
}
const sActions = { display: 'flex', justifyContent: 'flex-end', gap: '8px', marginTop: '20px' }
const sBtnCancel = {
  padding: '9px 18px', borderRadius: '6px', border: '1px solid #1e3a52',
  background: 'transparent', color: '#8ea9bf', cursor: 'pointer', fontSize: '0.82rem',
}
const sBtnSave = {
  padding: '9px 18px', borderRadius: '6px', border: 'none',
  background: '#00c9a7', color: '#04211c', fontWeight: '600',
  cursor: 'pointer', fontSize: '0.82rem',
}
</script>

<template>
  <Teleport to="body">
    <div v-if="show" :style="sOverlay" @click.self="cerrar">
      <div :style="sBox">
        <h2 style="margin:0 0 4px;font-size:1.1rem;">Nueva planilla</h2>
        <p style="margin:0 0 16px;font-size:0.72rem;color:#8ea9bf;">
          Certificación de avance para este contrato.
        </p>

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
              <input v-model.number="form.monto_certificado" type="number" step="0.01" min="0" :style="sInput" placeholder="0.00" />
              <span :style="sError" v-if="errors.monto_certificado">{{ errors.monto_certificado[0] }}</span>
            </div>
            <div :style="sField">
              <label :style="sLabel">Retenciones (Bs)</label>
              <input v-model.number="form.retencion_gcc" type="number" step="0.01" min="0" :style="sInput" />
              <div :style="sHint">Opcional — solo si esta planilla tiene retención en efectivo.</div>
            </div>

            <div :style="sFieldFull">
              <label :style="sLabel">Días de atraso</label>
              <input v-model.number="form.dias_atraso" type="number" min="0" :style="sInput" style="max-width:220px;" />
              <div :style="sHint">0 si no hubo atraso. Genera multa automática.</div>
            </div>

            <div :style="sFieldFull">
              <div :style="sHint">La amortización de anticipo y el líquido pagable se calculan automáticamente.</div>
            </div>
          </div>

          <div :style="sSubcard">
            <div :style="sSubcardHeader">
              <i class="ti ti-receipt-2"></i>
              <span>Seguimiento de pago</span>
            </div>

            <div :style="sGrid3">
              <div :style="sField">
                <label :style="sLabel">Importe pagado según SIGEP (Bs)</label>
                <input v-model.number="form.importe_pagado_sigep" type="number" step="0.01" min="0" :style="sInput" />
                <span :style="sError" v-if="errors.importe_pagado_sigep">{{ errors.importe_pagado_sigep[0] }}</span>
              </div>
              <div :style="sField">
                <label :style="sLabel">N° de C-31</label>
                <input v-model="form.numero_c31" type="text" :style="sInput" placeholder="Ej. N° 10236" />
                <span :style="sError" v-if="errors.numero_c31">{{ errors.numero_c31[0] }}</span>
              </div>
              <div :style="sField">
                <label :style="sLabel">Monto C-31 (Bs)</label>
                <input v-model.number="form.monto_c31" type="number" step="0.01" min="0" :style="sInput" />
                <span :style="sError" v-if="errors.monto_c31">{{ errors.monto_c31[0] }}</span>
              </div>

              <div :style="sField">
                <label :style="sLabel">Fecha aprobación del fiscal</label>
                <input v-model="form.fecha_aprobacion_fiscal" type="date" :style="sInput" />
                <span :style="sError" v-if="errors.fecha_aprobacion_fiscal">{{ errors.fecha_aprobacion_fiscal[0] }}</span>
              </div>
              <div :style="sField">
                <label :style="sLabel">Fecha elaboración de planilla</label>
                <input v-model="form.fecha_elaboracion_planilla" type="date" :style="sInput" />
                <span :style="sError" v-if="errors.fecha_elaboracion_planilla">{{ errors.fecha_elaboracion_planilla[0] }}</span>
              </div>
              <div :style="sField">
                <label :style="sLabel">Fecha de desembolso</label>
                <input v-model="form.fecha_desembolso" type="date" :style="sInput" />
                <span :style="sError" v-if="errors.fecha_desembolso">{{ errors.fecha_desembolso[0] }}</span>
                <div :style="sHint">Los días de demora se calculan solos.</div>
              </div>
            </div>
          </div>

          <div :style="sActions">
            <button type="button" :style="sBtnCancel" @click="cerrar">Cancelar</button>
            <button type="submit" :style="sBtnSave" :disabled="enviando">
              {{ enviando ? 'Guardando...' : 'Guardar planilla' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>