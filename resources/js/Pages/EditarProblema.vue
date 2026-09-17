<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  problema: { type: Object, required: true },
  proyectoId: { type: [Number, String], required: true },
  guardando: { type: Boolean, default: false },
})
const emit = defineEmits(['actualizar', 'cerrar'])

const form = ref({
  fecha_registro: props.problema.fecha_registro?.slice(0, 10) ?? '',
  problema_identificado: props.problema.problema_identificado ?? '',
  solucion_propuesta: props.problema.solucion_propuesta ?? '',
  responsable: props.problema.responsable ?? '',
  estado: props.problema.estado ?? 'Pendiente',
  fecha_cierre: props.problema.fecha_cierre?.slice(0, 10) ?? '',
  id_actividad: props.problema.id_actividad ?? '',
})
const errorFormulario = ref(null)

const actividades = ref([])
async function cargarActividades() {
  try {
    const { data } = await axios.get(`/api/proyectos/${props.proyectoId}/actividades`)
    actividades.value = data
  } catch (e) {
    console.error('No se pudieron cargar las actividades del cronograma:', e)
  }
}
onMounted(cargarActividades)

// --- Documento de resolución (PDF) ---
const archivoResolucion = ref(null)
const nombreArchivo = ref('')

const tieneArchivoExistente = computed(() => !!props.problema.archivo_resolucion_url)
const puedeMarcarResuelto = computed(() => !!archivoResolucion.value || tieneArchivoExistente.value)

function onArchivoChange(e) {
  const file = e.target.files[0]
  archivoResolucion.value = file || null
  nombreArchivo.value = file ? file.name : ''
}

function quitarArchivo() {
  archivoResolucion.value = null
  nombreArchivo.value = ''
  if (!tieneArchivoExistente.value && form.value.estado === 'Resuelto') {
    form.value.estado = 'Pendiente'
  }
}

function validar() {
  errorFormulario.value = null
  if (!form.value.problema_identificado.trim()) {
    errorFormulario.value = 'Debes describir el problema identificado.'
    return false
  }
  if (!form.value.fecha_registro) {
    errorFormulario.value = 'Debes seleccionar la fecha de registro.'
    return false
  }
  if (form.value.estado === 'Resuelto' && !puedeMarcarResuelto.value) {
    errorFormulario.value = 'Debes adjuntar el PDF de resolución antes de marcar este problema como Resuelto.'
    return false
  }
  if (form.value.estado === 'Resuelto' && !form.value.fecha_cierre) {
    errorFormulario.value = 'Debes indicar la fecha de cierre antes de marcar este problema como Resuelto.'
    return false
  }
  return true
}

function guardar() {
  if (!validar()) return

  const formData = new FormData()
  for (const key in form.value) {
    if (form.value[key] !== null && form.value[key] !== '') {
      formData.append(key, form.value[key])
    }
  }
  if (archivoResolucion.value) {
    formData.append('archivo_resolucion', archivoResolucion.value)
  }
  formData.append('_method', 'PUT')

  emit('actualizar', props.problema.id_problema, formData)
}

