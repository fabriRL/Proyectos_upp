<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/lib/axios'

defineProps({ collapsed: Boolean })
const emit = defineEmits(['toggle', 'navigate'])
const route = useRoute()

const nav = [
  { name: 'flujo', icon: 'ti-sitemap', label: 'Flujo del sistema' },
  { name: 'dashboard', icon: 'ti-layout-dashboard', label: 'Dashboard' },
  { name: 'decretos-supremos', icon: 'ti-file-certificate', label: 'Decretos Supremos' },
  { name: 'proyectos', icon: 'ti-building', label: 'Proyectos' },
  { name: 'reportes', icon: 'ti-report', label: 'Reportes' },
  { name: 'permisos', icon: 'ti-shield-lock', label: 'Permisos de Usuario' },
  { name: 'auditoria', icon: 'ti-history', label: 'Auditoría' },
]

const nombreProyectoActivo = ref('')
const cargandoProyectoActivo = ref(false)

async function cargarProyectoActivo(codigo) {
  if (!codigo) {
    nombreProyectoActivo.value = ''
    return
  }

  cargandoProyectoActivo.value = true
  try {
    const { data } = await axios.get(`/api/proyectos/${codigo}`)
    nombreProyectoActivo.value = data?.nombre || ''
  } catch (e) {
    console.error('No se pudo cargar el proyecto activo:', e)
    nombreProyectoActivo.value = ''
  } finally {
    cargandoProyectoActivo.value = false
  }
}

watch(
  () => route.params.codigo,
  (nuevoCodigo) => cargarProyectoActivo(nuevoCodigo),
  { immediate: true }
)
</script>

<template>
  <aside class="sidebar" :class="{ collapsed }">
    <div class="brand">
      <div class="brand-text">
        <div class="font-bold tracking-tight" style="font-size:1rem;color:#fff;letter-spacing:.08em;">SIPIP</div>
        <div class="text-xs mt-0.5" style="color:#8ea9bf;">Proyectos de Inversión Pública</div>
      </div><button class="collapse-btn" title="Contraer menú" @click="emit('toggle')"><i
          class="ti ti-layout-sidebar-left-collapse"></i></button>
    </div>

    <RouterLink
      v-if="route.params.codigo"
      :to="{ name: 'datos', params: { codigo: route.params.codigo } }"
      class="project-context"
      @click="emit('navigate')"
    >
      <i class="ti ti-building"></i>
      <div class="context-text">
        <div>{{ route.params.codigo }} · Activo</div>
        <small>{{ cargandoProyectoActivo ? 'Cargando…' : (nombreProyectoActivo || '—') }}</small>
      </div>
    </RouterLink>

    <ul class="menu menu-sm flex-1 p-2 gap-0.5">
      <li v-for="item in nav" :key="item.name">
        <RouterLink :to="{ name: item.name }" class="gap-2 rounded-lg" :class="route.name === item.name ? 'active' : ''"
          :title="item.label" @click="emit('navigate')"><i :class="'ti text-base ' + item.icon"></i><span>{{ item.label
            }}</span></RouterLink>
      </li>
    </ul>
    <div class="sidebar-footer">Actualización: 08/07/2026</div>
  </aside>
</template>

<style scoped>
.sidebar {
  width: 208px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #091520;
  border-right: 1px solid #19354d;
  transition: width .2s ease, transform .2s ease;
  z-index: 30
}

.brand {
  height: 65px;
  padding: 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #19354d;
  white-space: nowrap
}

.collapse-btn {
  color: #8ea9bf;
  width: 27px;
  height: 27px;
  border-radius: 6px
}

.collapse-btn:hover {
  background: #122130;
  color: #00c9a7
}

.project-context {
  margin: 8px;
  padding: 10px;
  display: flex;
  gap: 8px;
  border-radius: 12px;
  background: rgba(0, 201, 167, .1);
  border: 1px solid rgba(0, 201, 167, .2);
  color: #00c9a7;
  white-space: nowrap
}

.project-context>i {
  font-size: 17px;
  margin-top: 2px
}

.context-text {
  font-size: .72rem;
  font-weight: 700
}

.context-text small {
  display: block;
  color: #8ea9bf;
  font-size: .68rem;
  margin-top: 2px;
  font-weight: 400
}

.sidebar-footer {
  padding: 12px;
  color: #8ea9bf;
  border-top: 1px solid #19354d;
  font-size: 11px;
  opacity: .6;
  white-space: nowrap
}

.sidebar.collapsed {
  width: 64px
}

.sidebar.collapsed .brand {
  padding: 14px 10px;
  justify-content: center
}

.sidebar.collapsed .brand-text,
.sidebar.collapsed .context-text,
.sidebar.collapsed .sidebar-footer,
.sidebar.collapsed :deep(.menu span) {
  display: none
}

.sidebar.collapsed .collapse-btn .ti:before {
  content: '\eb1e'
}

.sidebar.collapsed .project-context {
  justify-content: center;
  padding: 10px;
  margin: 8px
}

.sidebar.collapsed :deep(.menu a) {
  justify-content: center;
  padding-left: 0;
  padding-right: 0
}

@media(max-width:767px) {
  .sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    width: 240px;
    box-shadow: 12px 0 30px rgba(0, 0, 0, .3)
  }

  .sidebar.collapsed {
    width: 240px;
    transform: translateX(-100%)
  }

  .sidebar.collapsed .brand {
    padding: 14px
  }

  .sidebar.collapsed .brand-text,
  .sidebar.collapsed .context-text,
  .sidebar.collapsed .sidebar-footer,
  .sidebar.collapsed :deep(.menu span) {
    display: block
  }

  .sidebar.collapsed .project-context {
    justify-content: flex-start;
    padding: 10px;
    margin: 8px
  }

  .sidebar.collapsed :deep(.menu a) {
    justify-content: flex-start;
    padding-left: 12px;
    padding-right: 12px
  }

  .sidebar.collapsed .collapse-btn .ti:before {
    content: '\eb30'
  }
}
</style>