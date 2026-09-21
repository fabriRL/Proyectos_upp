<script setup>
import { reactive, ref, computed } from 'vue'
import axios from 'axios'
import { useToast } from '@/composables/useToast.js'

const props = defineProps({
  show: Boolean,
  codigoProyecto: String,
  contratosExistentes: { type: Array, default: () => [] },
})
const emit = defineEmits(['close', 'created'])
const { showToast } = useToast()

const form = reactive({
  tipo_contrato: 'Obra',
  contratista: '',
  numero_minuta: '',
  fecha_firma_contrato: '',
  fecha_orden_proceder: '',
  fecha_entrega_provisional: '',
  fecha_entrega_definitiva: '',
  monto_vigente: null,
  anticipo: 0,
  anticipo_porcentaje: null,
  estado_contractual: 'Vigente',
  estado_fisico: 'En ejecución',
  fecha_conclusion_prevista: '',
})

const archivoOrdenProceder = ref(null)
const nombreArchivo = ref('')
const errors = ref({})
const enviando = ref(false)

const ROMANOS = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII', 'XIII', 'XIV', 'XV']

// El número de paquete es correlativo por proyecto (Paquete I, II, III...),
// no una opción fija — se calcula según cuántos "Paquete <romano>" ya
// existen en este proyecto.
const siguientePaquete = computed(() => {
  const patron = /^paquete\s+([ivxlcdm]+)$/i
  let maxIndice = -1
  for (const c of props.contratosExistentes) {
    const m = (c.tipo_contrato || '').match(patron)
    if (m) {
      const idx = ROMANOS.indexOf(m[1].toUpperCase())
      if (idx > maxIndice) maxIndice = idx
    }
  }
  const siguiente = ROMANOS[maxIndice + 1] || String(maxIndice + 2)
  return `Paquete ${siguiente}`
})

let tipoContratoPrevio = ''

function mostrarOpcionesTipoContrato(e) {
  tipoContratoPrevio = form.tipo_contrato
  form.tipo_contrato = ''
}

function restaurarTipoContratoSiVacio(e) {
  if (!form.tipo_contrato) {
    form.tipo_contrato = tipoContratoPrevio
  }
}

function diffDiasInclusive(desde, hasta) {
  if (!desde || !hasta) return null
  const d1 = new Date(desde + 'T00:00:00')
  const d2 = new Date(hasta + 'T00:00:00')
  if (isNaN(d1.getTime()) || isNaN(d2.getTime())) return null
  const dias = Math.round((d2 - d1) / 86400000) + 1
  return dias >= 0 ? dias : null
}

const plazoCalculado = computed(() =>
  diffDiasInclusive(form.fecha_orden_proceder, form.fecha_conclusion_prevista)
)

// Cuando hay % de anticipo, se muestra en vivo el monto en Bs calculado
// (referencia — el backend recalcula el valor real al guardar, no confía
// en este número).
const anticipoCalculado = computed(() => {
  if (!form.anticipo_porcentaje || !form.monto_vigente) return null
  return Math.round(form.monto_vigente * (form.anticipo_porcentaje / 100) * 100) / 100
})

function onArchivoChange(e) {
  const file = e.target.files[0]
  archivoOrdenProceder.value = file || null
  nombreArchivo.value = file ? file.name : ''
}

