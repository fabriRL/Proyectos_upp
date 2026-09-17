<script setup>
import { reactive, ref, computed } from 'vue'
import axios from '@/lib/axios'
import { useToast } from '@/composables/useToast.js'

const props = defineProps({
  show: Boolean,
  idContrato: [Number, String],
  fechaActual: { type: String, default: null },
})
const emit = defineEmits(['close', 'created'])
const { showToast } = useToast()

const form = reactive({
  tipo_modificacion: '',
  numero_documento_modificatorio: '',
  cite_documento_aprobacion: '',
  nueva_fecha_conclusion: '',
  monto_modificacion: null,
  descripcion: '',
  estado_registro_sicoes: 'Pendiente',
  fecha_informe_aprobacion: '',
  fecha_firma_documento: '',
  estado_documento: 'Pendiente',
})

const archivoPdf = ref(null)
const nombreArchivo = ref('')
const errors = ref({})
const enviando = ref(false)

const meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic']
function fmtFecha(fecha) {
  if (!fecha) return '—'
  const soloFecha = String(fecha).split('T')[0]
  const d = new Date(soloFecha + 'T00:00:00')
  if (isNaN(d.getTime())) return '—'
  return `${d.getDate()}-${meses[d.getMonth()]}-${String(d.getFullYear()).slice(2)}`
}

// Mismo cálculo que hace el backend al guardar (resta de timestamps) —
// se muestra en vivo aquí como referencia mientras llenas el formulario.
const plazoModificadoCalculado = computed(() => {
  if (!props.fechaActual || !form.nueva_fecha_conclusion) return null
  const tsAnterior = new Date(props.fechaActual + 'T00:00:00').getTime()
  const tsNueva = new Date(form.nueva_fecha_conclusion + 'T00:00:00').getTime()
  if (isNaN(tsAnterior) || isNaN(tsNueva)) return null
  return Math.round((tsNueva - tsAnterior) / 86400000)
})

function onArchivoChange(e) {
  const file = e.target.files[0]
  archivoPdf.value = file || null
  nombreArchivo.value = file ? file.name : ''
}

