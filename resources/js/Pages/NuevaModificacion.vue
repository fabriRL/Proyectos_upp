<script setup>
import { reactive, ref, computed } from 'vue'
import axios from '@/lib/axios'
import { useToast } from '@/composables/useToast.js'
const { showToast } = useToast()

const props = defineProps({
  show: Boolean,
  idContrato: [Number, String],
  fechaActual: { type: String, default: null },
})
const emit = defineEmits(['close', 'created'])

const form = reactive({
  tipo_modificacion: '',
  numero_documento_modificatorio: '',
  cite_documento_aprobacion: '',
  nueva_fecha_conclusion: '',
  monto_modificacion: null,
  descripcion: '',
  estado_registro_sicoes: 'Pendiente de registro',
  fecha_informe_aprobacion: '',
  fecha_firma_documento: '',
  estado_documento: 'Vigente',
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

function resetForm() {
  Object.assign(form, {
    tipo_modificacion: '',
    numero_documento_modificatorio: '',
    cite_documento_aprobacion: '',
    nueva_fecha_conclusion: '',
    monto_modificacion: null,
    descripcion: '',
    estado_registro_sicoes: 'Pendiente de registro',
    fecha_informe_aprobacion: '',
    fecha_firma_documento: '',
    estado_documento: 'Vigente',
  })
  archivoPdf.value = null
  nombreArchivo.value = ''
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
const sBox = { background: '#0d1f30', border: '1px solid #1e3a52', borderRadius: '12px', padding: '24px', width: '700px', maxWidth: '94vw', color: '#c8dae7', maxHeight: '90vh', overflowY: 'auto' }
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
const sActions = { display: 'flex', justifyContent: 'flex-end', gap: '8px', marginTop: '20px' }
const sBtnCancel = { padding: '9px 18px', borderRadius: '6px', border: '1px solid #1e3a52', background: 'transparent', color: '#8ea9bf', cursor: 'pointer', fontSize: '0.82rem' }
const sBtnSave = { padding: '9px 18px', borderRadius: '6px', border: 'none', background: '#00c9a7', color: '#04211c', fontWeight: '600', cursor: 'pointer', fontSize: '0.82rem' }
const sFileBtn = { display: 'inline-flex', alignItems: 'center', gap: '8px', padding: '9px 14px', border: '1px dashed #1e3a52', borderRadius: '6px', background: '#091520', color: '#8ea9bf', cursor: 'pointer', fontSize: '0.8rem' }
const sFechaActualBox = { display: 'flex', alignItems: 'center', gap: '6px', padding: '7px 10px', marginBottom: '6px', borderRadius: '6px', background: 'rgba(251,191,36,0.08)', border: '1px solid rgba(251,191,36,0.25)', fontSize: '0.72rem', color: '#8ea9bf' }
</script>

<template>
  <Teleport to="body">
    <div v-if="show" :style="sOverlay" @click.self="cerrar">
      <div :style="sBox">
        <h2 style="margin:0 0 4px;font-size:1.1rem;">Nueva modificación contractual</h2>
        <p style="margin:0 0 16px;font-size:0.72rem;color:#8ea9bf;">
          Registra un cambio formal al contrato (adenda, ampliación de plazo, ajuste de monto, etc.)
        </p>

        <form @submit.prevent="guardar">
          <div :style="sGrid">
            <div :style="sField">
              <label :style="sLabel">Tipo de modificación</label>
              <input v-model="form.tipo_modificacion" type="text" :style="sInput" placeholder="Ej. Ampliación de plazo" />
              <span :style="sError" v-if="errors.tipo_modificacion">{{ errors.tipo_modificacion[0] }}</span>
            </div>
            <div :style="sField">
              <label :style="sLabel">N° Documento Modificatorio</label>
              <input v-model="form.numero_documento_modificatorio" type="text" :style="sInput" />
            </div>

            <div :style="sField">
              <label :style="sLabel">CITE / Documento de Aprobación</label>
              <input v-model="form.cite_documento_aprobacion" type="text" :style="sInput" />
            </div>
            <div :style="sField">
              <label :style="sLabel">Estado de Registro en SICOES</label>
              <select v-model="form.estado_registro_sicoes" :style="sInput">
                <option>Registrado</option>
                <option>Pendiente de registro</option>
                <option>No aplica</option>
              </select>
            </div>

            <div :style="sField">
              <label :style="sLabel">Nueva fecha de conclusión</label>
              <div :style="sFechaActualBox">
                <i class="ti ti-info-circle" style="color:#fbbf24;"></i>
                <span>Fecha actual: <strong style="color:#fbbf24;">{{ fechaActual ? fmtFecha(fechaActual) : 'sin fecha registrada' }}</strong></span>
              </div>
              <input v-model="form.nueva_fecha_conclusion" type="date" :style="sInput" />
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
              <span :style="sHint">Se calcula solo, comparando con la fecha actual mostrada arriba.</span>
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
            <div :style="sField">
              <label :style="sLabel">Estado del documento</label>
              <select v-model="form.estado_documento" :style="sInput">
                <option>Vigente</option>
                <option>Anulado</option>
                <option>En trámite</option>
              </select>
            </div>

            <div :style="sField">
              <label :style="sLabel">Archivo PDF</label>
              <label :style="sFileBtn">
                <i class="ti ti-file-upload"></i>
                <span>{{ nombreArchivo || 'Seleccionar PDF...' }}</span>
                <input type="file" accept="application/pdf" @change="onArchivoChange" style="display:none;" />
              </label>
              <span :style="sError" v-if="errors.archivo_pdf">{{ errors.archivo_pdf[0] }}</span>
            </div>

            <div :style="sFieldFull">
              <label :style="sLabel">Descripción de la modificación</label>
              <textarea v-model="form.descripcion" :style="sTextarea" placeholder="Detalle del cambio realizado..."></textarea>
            </div>
          </div>

          <div :style="sActions">
            <button type="button" :style="sBtnCancel" @click="cerrar">Cancelar</button>
            <button type="submit" :style="sBtnSave" :disabled="enviando">
              {{ enviando ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>