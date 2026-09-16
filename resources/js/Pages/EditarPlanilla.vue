<script setup>
import { reactive, ref, computed } from 'vue'
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
  fecha_aprobacion_fiscal: soloFecha(props.planilla.fecha_aprobacion_fiscal),
  fecha_elaboracion_planilla: soloFecha(props.planilla.fecha_elaboracion_planilla),
  fecha_desembolso: soloFecha(props.planilla.fecha_desembolso),
  monto_certificado: props.planilla.monto_certificado,
  retencion_gcc: props.planilla.retencion_gcc,
  multa: props.planilla.multa ?? 0,
  importe_pagado_sigep: props.planilla.importe_pagado_sigep,
  numero_c31: props.planilla.numero_c31 ?? '',
  monto_c31: props.planilla.monto_c31,
})

const errors = ref({})
const enviando = ref(false)

// "Días de atraso" ya no se edita a mano — se recalcula solo, igual que
// "Días de Demora" (Desembolso − Aprobación Fiscal). Es informativo, no
// afecta la multa (que ahora es 100% manual, ver campo "Multa" abajo).
const diasAtrasoCalculado = computed(() => {
  if (!form.fecha_aprobacion_fiscal || !form.fecha_desembolso) return 0
  const desde = new Date(form.fecha_aprobacion_fiscal + 'T00:00:00')
  const hasta = new Date(form.fecha_desembolso + 'T00:00:00')
  if (isNaN(desde.getTime()) || isNaN(hasta.getTime())) return 0
  const dias = Math.round((hasta - desde) / 86400000)
  return Math.max(0, dias)
})

// --- Wizard de pasos (mismo patrón que NuevaPlanilla.vue) ---
const steps = [
  { n: 1, label: 'Periodo y fechas' },
  { n: 2, label: 'Montos y seguimiento' },
]
const currentStep = ref(1)
const errorPaso = ref('')

function irAPaso(n) {
  errorPaso.value = ''
  currentStep.value = n
}
function pasoAnterior() {
  errorPaso.value = ''
  currentStep.value = Math.max(currentStep.value - 1, 1)
}
function validarPasoActual() {
  errorPaso.value = ''
  if (currentStep.value === 1) {
    if (!form.periodo_desde || !form.periodo_hasta) {
      errorPaso.value = 'Completa el periodo (desde/hasta) antes de continuar.'
      return false
    }
  }
  return true
}
function onSubmit() {
  if (currentStep.value < steps.length) {
    if (!validarPasoActual()) return
    currentStep.value += 1
    return
  }
  guardar()
}

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
      if (errors.value.periodo_desde || errors.value.periodo_hasta) currentStep.value = 1
      else currentStep.value = 2
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

