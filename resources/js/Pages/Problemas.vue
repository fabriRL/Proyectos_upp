<script setup>
import { computed, onMounted, watch, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useUtils } from '@/composables/useUtils.js'
import { useProblemas } from '@/composables/useProblemas.js'
import NuevoProblema from '@/Pages/NuevoProblema.vue'
import EditarProblema from '@/Pages/EditarProblema.vue'
import { useToast } from '@/composables/useToast.js'
const { showToast } = useToast()

const { badgeClass } = useUtils()
/* ============================================================
   PROYECTO (mismo patrón que Cronograma.vue)
============================================================ */
const props = defineProps({
  proyecto: { type: Object, default: null },
  proyectoId: { type: [Number, String], default: null },
  routeParam: { type: String, default: 'codigo' },
})
const route = useRoute()
const identificador = computed(() =>
  props.proyecto?.codigo ??
  props.proyecto?.id_proyecto ??
  props.proyectoId ??
  route.params[props.routeParam] ??
  route.params.codigo ??
  route.params.proyecto ??
  route.params.id ??
  null
)
/* ============================================================
   DATOS
============================================================ */
const {
  problemas, cargando, error,
  fetchProblemas, crearProblema, actualizarProblema,
  diasAbiertos,
} = useProblemas(identificador)
/* ============================================================
   MODALES (separados: crear vs editar)
============================================================ */
const mostrarNuevo = ref(false)
const problemaEditando = ref(null)
const guardando = ref(false)
const lColor = (imp) => imp === 'Crítico' ? '#f87171' : imp === 'Alto' ? '#f59e0b' : '#00c9a7'

function abrirCrear() {
  mostrarNuevo.value = true
}
function abrirEditar(p) {
  problemaEditando.value = p
}
async function onCrear(payload) {
  guardando.value = true
  try {
    await crearProblema(payload)
    mostrarNuevo.value = false
    showToast('Problema registrado correctamente.', 'success')
  } catch (e) {
    error.value = 'No se pudo guardar el problema. Revisa los campos.'
  } finally {
    guardando.value = false
  }
}


async function onActualizar(id, payload) {
  guardando.value = true
  try {
    await actualizarProblema(id, payload)
    problemaEditando.value = null
    showToast('Problema actualizado correctamente.', 'success')
  } catch (e) {
    error.value = 'No se pudo guardar el problema. Revisa los campos.'
  } finally {
    guardando.value = false
  }
}
/* ============================================================
   INICIO
============================================================ */
onMounted(fetchProblemas)
watch(identificador, fetchProblemas)
</script>
<template>
  <div class="p-5">
    <div class="flex justify-end mb-4">
      <button class="btn btn-sm btn-primary" @click="abrirCrear">
        <i class="ti ti-plus" aria-hidden="true"></i> Registrar problema
      </button>
    </div>
    <div v-if="cargando" class="text-sm" style="color:#8ea9bf;">Cargando problemas…</div>
    <div v-else-if="error" class="text-sm mb-3" style="color:#f87171;">{{ error }}</div>
    <div v-else-if="problemas.length === 0" class="text-sm" style="color:#8ea9bf;">
      Aún no hay problemas registrados para este proyecto.
    </div>
    <div v-for="(p, i) in problemas" :key="p.id_problema"
         class="rounded-xl overflow-hidden mb-3"
         style="background-color:#0d1f30; border:1px solid #1e3a52;">
      <div class="flex">
        <!-- Barra lateral de color -->
        <div class="w-1 shrink-0" :style="{ background: lColor(p.impacto) }" />
        <div class="flex-1 p-4">
          <!-- Cabecera -->
          <div class="flex justify-between items-start mb-2">
            <div class="flex flex-wrap items-center gap-2">
              <code class="text-[11px]" style="color:#8ea9bf;">#{{ i + 1 }}</code>
              <span :class="badgeClass(p.impacto)">{{ p.impacto }}</span>
              <span :class="badgeClass(p.estado)">{{ p.estado }}</span>
              <span class="text-[11px]" style="color:#8ea9bf;">{{ p.fecha_registro }}</span>
              <a v-if="p.archivo_resolucion_url" :href="p.archivo_resolucion_url" target="_blank" class="pdf-link">
                <i class="ti ti-file-type-pdf"></i> PDF
              </a>
            </div>
            <div class="flex items-center gap-2">
              <span v-if="diasAbiertos(p) > 0"
                    class="text-[11px] font-bold px-2 py-0.5 rounded-full"
                    :style="{
                      color: diasAbiertos(p) > 30 ? '#f87171' : '#f59e0b',
                      background: diasAbiertos(p) > 30 ? 'rgba(248,113,113,0.12)' : 'rgba(245,158,11,0.12)',
                      border: `1px solid ${diasAbiertos(p) > 30 ? 'rgba(248,113,113,0.3)' : 'rgba(245,158,11,0.3)'}`
                    }">
                {{ diasAbiertos(p) }} días
              </span>
              <button type="button" class="accion-btn accion-editar" title="Editar problema" @click="abrirEditar(p)">✎</button>
            </div>
          </div>
          <!-- Problema -->
          <div class="font-semibold mb-3" style="color:#d0dde8;">{{ p.problema_identificado }}</div>
          <!-- Solución + Responsable -->
          <div class="grid grid-cols-2 gap-4" style="padding-top:12px; border-top:1px solid #19354d;">
            <div>
              <div class="field-label">Solución propuesta</div>
              <div class="text-xs leading-relaxed" style="color:#c8dae7;">{{ p.solucion_propuesta }}</div>
            </div>
            <div>
              <div class="field-label">Responsable</div>
              <div class="text-xs" style="color:#c8dae7;">{{ p.responsable }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <NuevoProblema
      v-if="mostrarNuevo"
      :proyecto-id="identificador"
      :guardando="guardando"
      @crear="onCrear"
      @cerrar="mostrarNuevo = false"
    />

    <EditarProblema
      v-if="problemaEditando"
      :problema="problemaEditando"
      :guardando="guardando"
      @actualizar="onActualizar"
      @cerrar="problemaEditando = null"
    />
  </div>
</template>
<style scoped>
.accion-btn {
  width: 26px;
  height: 26px;
  flex: 0 0 26px;
  display: inline-flex;
  justify-content: center;
  align-items: center;
  border-radius: 6px;
  cursor: pointer;
  font-size: 13px;
  line-height: 1;
  transition: background .15s ease, border-color .15s ease, transform .15s ease;
}
.accion-editar {
  background: rgba(77, 179, 240, .10);
  border: 1px solid rgba(77, 179, 240, .22);
  color: #55b8ef;
}
.accion-editar:hover {
  background: rgba(77, 179, 240, .2);
  border-color: rgba(77, 179, 240, .35);
  transform: translateY(-1px);
}
.pdf-link {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  color: #00c9a7;
  font-size: 11px;
  font-weight: 700;
  text-decoration: none;
}
.pdf-link:hover {
  text-decoration: underline;
}
</style>