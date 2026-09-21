<script setup>
import { reactive, ref } from 'vue'
import axios from '@/lib/axios'
import { useToast } from '@/composables/useToast.js'

const props = defineProps({
  decreto: { type: Object, default: null },
})
const emit = defineEmits(['close', 'saved'])
const { showToast } = useToast()

const esEdicion = !!props.decreto
const form = reactive({
  numero_decreto: props.decreto?.numero_decreto ?? '',
  monto: props.decreto ? Number(props.decreto.monto) : null,
  fecha_decreto: props.decreto?.fecha_decreto ? String(props.decreto.fecha_decreto).slice(0, 10) : '',
  descripcion: props.decreto?.descripcion ?? '',
})
const errors = ref({})
const enviando = ref(false)

async function guardar() {
  enviando.value = true
  errors.value = {}
  const payload = {
    ...form,
    fecha_decreto: form.fecha_decreto || null,
    descripcion: form.descripcion?.trim() || null,
    monto: form.monto ?? 0,
  }
  try {
    if (esEdicion) {
      const { data } = await axios.put(`/api/decretos-supremos/${props.decreto.id_decreto_supremo}`, payload)
      const n = data.proyectos_actualizados
      showToast(
        n > 0
          ? `Decreto Supremo actualizado. Se actualizaron los datos de ${n} proyecto(s) que lo usan.`
          : 'Decreto Supremo actualizado correctamente.',
        'success'
      )
    } else {
      await axios.post('/api/decretos-supremos', payload)
      showToast('Decreto Supremo creado correctamente.', 'success')
    }
    emit('saved')
    emit('close')
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors ?? {}
    else {
      console.error(e)
      showToast('No se pudo guardar el Decreto Supremo.', 'error')
    }
  } finally {
    enviando.value = false
  }
}

const sOverlay = { position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.75)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 999999 }
const sBox = { background: '#0d1f30', border: '1px solid #1e3a52', borderRadius: '12px', padding: '24px', width: '480px', maxWidth: '92vw', color: '#c8dae7', maxHeight: '90vh', overflowY: 'auto' }
const sLabel = { display: 'block', fontSize: '0.72rem', color: '#8ea9bf', margin: '14px 0 4px' }
const sInput = { width: '100%', boxSizing: 'border-box', background: '#091520', border: '1px solid #1e3a52', borderRadius: '6px', padding: '9px 10px', color: '#c8dae7', fontSize: '0.85rem' }
const sError = { color: '#f87171', fontSize: '0.65rem', marginTop: '4px', display: 'block' }
const sHint = { color: '#647a8e', fontSize: '0.65rem', marginTop: '4px', lineHeight: '1.4' }
const sRow = { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }
const sActions = { display: 'flex', justifyContent: 'flex-end', gap: '8px', marginTop: '24px' }
const sBtnCancel = { padding: '9px 18px', borderRadius: '6px', border: '1px solid #1e3a52', background: 'transparent', color: '#8ea9bf', cursor: 'pointer', fontSize: '0.82rem' }
const sBtnSave = { padding: '9px 18px', borderRadius: '6px', border: 'none', background: '#00c9a7', color: '#04211c', fontWeight: '600', cursor: 'pointer', fontSize: '0.82rem' }
</script>

<template>
  <Teleport to="body">
    <div :style="sOverlay" @click.self="emit('close')">
      <div :style="sBox">
        <h2 style="margin:0 0 4px;font-size:1.1rem;">{{ esEdicion ? 'Editar Decreto Supremo' : 'Nuevo Decreto Supremo' }}</h2>
        <p style="margin:0 0 8px;font-size:0.72rem;color:#8ea9bf;">Catálogo general de Decretos Supremos.</p>

        <div
          v-if="esEdicion && decreto.proyectos_count > 0"
          style="margin:8px 0 0;padding:9px 11px;border-radius:7px;background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.25);color:#fbbf24;font-size:.7rem;line-height:1.4;"
        >
          Este decreto lo usan {{ decreto.proyectos_count }} proyecto(s). Si cambias el número o el monto,
          también se actualizarán en esos proyectos.
        </div>

        <form @submit.prevent="guardar">
          <label :style="sLabel">Número de Decreto Supremo</label>
          <input v-model="form.numero_decreto" type="text" :style="sInput" placeholder="Ej. D.S. N° 4826 del 16 de noviembre de 2022" />
          <span :style="sError" v-if="errors.numero_decreto">{{ errors.numero_decreto[0] }}</span>

          <div :style="sRow">
            <div>
              <label :style="sLabel">Monto (Bs)</label>
              <input v-model.number="form.monto" type="number" step="0.01" min="0" :style="sInput" placeholder="0.00" />
              <span :style="sError" v-if="errors.monto">{{ errors.monto[0] }}</span>
            </div>
            <div>
              <label :style="sLabel">Fecha del decreto</label>
              <input v-model="form.fecha_decreto" type="date" :style="sInput" />
              <span :style="sError" v-if="errors.fecha_decreto">{{ errors.fecha_decreto[0] }}</span>
            </div>
          </div>

          <label :style="sLabel">Descripción (opcional)</label>
          <textarea v-model="form.descripcion" rows="3" :style="{ ...sInput, resize: 'vertical', fontFamily: 'inherit' }"></textarea>
          <div :style="sHint">Los registros por proyecto (incrementos, puesta en marcha, auditoría) no se modifican desde aquí.</div>

          <div :style="sActions">
            <button type="button" :style="sBtnCancel" @click="emit('close')">Cancelar</button>
            <button type="submit" :style="sBtnSave" :disabled="enviando">
              {{ enviando ? 'Guardando...' : (esEdicion ? 'Guardar cambios' : 'Crear decreto') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
