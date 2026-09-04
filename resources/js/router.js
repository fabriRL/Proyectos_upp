import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const routes = [
  { path: '/login', name: 'login', component: () => import('@/Pages/Login.vue'), meta: { public: true } },
  { path: '/', name: 'dashboard', component: () => import('@/Pages/Dashboard.vue') },
  { path: '/flujo', name: 'flujo', component: () => import('@/Pages/FlujoSistema.vue') },
  { path: '/proyectos', name: 'proyectos', component: () => import('@/Pages/ListaProyectos.vue') },
  { path: '/proyectos/nuevo', name: 'nuevo-proyecto', component: () => import('@/Pages/NuevoProyecto.vue') },
  { path: '/proyectos/:codigo/editar', name: 'editar-proyecto', component: () => import('@/Pages/EditarProyecto.vue') },
  {
path: '/proyectos/:codigo',
component: () => import('@/Pages/ProyectoLayout.vue'),
children: [
      { path: 'datos', name: 'datos', component: () => import('@/Pages/DatosGenerales.vue') },
      { path: 'cronograma', name: 'cronograma', component: () => import('@/Pages/Cronograma.vue') },
      { path: 'problemas', name: 'problemas', component: () => import('@/Pages/Problemas.vue') },
      { path: 'indicadores', name: 'indicadores', component: () => import('@/Pages/ResumenIndicadores.vue') },
      { path: 'contratos', name: 'contratos', component: () => import('@/Pages/Contratos.vue') },
      { path: 'planillas', name: 'planillas', component: () => import('@/Pages/PlanillasPago.vue') },
      { path: 'decretos', name: 'decretos', component: () => import('@/Pages/MontosDecretoSupremo.vue') },
      { path: 'financiero', name: 'financiero', component: () => import('@/Pages/ProgramacionFinanciera.vue') },
      { path: 'reporte', name: 'reporte', component: () => import('@/Pages/GenerarReporte.vue') },
      { path: 'modificaciones', name: 'modificaciones', component: () => import('@/Pages/ModificacionesContractuales.vue') },
      
    ],
  },
]

const router = createRouter({
history: createWebHistory(),
routes,
})

router.beforeEach((to) => {
const { isAuthenticated } = useAuth()
if (!to.meta.public && !isAuthenticated.value) {
return { name: 'login' }
  }
if (to.name === 'login' && isAuthenticated.value) {
return { name: 'dashboard' }
  }
})

export default router