<script setup>
import { reactive, ref, computed } from 'vue'
import axios from '@/lib/axios'
import { useToast } from '@/composables/useToast.js'

const props = defineProps({
  modificacion: { type: Object, required: true },
  esUltima: { type: Boolean, required: true },
  fechaActual: { type: String, default: null },
})
const emit = defineEmits(['close', 'updated'])
const { showToast } = useToast()

function soloFecha(valor) {
  if (!valor) return ''
  return String(valor).split('T')[0]
}

const form = reactive({
  tipo_modificacion: props.modificacion.tipo_modificacion ?? '',
  numero_documento_modificatorio: props.modificacion.numero_documento_modificatorio ?? '',
  cite_documento_aprobacion: props.modificacion.cite_documento_aprobacion ?? '',
  nueva_fecha_conclusion: soloFecha(props.modificacion.nueva_fecha_conclusion),
  monto_modificacion: props.modificacion.monto_modificacion,
  descripcion: props.modificacion.descripcion ?? '',
  estado_registro_sicoes: props.modificacion.estado_registro_sicoes ?? 'Pendiente',
  fecha_informe_aprobacion: soloFecha(props.modificacion.fecha_informe_aprobacion),
  fecha_firma_documento: soloFecha(props.modificacion.fecha_firma_documento),
  estado_documento: props.modificacion.estado_documento ?? 'Pendiente',
})

const archivoPdf = ref(null)
const nombreArchivo = ref('')
const errors = ref({})
const enviando = ref(false)

const meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic']
function fmtFecha(fecha) {
  if (!fecha) return '—'
  const soloF = String(fecha).split('T')[0]
  const d = new Date(soloF + 'T00:00:00')
  if (isNaN(d.getTime())) return '—'
  return `${d.getDate()}-${meses[d.getMonth()]}-${String(d.getFullYear()).slice(2)}`
}

const plazoModificadoCalculado = computed(() => {
  const base = props.modificacion.fecha_anterior
  if (!base || !form.nueva_fecha_conclusion) return null
  const tsAnterior = new Date(soloFecha(base) + 'T00:00:00').getTime()
  const tsNueva = new Date(form.nueva_fecha_conclusion + 'T00:00:00').getTime()
  if (isNaN(tsAnterior) || isNaN(tsNueva)) return null
  return Math.round((tsNueva - tsAnterior) / 86400000)
})

function onArchivoChange(e) {
  const file = e.target.files[0]
  archivoPdf.value = file || null
  nombreArchivo.value = file ? file.name : ''
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
    formData.append('_method', 'PUT')

    await axios.post(`/api/modificaciones/${props.modificacion.id_modificacion}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    showToast('Modificación actualizada correctamente.', 'success')
    emit('updated')
    emit('close')
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors ?? {}
    } else {
      console.error(e)
      showToast('Ocurrió un error al actualizar la modificación.', 'error')
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
const sLabel = { display: 'block', fontSize: '0.72rem', color: '#8ea9bf', margin: '0 0 4px', display: 'flex', alignItems: 'center', gap: '6px' }
const sInput = { width: '100%', boxSizing: 'border-box', background: '#091520', border: '1px solid #1e3a52', borderRadius: '6px', padding: '8px 9px', color: '#c8dae7', fontSize: '0.85rem' }
const sInputCalc = { ...sInput, background: '#091622', color: '#00c9a7', fontWeight: '700', cursor: 'not-allowed', borderColor: 'rgba(0,201,167,0.25)' }
const sInputDisabled = { ...sInput, opacity: '0.6', cursor: 'not-allowed' }
const sTagAuto = { fontSize: '0.6rem', fontWeight: '700', padding: '1px 6px', borderRadius: '4px', background: 'rgba(0,201,167,0.12)', color: '#00c9a7', textTransform: 'none' }
const sTagBloqueado = { fontSize: '0.6rem', fontWeight: '700', padding: '1px 6px', borderRadius: '4px', background: 'rgba(251,191,36,0.12)', color: '#fbbf24', textTransform: 'none' }
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
    <div :style="sOverlay" @click.self="cerrar">
      <div :style="sBox">
        <h2 style="margin:0 0 4px;font-size:1.1rem;">Editar modificación contractual</h2>
        <p style="margin:0 0 16px;font-size:0.72rem;color:#8ea9bf;">N°{{ modificacion.numero }} — {{ modificacion.tipo_modificacion }}</p>

        <form @submit.prevent="guardar">
          <div :style="sGrid">
            <div :style="sField">
              <label :style="sLabel">Tipo de modificación</label>
              <input v-model="form.tipo_modificacion" type="text" :style="sInput" />
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
                <option>Firmado y Reportado</option>
                <option>Pendiente</option>
              </select>
            </div>

            <div :style="sFieldFull">
              <label :style="sLabel">
                Nueva fecha de conclusión
                <span v-if="!esUltima" :style="sTagBloqueado">Bloqueada</span>
              </label>
              <div :style="sFechaActualBox" v-if="modificacion.fecha_anterior">
                <i class="ti ti-info-circle" style="color:#fbbf24;"></i>
                <span>Fecha anterior de esta modificación: <strong style="color:#fbbf24;">{{ fmtFecha(modificacion.fecha_anterior) }}</strong></span>
              </div>
              <input
                v-model="form.nueva_fecha_conclusion"
                type="date"
                :style="esUltima ? sInput : sInputDisabled"
                :disabled="!esUltima"
                style="max-width:260px;"
              />
              <span :style="sHint" v-if="!esUltima">
                Solo se puede cambiar la fecha de la modificación más reciente del contrato, para no romper el historial de las modificaciones posteriores.
              </span>
              <span :style="sError" v-if="errors.nueva_fecha_conclusion">{{ errors.nueva_fecha_conclusion[0] }}</span>
            </div>

            <div :style="sField" v-if="esUltima">
              <label :style="sLabel">
                Plazo modificado (días)
                <span :style="sTagAuto">Automático</span>
              </label>
              <input
                :value="plazoModificadoCalculado !== null ? (plazoModificadoCalculado >= 0 ? '+' : '') + plazoModificadoCalculado + ' días' : (modificacion.plazo_modificado_dias + ' días')"
                type="text"
                disabled
                :style="sInputCalc"
              />
            </div>
            <div :style="sField">
              <label :style="sLabel">Monto de la modificación (Bs)</label>
              <input v-model.number="form.monto_modificacion" type="number" step="0.01" :style="sInput" />
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
                <option>Concluido</option>
                <option>Pendiente</option>
              </select>
            </div>
            <div :style="sField">
              <label :style="sLabel">Archivo PDF</label>
              <a v-if="modificacion.archivo_pdf_url && !nombreArchivo" :href="modificacion.archivo_pdf_url" target="_blank" style="display:inline-flex;align-items:center;gap:6px;margin-bottom:8px;color:#00c9a7;font-weight:600;font-size:.78rem;text-decoration:none;">
                <i class="ti ti-file-type-pdf"></i> Ver PDF actual
              </a>
              <label :style="sFileBtn">
                <i class="ti ti-file-upload"></i>
                <span>{{ nombreArchivo || 'Reemplazar PDF...' }}</span>
                <input type="file" accept="application/pdf" @change="onArchivoChange" style="display:none;" />
              </label>
              <span :style="sError" v-if="errors.archivo_pdf">{{ errors.archivo_pdf[0] }}</span>
            </div>

            <div :style="sFieldFull">
              <label :style="sLabel">Descripción de la modificación</label>
              <textarea v-model="form.descripcion" :style="sTextarea"></textarea>
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