// --- Wizard de pasos (3: Datos, Fechas+Estados, Montos) ---
const steps = [
  { n: 1, label: 'Datos del contrato' },
  { n: 2, label: 'Fechas y Estados' },
  { n: 3, label: 'Montos' },
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
    if (!form.contratista?.trim()) {
      errorPaso.value = 'Debes ingresar el nombre del contratista.'
      return false
    }
  }
  if (currentStep.value === 2) {
    if (!form.fecha_orden_proceder) {
      errorPaso.value = 'La fecha de orden de proceder es obligatoria.'
      return false
    }
    if (!archivoOrdenProceder.value) {
      errorPaso.value = 'Debes adjuntar el PDF de orden de proceder antes de continuar.'
      return false
    }
  }
  if (currentStep.value === 3) {
    if (!form.monto_vigente || form.monto_vigente <= 0) {
      errorPaso.value = 'Debes ingresar el monto vigente del contrato.'
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
    tipo_contrato: 'Obra', contratista: '',
    numero_minuta: '', fecha_firma_contrato: '', fecha_orden_proceder: '',
    fecha_entrega_provisional: '', fecha_entrega_definitiva: '',
    monto_vigente: null, anticipo: 0, anticipo_porcentaje: null,
    estado_contractual: 'Vigente', estado_fisico: 'En ejecución',
    fecha_conclusion_prevista: '',
  })
  archivoOrdenProceder.value = null
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
    if (archivoOrdenProceder.value) {
      formData.append('archivo_orden_proceder', archivoOrdenProceder.value)
    }
    await axios.post(`/api/proyectos/${props.codigoProyecto}/contratos`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    showToast('Contrato registrado correctamente.', 'success')
    resetForm()
    emit('created')
    emit('close')
  } catch (e) {
    console.error('Error al guardar contrato:', e.response?.data ?? e)

    if (e.response?.status === 422) {
      errors.value = e.response.data?.errors ?? {}
      if (errors.value.contratista) currentStep.value = 1
      else if (errors.value.fecha_orden_proceder || errors.value.archivo_orden_proceder) currentStep.value = 2
      else if (errors.value.monto_vigente) currentStep.value = 3
      errorPaso.value = e.response.data?.message ?? 'Revisa los campos marcados en rojo.'
    } else {
      errorPaso.value = 'No se pudo guardar el contrato. Intenta nuevamente.'
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
const sBox = { background: '#0d1f30', border: '1px solid #1e3a52', borderRadius: '12px', padding: '24px', width: '780px', maxWidth: '96vw', color: '#c8dae7', maxHeight: '90vh', overflowY: 'auto' }
const sLabel = { display: 'block', fontSize: '0.72rem', color: '#8ea9bf', margin: '0 0 4px' }
const sInput = { width: '100%', boxSizing: 'border-box', background: '#091520', border: '1px solid #1e3a52', borderRadius: '6px', padding: '8px' }
const sInputCalc = { ...sInput, background: '#091622', color: '#00c9a7', fontWeight: '600', cursor: 'not-allowed', borderColor: 'rgba(0,201,167,0.25)' }
const sHint = { color: '#647a8e', fontSize: '0.65rem', marginTop: '4px', lineHeight: '1.4' }
const sError = { color: '#f87171', fontSize: '0.65rem', display: 'block', marginTop: '4px' }
const sField = { display: 'flex', flexDirection: 'column' }
const sFieldFull = { ...sField, gridColumn: '1 / -1' }
const sGrid = { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px 16px' }
const sActions = { display: 'flex', justifyContent: 'space-between', gap: '8px', marginTop: '20px' }
const sBtnCancel = { padding: '8px 16px', borderRadius: '6px', border: '1px solid #1e3a52', background: 'transparent', color: '#8ea9bf', cursor: 'pointer' }
const sBtnSave = { padding: '8px 16px', borderRadius: '6px', border: 'none', background: '#00c9a7', color: '#04211c', fontWeight: '600', cursor: 'pointer' }
const sFileBtn = { display: 'inline-flex', alignItems: 'center', gap: '8px', padding: '9px 14px', border: '1px dashed #1e3a52', borderRadius: '6px', background: '#091520', color: '#8ea9bf', cursor: 'pointer', fontSize: '0.8rem' }
</script>

<template>
  <Teleport to="body">
    <Transition name="modal-fade">
    <div v-if="show" :style="sOverlay" @click.self="cerrar">
      <div :style="sBox">
        <h2 style="margin:0 0 4px;font-size:1.1rem;">Nuevo contrato</h2>
        <p style="margin:0 0 18px;font-size:0.72rem;color:#8ea9bf;">
          Registra un nuevo contrato o paquete de obra para este proyecto.
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
                   PASO 1 — Datos del contrato
              =================================================== -->
              <div v-if="currentStep === 1" :style="sGrid" key="paso-1">
                <div :style="sFieldFull">
                  <label :style="sLabel">Contratista</label>
                  <input v-model="form.contratista" type="text" :style="sInput" />
                  <span :style="sError" v-if="errors.contratista">{{ errors.contratista[0] }}</span>
                </div>

                <div :style="sField">
                  <label :style="sLabel">Tipo de Contrato/Paquete</label>
                  <input v-model="form.tipo_contrato" type="text" list="tipos-contrato-list" :style="sInput" placeholder="Ej. Paquete I, Vías y Accesos, Supervisión" @focus="mostrarOpcionesTipoContrato" @blur="restaurarTipoContratoSiVacio" />
                  <datalist id="tipos-contrato-list">
                    <option>{{ siguientePaquete }}</option>
                    <option>Vías y Accesos</option><option>Supervisión</option>
                    <option>Obra</option><option>Consultoría</option>
                    <option>Bienes</option><option>Servicios</option>
                  </datalist>
                  <span :style="sError" v-if="errors.tipo_contrato">{{ errors.tipo_contrato[0] }}</span>
                </div>

                <div :style="sField">
                  <label :style="sLabel">N° de Minuta de contrato</label>
                  <input v-model="form.numero_minuta" type="text" :style="sInput" placeholder="Ej. EMAPA-UAL-CD 212/2023" />
                  <span :style="sError" v-if="errors.numero_minuta">{{ errors.numero_minuta[0] }}</span>
                </div>
              </div>

              <!-- ==================================================
                   PASO 2 — Fechas y Estados
              =================================================== -->
              <div v-else-if="currentStep === 2" :style="sGrid" key="paso-2">
                <div :style="sField">
                  <label :style="sLabel">Fecha de firma de contrato</label>
                  <input v-model="form.fecha_firma_contrato" type="date" :style="sInput" />
                </div>

                <div :style="sField">
                  <label :style="sLabel">Fecha de orden de proceder *</label>
                  <input v-model="form.fecha_orden_proceder" type="date" :style="sInput" />
                  <span :style="sError" v-if="errors.fecha_orden_proceder">{{ errors.fecha_orden_proceder[0] }}</span>
                </div>

                <div :style="sFieldFull">
                  <label :style="sLabel">PDF Orden de Proceder *</label>
                  <label :style="sFileBtn">
                    <i class="ti ti-file-upload"></i>
                    <span>{{ nombreArchivo || 'Seleccionar PDF (obligatorio)...' }}</span>
                    <input type="file" accept="application/pdf" @change="onArchivoChange" style="display:none;" />
                  </label>
                  <span :style="sHint">Sin este documento no se puede registrar el contrato.</span>
                  <span :style="sError" v-if="errors.archivo_orden_proceder">{{ errors.archivo_orden_proceder[0] }}</span>
                </div>

                <div :style="sField">
                  <label :style="sLabel">Fecha conclusión prevista</label>
                  <input v-model="form.fecha_conclusion_prevista" type="date" :style="sInput" />
                </div>

                <div :style="sField">
                  <label :style="sLabel">Plazo s/contrato (días)</label>
                  <input
                    :value="plazoCalculado !== null ? plazoCalculado + ' días' : '—'"
                    type="text"
                    disabled
                    :style="sInputCalc"
                  />
                  <div :style="sHint">Desde orden de proceder hasta conclusión prevista.</div>
                </div>

                <div :style="sField">
                  <label :style="sLabel">Fecha de entrega provisional</label>
                  <input v-model="form.fecha_entrega_provisional" type="date" :style="sInput" />
                  <div :style="sHint">Opcional. Se llena al hacer la recepción provisional de la obra.</div>
                </div>

                <div :style="sField">
                  <label :style="sLabel">Fecha de entrega definitiva</label>
                  <input v-model="form.fecha_entrega_definitiva" type="date" :style="sInput" />
                  <div :style="sHint">Al llenarla, el Avance Físico pasa automáticamente a 100%.</div>
                </div>

                <div :style="sField">
                  <label :style="sLabel">Estado contractual</label>
                  <select v-model="form.estado_contractual" :style="sInput">
                    <option>Vigente</option><option>Vencido</option>
                    <option>Cerrado</option><option>Paralizado</option>
                  </select>
                </div>

                <div :style="sField">
                  <label :style="sLabel">Estado físico</label>
                  <select v-model="form.estado_fisico" :style="sInput">
                    <option>En ejecución</option><option>Concluido</option>
                    <option>Paralizado</option><option>Vencido</option>
                  </select>
                </div>
              </div>

              <!-- ==================================================
                   PASO 3 — Montos
              =================================================== -->
              <div v-else :style="sGrid" key="paso-3">
                <div :style="sFieldFull">
                  <label :style="sLabel">Monto vigente (Bs)</label>
                  <input v-model.number="form.monto_vigente" type="number" step="0.01" min="0" :style="sInput" />
                  <span :style="sError" v-if="errors.monto_vigente">{{ errors.monto_vigente[0] }}</span>
                </div>

                <div :style="sField">
                  <label :style="sLabel">Anticipo (%)</label>
                  <input v-model.number="form.anticipo_porcentaje" type="number" step="0.01" min="0" max="100" :style="sInput" placeholder="Ej. 20" />
                  <div :style="sHint">Opcional. Si lo llenas, el monto en Bs se calcula solo.</div>
                </div>

                <div :style="sField">
                  <label :style="sLabel">Anticipo (Bs)</label>
                  <input
                    v-if="form.anticipo_porcentaje"
                    :value="anticipoCalculado !== null ? anticipoCalculado : 0"
                    type="text"
                    disabled
                    :style="sInputCalc"
                  />
                  <input
                    v-else
                    v-model.number="form.anticipo"
                    type="number"
                    step="0.01"
                    min="0"
                    :style="sInput"
                    placeholder="0.00"
                  />
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
                  <span v-else>{{ enviando ? 'Guardando...' : 'Guardar contrato' }}</span>
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