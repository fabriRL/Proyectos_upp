import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import PermisosUsuario from '@/Pages/PermisosUsuario.vue'

const routes = [
  { path: '/login', name: 'login', component: () => import('@/Pages/Login.vue'), meta: { public: true } },
  { path: '/', name: 'dashboard', component: () => import('@/Pages/Dashboard.vue') },
  { path: '/flujo', name: 'flujo', component: () => import('@/Pages/FlujoSistema.vue') },
  { path: '/proyectos', name: 'proyectos', component: () => import('@/Pages/ListaProyectos.vue'), meta: { permiso: 'proyectos.gestionar' } },
  { path: '/proyectos/nuevo', name: 'nuevo-proyecto', component: () => import('@/Pages/NuevoProyecto.vue'), meta: { permiso: 'proyectos.gestionar' } },
  { path: '/proyectos/:codigo/editar', name: 'editar-proyecto', component: () => import('@/Pages/EditarProyecto.vue'), meta: { permiso: 'proyectos.gestionar' } },

  { path: '/reportes', name: 'reportes', component: () => import('@/Pages/HistorialReportes.vue'), meta: { permiso: 'reportes.gestionar' } },
  { path: '/reportes/generar', name: 'generar-reporte', component: () => import('@/Pages/GenerarReporte.vue'), meta: { permiso: 'reportes.gestionar' } },

  { path: '/permisos', name: 'permisos', component: PermisosUsuario, meta: { permiso: 'roles.gestionar' } },

  {
    path: '/proyectos/:codigo',
    component: () => import('@/Pages/ProyectoLayout.vue'),
    children: [
      { path: 'datos', name: 'datos', component: () => import('@/Pages/DatosGenerales.vue'), meta: { permiso: 'proyectos.gestionar' } },
      { path: 'cronograma', name: 'cronograma', component: () => import('@/Pages/Cronograma.vue'), meta: { permiso: 'cronograma.gestionar' } },
      { path: 'problemas', name: 'problemas', component: () => import('@/Pages/Problemas.vue'), meta: { permiso: 'problemas.gestionar' } },
      { path: 'indicadores', name: 'indicadores', component: () => import('@/Pages/ResumenIndicadores.vue'), meta: { permiso: 'resumen.ver' } },
      { path: 'contratos', name: 'contratos', component: () => import('@/Pages/Contratos.vue'), meta: { permiso: 'contratos.gestionar' } },
      { path: 'planillas', name: 'planillas', component: () => import('@/Pages/PlanillasPago.vue'), meta: { permiso: 'planillas.gestionar' } },
      { path: 'decretos', name: 'decretos', component: () => import('@/Pages/MontosDecretoSupremo.vue'), meta: { permiso: 'decretos.gestionar' } },
      { path: 'financiero', name: 'financiero', component: () => import('@/Pages/ProgramacionFinanciera.vue'), meta: { permiso: 'financiero.gestionar' } },
      { path: 'modificaciones', name: 'modificaciones', component: () => import('@/Pages/ModificacionesContractuales.vue'), meta: { permiso: 'modificaciones.gestionar' } },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const { isAuthenticated, tienePermiso, fetchUser } = useAuth()

  if (!to.meta.public && !isAuthenticated.value) {
    return { name: 'login' }
  }

  if (to.name === 'login' && isAuthenticated.value) {
    return { name: 'dashboard' }
  }

  // Antes de decidir si el usuario puede entrar a la ruta, se refrescan
  // sus permisos reales desde el backend — así, si un administrador
  // cambió los permisos de su rol hace un momento, se aplican de
  // inmediato en la siguiente navegación, sin necesidad de cerrar sesión.
  if (isAuthenticated.value && !to.meta.public) {
    await fetchUser()
  }

  if (to.name !== 'dashboard' && to.meta.permiso && isAuthenticated.value && !tienePermiso(to.meta.permiso)) {
    return { name: 'dashboard' }
  }
})

export default router