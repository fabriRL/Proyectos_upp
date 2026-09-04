<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const route = useRoute()
const router = useRouter()
const { currentUser, logout } = useAuth()
const isDark = ref(true)
defineEmits(['toggle-menu'])

const titles = { indicadores: 'Resumen de indicadores del proyecto', 'nuevo-proyecto': 'Registro de nuevo proyecto', flujo: 'Flujo del sistema', dashboard: 'Dashboard de seguimiento', proyectos: 'Proyectos de inversión', datos: 'Datos generales del proyecto', cronograma: 'Cronograma de actividades', contratos: 'Contratos y paquetes', planillas: 'Planillas de pago — Paquete I', problemas: 'Problemas y soluciones', financiero: 'Programación financiera', reporte: 'Generar reporte V5' }
const currentTitle = computed(() => titles[route.name] || '')

// Iniciales del usuario logueado: usa las que venga del backend si existen,
// si no, las calcula a partir del nombre (primeras letras de las dos primeras
// palabras), y solo cae en "US" si no hay ningún dato de usuario disponible.
const userInitials = computed(() => {
  if (currentUser.value?.initials) return currentUser.value.initials

  const nombre = currentUser.value?.name?.trim()
  if (!nombre) return 'US'

  const partes = nombre.split(/\s+/)
  const iniciales = partes.slice(0, 2).map(p => p[0]).join('')
  return iniciales.toUpperCase() || 'US'
})

function closeSession() { logout(); router.replace({ name: 'login' }) }
function applyTheme(dark) {
  isDark.value = dark
  document.documentElement.dataset.colorMode = dark ? 'dark' : 'light'
  localStorage.setItem('sipip-color-mode', dark ? 'dark' : 'light')
}
function toggleTheme() { applyTheme(!isDark.value) }
onMounted(() => applyTheme(localStorage.getItem('sipip-color-mode') !== 'light'))
</script>

<template>
  <div class="flex flex-col shrink-0">
    <div class="topbar-line" />
    <div class="navbar min-h-11 px-5 py-0" style="background-color:#091520; border-bottom:1px solid #19354d;">
      <div class="flex-1"><button class="btn btn-ghost btn-sm btn-square mr-2" style="color:#8ea9bf;"
          aria-label="Abrir o cerrar menú" @click="$emit('toggle-menu')"><i
            class="ti ti-menu-2 text-lg"></i></button><span class="text-sm font-semibold tracking-wide"
          style="color:#d0dde8;">{{ currentTitle }}</span></div>
      <div class="flex gap-2 items-center">
        <button class="theme-toggle" type="button" :aria-label="isDark ? 'Activar modo claro' : 'Activar modo nocturno'"
          :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo nocturno'" @click="toggleTheme">
          <i :class="isDark ? 'ti ti-sun' : 'ti ti-moon-stars'"></i>
        </button>
        <div class="dropdown dropdown-end"><button tabindex="0" class="avatar placeholder user-trigger"
            aria-label="Abrir menú de usuario">
            <div class="rounded-full w-7 text-xs font-bold"
              style="background-color:#122130; border:1px solid #1e3a52; color:#00c9a7; display:flex; align-items:center; justify-content:center;">
              {{ userInitials }}</div>
          </button>
          <ul tabindex="0" class="dropdown-content user-menu menu menu-sm mt-2 w-52 rounded-box p-2 shadow-xl">
            <li class="user-header">
              <span class="font-semibold" style="color:#d4e4f0;">{{ currentUser?.name || 'Usuario' }}</span>
              <small>{{ currentUser?.role || 'Usuario SIPIP' }}</small>
              <small v-if="currentUser?.email">{{ currentUser.email }}</small>
            </li>
            <li><button @click="router.push({ name: 'dashboard' })"><i class="ti ti-home"></i> Inicio</button></li>
            <li class="menu-divider"></li>
            <li><button class="logout" @click="closeSession"><i class="ti ti-logout"></i> Cerrar sesión</button></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.user-trigger {
  cursor: pointer
}

.theme-toggle{width:29px;height:29px;display:grid;place-items:center;border:1px solid #1e3a52;border-radius:50%;background:#122130;color:#facc15;transition:.2s}.theme-toggle:hover{border-color:#00c9a7;background:rgba(0,201,167,.1);color:#00c9a7}

.user-trigger:hover div {
  border-color: #00c9a7 !important
}

.user-menu {
  background: #0d1f30;
  border: 1px solid #1e3a52
}

.user-menu li button {
  color: #c8dae7
}

.user-menu li button:hover {
  background: rgba(0, 201, 167, .08);
  color: #75e5d2
}

.user-menu li button i {
  font-size: 1rem
}

.user-header {
  display: flex;
  flex-direction: column;
  padding: 8px 10px !important;
  pointer-events: none
}

.user-header small {
  font-size: .68rem;
  color: #8ea9bf;
  margin-top: 2px
}

.menu-divider {
  height: 1px !important;
  background: #19354d;
  margin: 5px 4px
}

.user-menu .logout {
  color: #fca5a5 !important
}

.user-menu .logout:hover {
  background: rgba(248, 113, 113, .09) !important;
  color: #fecaca !important
}

@media(max-width:480px) {
  .risk-badge {
    display: none !important
  }
}
</style>