const sOverlay = {
  position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
  background: 'rgba(0,0,0,0.75)', display: 'flex',
  alignItems: 'center', justifyContent: 'center', zIndex: 999999,
}
const sBox = {
  background: '#0d1f30', border: '1px solid #1e3a52', borderRadius: '12px',
  padding: '24px', width: '780px', maxWidth: '96vw', color: '#c8dae7',
  maxHeight: '92vh', overflowY: 'auto',
}
const sLabel = { display: 'block', fontSize: '0.72rem', color: '#8ea9bf', margin: '0 0 4px' }
const sInput = {
  width: '100%', boxSizing: 'border-box', background: '#091520',
  border: '1px solid #1e3a52', borderRadius: '6px', padding: '8px 9px',
  color: '#c8dae7', fontSize: '0.85rem',
}
const sInputCalc = { ...sInput, background: '#091622', color: '#00c9a7', fontWeight: '700', cursor: 'not-allowed', borderColor: 'rgba(0,201,167,0.25)' }
const sTagAuto = { fontSize: '0.6rem', fontWeight: '700', padding: '1px 6px', borderRadius: '4px', background: 'rgba(0,201,167,0.12)', color: '#00c9a7', textTransform: 'none', marginLeft: '6px' }
const sError = { color: '#f87171', fontSize: '0.65rem', marginTop: '4px', display: 'block' }
const sHint = { color: '#647a8e', fontSize: '0.65rem', marginTop: '4px', lineHeight: '1.4' }
const sField = { display: 'flex', flexDirection: 'column' }
const sGrid = { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px 16px' }
const sActions = { display: 'flex', justifyContent: 'space-between', gap: '8px', marginTop: '20px' }
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
    <div :style="sOverlay" @click.self="cerrar">
      <div :style="sBox">
        <h2 style="margin:0 0 4px;font-size:1.1rem;">Editar planilla</h2>
        <p style="margin:0 0 16px;font-size:0.72rem;color:#8ea9bf;">N°{{ planilla.numero }}</p>

        <div style="display:flex;gap:22px;align-items:flex-start;">

          <!-- LÍNEA DE PASOS VERTICAL -->
          <div class="stepper-vertical">
            <div
              v-for="(s, idx) in steps"
              :key="s.n"
              class="stepper-row"
              :class="{ active: currentStep === s.n, done: currentStep > s.n }"
              @click="irAPaso(s.n)"
            >
              <div class="stepper-track">
                <div class="stepper-circle">
                  <i v-if="currentStep > s.n" class="ti ti-check"></i>
                  <span v-else>{{ s.n }}</span>
                </div>
                <div
                  v-if="idx < steps.length - 1"
                  class="stepper-connector-v"
                  :class="{ done: currentStep > s.n }"
                ></div>
              </div>
              <div class="stepper-label-v">{{ s.label }}</div>
            </div>
          </div>

          <div style="flex:1;min-width:0;">

            <p v-if="errorPaso" class="paso-error">
              <i class="ti ti-alert-circle"></i>
              {{ errorPaso }}
            </p>

            <form @submit.prevent="onSubmit">

              <!-- ==================================================
                   PASO 1 — Periodo y fechas
              =================================================== -->
              <div v-show="currentStep === 1" :style="sGrid">
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
                  <div :style="sHint">Déjala vacía si el pago todavía no se ha hecho.</div>
                </div>
                <div :style="sField">
                  <label :style="sLabel">
                    Días de atraso
                    <span :style="sTagAuto">Automático</span>
                  </label>
                  <input
                    :value="diasAtrasoCalculado + ' días'"
                    type="text"
                    disabled
                    :style="sInputCalc"
                  />
                  <div :style="sHint">
                    Desembolso − Aprobación Fiscal. Solo informativo — no afecta la multa.
                  </div>
                </div>
              </div>

              <!-- ==================================================
                   PASO 2 — Montos y seguimiento
              =================================================== -->
              <div v-show="currentStep === 2" :style="sGrid">
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
                  <label :style="sLabel">Multa (Bs)</label>
                  <input v-model.number="form.multa" type="number" step="0.01" min="0" :style="sInput" />
                  <div :style="sHint">Monto a criterio del usuario, sin cálculo automático.</div>
                  <span :style="sError" v-if="errors.multa">{{ errors.multa[0] }}</span>
                </div>
                <div :style="sField">
                  <div :style="sHint" style="margin-top:24px;">La amortización de anticipo y el líquido pagable se recalculan automáticamente.</div>
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
              </div>

              <div :style="sActions">
                <button
                  v-if="currentStep === 1"
                  type="button"
                  :style="sBtnCancel"
                  @click="cerrar"
                >
                  Cancelar
                </button>
                <button
                  v-else
                  type="button"
                  :style="sBtnCancel"
                  @click="pasoAnterior"
                >
                  Atrás
                </button>

                <button type="submit" :style="sBtnSave" :disabled="enviando">
                  <span v-if="currentStep < steps.length">Siguiente</span>
                  <span v-else>{{ enviando ? 'Guardando...' : 'Actualizar' }}</span>
                </button>
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.stepper-vertical { display: flex; flex-direction: column; flex-shrink: 0; width: 150px; padding-top: 4px; }
.stepper-row { display: flex; align-items: flex-start; gap: 12px; cursor: pointer; }
.stepper-track { display: flex; flex-direction: column; align-items: center; flex-shrink: 0; }
.stepper-circle { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #1e3a52; background: #0d1f30; color: #8ea9bf; font-weight: 700; font-size: .78rem; transition: .2s; flex-shrink: 0; }
.stepper-row.active .stepper-circle { border-color: #00c9a7; background: rgba(0, 201, 167, .12); color: #00c9a7; }
.stepper-row.done .stepper-circle { border-color: #00c9a7; background: #00c9a7; color: #04211c; }
.stepper-connector-v { width: 2px; flex: 1; min-height: 28px; background: #1e3a52; margin: 4px 0; transition: .2s; }
.stepper-connector-v.done { background: #00c9a7; }
.stepper-label-v { padding-top: 6px; font-size: .72rem; color: #8ea9bf; font-weight: 600; line-height: 1.3; }
.stepper-row.active .stepper-label-v, .stepper-row.done .stepper-label-v { color: #d0dde8; }
.paso-error { display: flex; align-items: center; gap: 8px; padding: 9px 12px; margin-bottom: 14px; border-radius: 7px; background: rgba(248, 113, 113, .1); border: 1px solid rgba(248, 113, 113, .3); color: #fca5a5; font-size: .78rem; }

@media (max-width: 640px) {
  .stepper-vertical { width: 100%; flex-direction: row; gap: 4px; }
  .stepper-row { flex-direction: column; align-items: center; flex: 1; }
  .stepper-track { flex-direction: row; }
  .stepper-connector-v { width: auto; height: 2px; flex: 1; margin: 14px 4px 0; }
  .stepper-label-v { text-align: center; padding-top: 4px; }
}
</style>