// --- Wizard de pasos (3: Datos, Fechas y Montos, Estados) ---
const steps = [
  { n: 1, label: 'Datos de la modificación' },
  { n: 2, label: 'Fechas y Montos' },
  { n: 3, label: 'Estados' },
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
    if (!form.tipo_modificacion?.trim()) {
      errorPaso.value = 'Debes ingresar el tipo de modificación.'
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

function resetForm() {
  Object.assign(form, {
    tipo_modificacion: '',
    numero_documento_modificatorio: '',
    cite_documento_aprobacion: '',
    nueva_fecha_conclusion: '',
    monto_modificacion: null,
    descripcion: '',
    estado_registro_sicoes: 'Pendiente',
    fecha_informe_aprobacion: '',
    fecha_firma_documento: '',
    estado_documento: 'Pendiente',
  })
  archivoPdf.value = null
  nombreArchivo.value = ''
  errors.value = {}
  errorPaso.value = ''
  currentStep.value = 1
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
    if (archivoPdf.value) {
      formData.append('archivo_pdf', archivoPdf.value)
    }

    await axios.post(`/api/contratos/${props.idContrato}/modificaciones`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    showToast('Modificación contractual registrada correctamente.', 'success')
    resetForm()
    emit('created')
    emit('close')
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors
      if (errors.value.tipo_modificacion) currentStep.value = 1
      else if (errors.value.archivo_pdf || errors.value.nueva_fecha_conclusion || errors.value.monto_modificacion) currentStep.value = 2
      errorPaso.value = e.response.data?.message ?? 'Revisa los campos marcados en rojo.'
    } else {
      console.error(e)
      showToast('Ocurrió un error al guardar la modificación.', 'error')
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
const sBox = { background: '#0d1f30', border: '1px solid #1e3a52', borderRadius: '12px', padding: '24px', width: '760px', maxWidth: '96vw', color: '#c8dae7', maxHeight: '90vh', overflowY: 'auto' }
const sLabel = { display: 'block', fontSize: '0.72rem', color: '#8ea9bf', margin: '0 0 4px', display: 'flex', alignItems: 'center', gap: '6px' }
const sInput = { width: '100%', boxSizing: 'border-box', background: '#091520', border: '1px solid #1e3a52', borderRadius: '6px', padding: '8px 9px', color: '#c8dae7', fontSize: '0.85rem' }
const sInputCalc = { ...sInput, background: '#091622', color: '#00c9a7', fontWeight: '700', cursor: 'not-allowed', borderColor: 'rgba(0,201,167,0.25)' }
const sTagAuto = { fontSize: '0.6rem', fontWeight: '700', padding: '1px 6px', borderRadius: '4px', background: 'rgba(0,201,167,0.12)', color: '#00c9a7', textTransform: 'none' }
const sTextarea = { ...sInput, minHeight: '80px', resize: 'vertical', fontFamily: 'inherit' }
const sError = { color: '#f87171', fontSize: '0.65rem', marginTop: '4px', display: 'block' }
const sHint = { color: '#647a8e', fontSize: '0.65rem', marginTop: '4px', display: 'block', lineHeight: '1.4' }
const sField = { display: 'flex', flexDirection: 'column' }
const sFieldFull = { ...sField, gridColumn: '1 / -1' }
const sGrid = { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px 16px' }
const sActions = { display: 'flex', justifyContent: 'space-between', gap: '8px', marginTop: '20px' }
const sBtnCancel = { padding: '9px 18px', borderRadius: '6px', border: '1px solid #1e3a52', background: 'transparent', color: '#8ea9bf', cursor: 'pointer', fontSize: '0.82rem' }
const sBtnSave = { padding: '9px 18px', borderRadius: '6px', border: 'none', background: '#00c9a7', color: '#04211c', fontWeight: '600', cursor: 'pointer', fontSize: '0.82rem' }
const sFileBtn = { display: 'inline-flex', alignItems: 'center', gap: '8px', padding: '9px 14px', border: '1px dashed #1e3a52', borderRadius: '6px', background: '#091520', color: '#8ea9bf', cursor: 'pointer', fontSize: '0.8rem' }
const sFechaActualBox = { display: 'flex', alignItems: 'center', gap: '6px', padding: '7px 10px', marginBottom: '6px', borderRadius: '6px', background: 'rgba(251,191,36,0.08)', border: '1px solid rgba(251,191,36,0.25)', fontSize: '0.72rem', color: '#8ea9bf' }
</script>

<template>
  <Teleport to="body">
    <Transition name="modal-fade">
    <div v-if="show" :style="sOverlay" @click.self="cerrar">
      <div :style="sBox">
        <h2 style="margin:0 0 4px;font-size:1.1rem;">Nueva modificación contractual</h2>
        <p style="margin:0 0 16px;font-size:0.72rem;color:#8ea9bf;">
          Registra un cambio formal al contrato (adenda, ampliación de plazo, ajuste de monto, etc.)
        </p>

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

          <!-- CONTENIDO DEL FORMULARIO -->
          <div style="flex:1;min-width:0;">

            <p v-if="errorPaso" class="paso-error">
              <i class="ti ti-alert-circle"></i>
              {{ errorPaso }}
            </p>

            <form @submit.prevent="onSubmit" novalidate>

              <Transition name="fade-slide" mode="out-in">

              <!-- ==================================================
                   PASO 1 — Datos de la modificación
              =================================================== -->
              <div v-if="currentStep === 1" :style="sGrid" key="paso-1">
                <div :style="sField">
                  <label :style="sLabel">Tipo de modificación</label>
                  <input v-model="form.tipo_modificacion" type="text" :style="sInput" placeholder="Ej. Ampliación de plazo" />
                  <span :style="sError" v-if="errors.tipo_modificacion">{{ errors.tipo_modificacion[0] }}</span>
                </div>
                <div :style="sField">
                  <label :style="sLabel">N° Documento Modificatorio</label>
                  <input v-model="form.numero_documento_modificatorio" type="text" :style="sInput" />
                </div>

                <div :style="sFieldFull">
                  <label :style="sLabel">CITE / Documento de Aprobación</label>
                  <input v-model="form.cite_documento_aprobacion" type="text" :style="sInput" />
                </div>

                <div :style="sFieldFull">
                  <label :style="sLabel">Descripción de la modificación</label>
                  <textarea v-model="form.descripcion" :style="sTextarea" placeholder="Detalle del cambio realizado..."></textarea>
                </div>
              </div>

              <!-- ==================================================
                   PASO 2 — Fechas y Montos
              =================================================== -->
              <div v-else-if="currentStep === 2" :style="sGrid" key="paso-2">
                <div :style="sFieldFull">
                  <label :style="sLabel">Nueva fecha de conclusión</label>
                  <div :style="sFechaActualBox">
                    <i class="ti ti-info-circle" style="color:#fbbf24;"></i>
                    <span>Fecha actual: <strong style="color:#fbbf24;">{{ fechaActual ? fmtFecha(fechaActual) : 'sin fecha registrada' }}</strong></span>
                  </div>
                  <input v-model="form.nueva_fecha_conclusion" type="date" :style="sInput" style="max-width:260px;" />
                </div>

                <div :style="sField">
                  <label :style="sLabel">
                    Plazo modificado (días)
                    <span :style="sTagAuto">Automático</span>
                  </label>
                  <input
                    :value="plazoModificadoCalculado !== null ? (plazoModificadoCalculado >= 0 ? '+' : '') + plazoModificadoCalculado + ' días' : '—'"
                    type="text"
                    disabled
                    :style="sInputCalc"
                  />
                  <span :style="sHint">Se calcula solo, comparando con la fecha actual.</span>
                </div>
                <div :style="sField">
                  <label :style="sLabel">Monto de la modificación (Bs)</label>
                  <input v-model.number="form.monto_modificacion" type="number" step="0.01" :style="sInput" placeholder="0.00" />
                </div>

                <div :style="sField">
                  <label :style="sLabel">Fecha del informe de aprobación</label>
                  <input v-model="form.fecha_informe_aprobacion" type="date" :style="sInput" />
                </div>
                <div :style="sField">
                  <label :style="sLabel">Fecha de firma del documento</label>
                  <input v-model="form.fecha_firma_documento" type="date" :style="sInput" />
                </div>

                <div :style="sFieldFull">
                  <label :style="sLabel">Archivo PDF</label>
                  <label :style="sFileBtn">
                    <i class="ti ti-file-upload"></i>
                    <span>{{ nombreArchivo || 'Seleccionar PDF...' }}</span>
                    <input type="file" accept="application/pdf" @change="onArchivoChange" style="display:none;" />
                  </label>
                  <span :style="sError" v-if="errors.archivo_pdf">{{ errors.archivo_pdf[0] }}</span>
                </div>
              </div>

              <!-- ==================================================
                   PASO 3 — Estados
              =================================================== -->
              <div v-else :style="sGrid" key="paso-3">
                <div :style="sField">
                  <label :style="sLabel">Estado de Registro en SICOES</label>
                  <select v-model="form.estado_registro_sicoes" :style="sInput">
                    <option>Firmado y Reportado</option>
                    <option>Pendiente</option>
                  </select>
                </div>

                <div :style="sField">
                  <label :style="sLabel">Estado del documento</label>
                  <select v-model="form.estado_documento" :style="sInput">
                    <option>Concluido</option>
                    <option>Pendiente</option>
                  </select>
                </div>
              </div>

              </Transition>

              <!-- NAVEGACIÓN -->
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
                  <span v-else>{{ enviando ? 'Guardando...' : 'Guardar' }}</span>
                </button>
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>
    </Transition>
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
