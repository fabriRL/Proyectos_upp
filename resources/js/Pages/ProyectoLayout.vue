<script setup>
import { useRoute } from 'vue-router'
import { ref, watch } from 'vue'
import axios from '@/lib/axios'

const route = useRoute()
const tabs = [
  { name: 'datos', label: '1. Datos generales', icon: 'ti-file-text' }, 
  { name: 'cronograma', label: '2. Cronograma', icon: 'ti-calendar-event' }, 
  { name: 'problemas', label: '3. Problemas', icon: 'ti-alert-circle' }, 
  { name: 'indicadores', label: '4. Resumen', icon: 'ti-chart-dots-3' }, 
  { name: 'contratos', label: '6. Contratos', icon: 'ti-clipboard-list' }, 
  { name: 'modificaciones', label: 'Modif. Contractuales', icon: 'ti-file-diff' },
  { name: 'planillas', label: 'Planillas', icon: 'ti-receipt' }, 
  { name: 'decretos', label: '7. Decreto Supremo', icon: 'ti-file-certificate' },
  { name: 'financiero', label: '8. Prog. financiera', icon: 'ti-chart-bar' }, 
  { name: 'reporte', label: 'Reporte', icon: 'ti-file-download' },
]

const nombreProyecto = ref('')
const cargandoNombre = ref(true)

async function cargarProyecto(codigo) {
  if (!codigo) return

  cargandoNombre.value = true

  try {
    const { data } = await axios.get(`/api/proyectos/${codigo}`)
    nombreProyecto.value = data?.nombre || ''
  } catch (e) {
    console.error('No se pudo cargar el nombre del proyecto:', e)
    nombreProyecto.value = ''
  } finally {
    cargandoNombre.value = false
  }
}

watch(
  () => route.params.codigo,
  (nuevoCodigo) => cargarProyecto(nuevoCodigo),
  { immediate: true }
)
</script>

<template>
<div>
<div class="project-nav">
<div class="project-context">
  <span>Proyecto activo</span>
  <strong v-if="cargandoNombre">{{ route.params.codigo }} · Cargando…</strong>
  <strong v-else-if="nombreProyecto">{{ route.params.codigo }} · {{ nombreProyecto }}</strong>
  <strong v-else>{{ route.params.codigo }}</strong>
</div>
<nav class="project-tabs" aria-label="Módulos del proyecto">
<RouterLink v-for="tab in tabs" :key="tab.name"
          :to="{ name: tab.name, params: { codigo: route.params.codigo } }"
          :class="{ active: route.name === tab.name }"><i :class="`ti ${tab.icon}`"></i>{{ tab.label }}</RouterLink>
</nav>
</div>
<RouterView />
</div>
</template>

<style scoped>
.project-nav {
background: #0d1f30;
border-bottom: 1px solid #1e3a52;
padding: 10px 20px 0
}
.project-context {
display: flex;
align-items: baseline;
gap: 8px;
padding-bottom: 10px
}
.project-context span {
font-size: .65rem;
text-transform: uppercase;
letter-spacing: .06em;
color: #00c9a7;
font-weight: 700
}
.project-context strong {
color: #c8dae7;
font-size: .77rem;
font-weight: 600
}
.project-tabs {
display: flex;
overflow-x: auto;
gap: 2px
}
.project-tabs a {
white-space: nowrap;
padding: 9px 10px;
color: #8ea9bf;
font-size: .72rem;
border-bottom: 2px solid transparent
}
.project-tabs a:hover {
color: #d4e4f0;
background: rgba(0, 201, 167, .05)
}
.project-tabs a.active {
color: #00c9a7;
border-bottom-color: #00c9a7;
background: rgba(0, 201, 167, .08)
}
.project-tabs i {
margin-right: 5px;
font-size: .85rem
}
@media(max-width:640px) {
.project-nav {
padding-left: 12px;
padding-right: 12px
  }
.project-context {
display: block
  }
.project-context strong {
display: block;
margin-top: 3px
  }
.project-tabs a {
padding: 9px 8px
  }
}
</style>