function cerrar() {
  if (props.guardando) return
  emit('cerrar')
}
</script>
<template>
  <div class="modal-overlay" @click.self="cerrar">
    <div class="modal-problema">
      <div class="modal-header">
        <div class="modal-title">
          <div class="modal-icon">
            <i class="ti ti-pencil"></i>
          </div>
          <div>
            <h3>Editar problema</h3>
            <p>Modifica los datos del problema.</p>
          </div>
        </div>
        <button type="button" class="modal-close" @click="cerrar">
          <i class="ti ti-x"></i>
        </button>
      </div>

      <form class="form-problema" @submit.prevent="guardar">
        <div v-if="errorFormulario" class="form-error">
          <i class="ti ti-alert-circle"></i>
          <span>{{ errorFormulario }}</span>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Fecha</label>
            <div class="input-wrap">
              <i class="ti ti-calendar"></i>
              <input type="date" v-model="form.fecha_registro" required />
            </div>
          </div>
          <div class="form-group">
            <label>Estado</label>
            <div class="input-wrap">
              <i class="ti ti-flag"></i>
              <select v-model="form.estado">
                <option>Pendiente</option>
                <option>En proceso</option>
                <option value="Resuelto" :disabled="!puedeMarcarResuelto">Resuelto</option>
              </select>
            </div>
          </div>
          <div class="form-group form-full" v-if="form.estado === 'Resuelto'">
            <label>Fecha de cierre</label>
            <div class="input-wrap">
              <i class="ti ti-calendar-check"></i>
              <input type="date" v-model="form.fecha_cierre" :min="form.fecha_registro || undefined" required />
            </div>
            <p class="field-hint">Se mostrará en el grid de problemas junto con el estado "Resuelto".</p>
          </div>
          <div class="form-group form-full">
            <label>Problema identificado</label>
            <div class="input-wrap textarea-wrap">
              <i class="ti ti-alert-circle"></i>
              <textarea v-model="form.problema_identificado" rows="2" placeholder="Describe el problema..." required></textarea>
            </div>
          </div>
          <div class="form-group form-full">
            <label>Actividad afectada del cronograma</label>
            <div class="input-wrap">
              <i class="ti ti-timeline-event"></i>
              <select v-model="form.id_actividad">
                <option value="">— Ninguna (opcional) —</option>
                <option v-for="a in actividades" :key="a.id_actividad" :value="a.id_actividad">
                  N°{{ a.numero }} — {{ a.actividad }}
                </option>
              </select>
            </div>
            <p style="margin:4px 0 0;color:#647a8e;font-size:.64rem;">
              Si eliges una actividad, esta pasará a "Retrasada" automáticamente mientras el problema siga abierto.
            </p>
          </div>
          <div class="form-group">
            <label>Responsable</label>
            <div class="input-wrap">
              <i class="ti ti-user"></i>
              <input type="text" v-model="form.responsable" placeholder="Ej. Área Financiera" />
            </div>
          </div>
          <div class="form-group form-full">
            <label>Solución propuesta</label>
            <div class="input-wrap textarea-wrap">
              <i class="ti ti-bulb"></i>
              <textarea v-model="form.solucion_propuesta" rows="2" placeholder="¿Cómo se resolverá?"></textarea>
            </div>
          </div>

          <div class="form-group form-full">
            <label>Documento de resolución (PDF)</label>
            <label class="file-picker">
              <i class="ti ti-file-upload"></i>
              <span>{{ nombreArchivo || (tieneArchivoExistente ? 'Reemplazar PDF...' : 'Seleccionar PDF...') }}</span>
              <input type="file" accept="application/pdf" @change="onArchivoChange" style="display:none;" />
            </label>

            <div v-if="nombreArchivo" class="file-nuevo">
              <i class="ti ti-file-type-pdf"></i>
              <span>{{ nombreArchivo }}</span>
              <button type="button" class="file-quitar" @click="quitarArchivo">✕</button>
            </div>
            <div v-else-if="tieneArchivoExistente" class="file-existente">
              <i class="ti ti-file-type-pdf"></i>
              <a :href="problema.archivo_resolucion_url" target="_blank">Ver documento actual</a>
            </div>

            <p v-if="!puedeMarcarResuelto" class="field-hint">
              Debes adjuntar este documento antes de poder marcar el problema como "Resuelto".
            </p>
          </div>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-cancelar" :disabled="guardando" @click="cerrar">
            Cancelar
          </button>
          <button type="submit" class="btn-guardar" :disabled="guardando">
            <i v-if="guardando" class="ti ti-loader-2 spin"></i>
            <i v-else class="ti ti-device-floppy"></i>
            {{ guardando ? 'Guardando...' : 'Actualizar' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
  background: rgba(1, 8, 14, .78);
  backdrop-filter: blur(5px);
}
.modal-problema {
  width: min(600px, 100%);
  max-height: 92vh;
  overflow-y: auto;
  border: 1px solid #29485e;
  border-radius: 13px;
  background: linear-gradient(145deg, #0d2233, #0b1c2b);
  box-shadow: 0 25px 90px rgba(0, 0, 0, .58);
  scrollbar-width: thin;
  scrollbar-color: #31536b transparent;
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 15px;
  padding: 19px 20px;
  border-bottom: 1px solid #1b394e;
  background: linear-gradient(135deg, rgba(0, 201, 167, .045), transparent);
}
.modal-title {
  display: flex;
  align-items: center;
  gap: 11px;
}
.modal-icon {
  width: 38px;
  height: 38px;
  display: flex;
  justify-content: center;
  align-items: center;
  border: 1px solid rgba(0, 201, 167, .2);
  border-radius: 9px;
  background: rgba(0, 201, 167, .09);
  color: #00c9a7;
  font-size: 18px;
}
.modal-header h3 {
  margin: 0;
  color: #dceaf2;
  font-size: .92rem;
}
.modal-header p {
  margin: 4px 0 0;
  color: #718da2;
  font-size: .65rem;
}
.modal-close {
  width: 30px;
  height: 30px;
  display: flex;
  justify-content: center;
  align-items: center;
  border: 1px solid transparent;
  border-radius: 6px;
  background: transparent;
  color: #7893a7;
  font-size: 18px;
  cursor: pointer;
  transition: .15s;
}
.modal-close:hover {
  border-color: #29465c;
  background: rgba(255, 255, 255, .04);
  color: #e0edf5;
}
.form-problema {
  padding: 20px;
}
.form-error {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-bottom: 16px;
  padding: 10px 12px;
  border: 1px solid rgba(242, 139, 130, .25);
  border-radius: 7px;
  background: rgba(242, 139, 130, .07);
  color: #f28b82;
  font-size: .69rem;
  line-height: 1.4;
}
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.form-full {
  grid-column: 1 / -1;
}
.form-group label {
  color: #9cb5c6;
  font-size: .65rem;
  font-weight: 750;
}
.input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.input-wrap > i {
  position: absolute;
  left: 10px;
  top: 12px;
  z-index: 2;
  color: #5f7c91;
  font-size: 15px;
  pointer-events: none;
}
.textarea-wrap > i {
  top: 12px;
}
.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  box-sizing: border-box;
  min-height: 38px;
  padding: 9px 10px 9px 32px;
  border: 1px solid #29465c;
  border-radius: 7px;
  outline: none;
  background: #081a29;
  color: #d4e4f0;
  font-size: .7rem;
  font-family: inherit;
  transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
}
.form-group textarea {
  resize: vertical;
  min-height: 60px;
}
.form-group select {
  appearance: auto;
}
.form-group input::placeholder,
.form-group textarea::placeholder {
  color: #536e82;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  border-color: rgba(0, 201, 167, .65);
  background: #091d2c;
  box-shadow: 0 0 0 3px rgba(0, 201, 167, .07);
}
.file-picker {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 38px;
  padding: 9px 12px;
  border: 1px dashed #29465c;
  border-radius: 7px;
  background: #081a29;
  color: #91aabd;
  font-size: .7rem;
  cursor: pointer;
  transition: .15s;
}
.file-picker:hover {
  border-color: #00c9a7;
  color: #d4e4f0;
}
.file-picker i {
  font-size: 15px;
  color: #5f7c91;
}
.file-nuevo,
.file-existente {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 6px;
  font-size: .68rem;
}
.file-nuevo {
  color: #d4e4f0;
}
.file-nuevo i {
  color: #f28b82;
}
.file-existente a {
  color: #00c9a7;
  text-decoration: underline;
}
.file-existente i {
  color: #00c9a7;
}
.file-quitar {
  background: none;
  border: none;
  color: #f28b82;
  cursor: pointer;
  font-size: .72rem;
  padding: 0 4px;
}
.field-hint {
  margin: 6px 0 0;
  color: #647a8e;
  font-size: .64rem;
  line-height: 1.4;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 9px;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid #19374c;
}
.btn-cancelar,
.btn-guardar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  min-height: 37px;
  padding: 0 14px;
  border-radius: 7px;
  font-size: .68rem;
  font-weight: 750;
  cursor: pointer;
  transition: .15s;
}
.btn-cancelar {
  border: 1px solid #31516a;
  background: transparent;
  color: #91aabd;
}
.btn-cancelar:hover:not(:disabled) {
  background: rgba(255, 255, 255, .04);
  border-color: #45657b;
}
.btn-guardar {
  border: 1px solid rgba(0, 229, 192, .4);
  background: linear-gradient(135deg, #00d0aa, #00b99b);
  color: #04151b;
  box-shadow: 0 5px 15px rgba(0, 201, 167, .12);
}
.btn-guardar:hover:not(:disabled) {
  filter: brightness(1.07);
  transform: translateY(-1px);
}
.btn-cancelar:disabled,
.btn-guardar:disabled {
  opacity: .55;
  cursor: not-allowed;
}
.spin {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
@media (max-width: 640px) {
  .form-grid { grid-template-columns: 1fr; }
  .form-full { grid-column: auto; }
  .modal-overlay { align-items: flex-end; padding: 8px; }
  .modal-problema { max-height: 94vh; border-radius: 12px 12px 8px 8px; }
}
</style>