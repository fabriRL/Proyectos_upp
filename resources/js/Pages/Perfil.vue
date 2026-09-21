<script setup>
import { computed } from 'vue'
import { useAuth } from '@/composables/useAuth'

const { currentUser } = useAuth()

const permisos = computed(() => currentUser.value?.permisos ?? [])

/* ============================================================
   AGRUPACIÓN EN "CARPETAS" POR MÓDULO
   ============================================================
   Misma agrupación que usa PermisosUsuario.vue para editar roles,
   aquí solo de lectura: se filtra a los permisos que el usuario
   realmente tiene.
============================================================ */
const definicionGrupos = [
  {
    key: 'proyectos',
    icono: 'ti-building',
    titulo: 'Gestión de Proyectos',
    descripcion: 'Módulos que viven dentro de un proyecto.',
    permisos: [
      'proyectos.gestionar',
      'cronograma.gestionar',
      'problemas.gestionar',
      'resumen.ver',
      'contratos.gestionar',
      'modificaciones.gestionar',
      'planillas.gestionar',
      'decretos.gestionar',
      'financiero.gestionar',
    ],
  },
  {
    key: 'sistema',
    icono: 'ti-settings',
    titulo: 'Sistema General',
    descripcion: 'Pantallas independientes, no atadas a un proyecto específico.',
    permisos: ['dashboard.ver', 'reportes.gestionar', 'roles.gestionar', 'decretos_supremos.gestionar', 'auditoria.ver'],
  },
]

const gruposConPermisos = computed(() =>
  definicionGrupos
    .map(g => ({
      ...g,
      items: permisos.value.filter(p => g.permisos.includes(p.nombre)),
    }))
    .filter(g => g.items.length)
)

function formatFecha(value) {
  if (!value) return null
  const d = new Date(value)
  if (isNaN(d)) return null
  return d.toLocaleDateString('es-BO', { day: '2-digit', month: 'long', year: 'numeric' })
}

const creadoEn = computed(() => formatFecha(currentUser.value?.creado_en) || 'No disponible')
const ultimoAcceso = computed(() => formatFecha(currentUser.value?.ultimo_inicio_sesion) || 'Este es tu primer acceso registrado')
</script>

<template>
  <div class="p-5 perfil-page">

    <div class="perfil-header">
      <div class="avatar-grande">{{ currentUser?.initials || 'US' }}</div>
      <div>
        <h1>{{ currentUser?.name || 'Usuario' }}</h1>
        <p><i class="ti ti-shield-half-filled"></i> {{ currentUser?.role || 'Sin rol asignado' }}</p>
      </div>
    </div>

    <div class="perfil-layout">

      <!-- MIS DATOS -->
      <section class="perfil-card">
        <div class="card-header"><i class="ti ti-id-badge-2"></i> Mis datos</div>

        <dl class="datos-lista">
          <div class="dato-item">
            <dt>Nombre completo</dt>
            <dd>{{ currentUser?.name || '—' }}</dd>
          </div>
          <div class="dato-item">
            <dt>Correo electrónico</dt>
            <dd>{{ currentUser?.email || '—' }}</dd>
          </div>
          <div class="dato-item">
            <dt>Rol asignado</dt>
            <dd>{{ currentUser?.role || 'Sin rol asignado' }}</dd>
          </div>
          <div class="dato-item" v-if="currentUser?.roleDescription">
            <dt>Descripción del rol</dt>
            <dd>{{ currentUser.roleDescription }}</dd>
          </div>
          <div class="dato-item">
            <dt>Miembro desde</dt>
            <dd>{{ creadoEn }}</dd>
          </div>
          <div class="dato-item">
            <dt>Último acceso</dt>
            <dd>{{ ultimoAcceso }}</dd>
          </div>
        </dl>
      </section>

      <!-- PERMISOS -->
      <section class="perfil-card">
        <div class="card-header">
          <span><i class="ti ti-shield-check"></i> Permisos asignados</span>
          <span class="count-badge">{{ permisos.length }}</span>
        </div>
        <p class="card-sub">Estos son los módulos y acciones que tu rol te permite usar en el sistema.</p>

        <div v-if="!permisos.length" class="empty-permisos">
          <i class="ti ti-shield-off"></i>
          <span>No tienes permisos asignados todavía. Contacta a un administrador.</span>
        </div>

        <div v-for="grupo in gruposConPermisos" :key="grupo.key" class="grupo-permisos">
          <div class="grupo-header">
            <i :class="'ti ' + grupo.icono"></i>
            <div>
              <div class="grupo-titulo">{{ grupo.titulo }}</div>
              <div class="grupo-descripcion">{{ grupo.descripcion }}</div>
            </div>
            <span class="grupo-count">{{ grupo.items.length }}</span>
          </div>

          <div class="permiso-item" v-for="p in grupo.items" :key="p.id_permiso">
            <i class="ti ti-circle-check"></i>
            <div>
              <div class="permiso-nombre">{{ p.nombre }}</div>
              <div class="permiso-descripcion" v-if="p.descripcion">{{ p.descripcion }}</div>
            </div>
          </div>
        </div>
      </section>

    </div>
  </div>
</template>

<style scoped>
.perfil-page { max-width: 1000px; margin: auto; }

.perfil-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
}
.avatar-grande {
  width: 58px; height: 58px; flex: 0 0 58px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 14px;
  background: linear-gradient(145deg, rgba(0,201,167,.22), rgba(0,201,167,.05));
  border: 1px solid rgba(0,201,167,.3);
  color: #00d2ad; font-size: 1.15rem; font-weight: 800;
  box-shadow: 0 10px 28px rgba(0,0,0,.2);
}
.perfil-header h1 { margin: 0; color: #f2fbff; font-size: 1.2rem; font-weight: 800; }
.perfil-header p {
  display: flex; align-items: center; gap: 6px;
  margin: 5px 0 0; color: #8ea9bf; font-size: .8rem;
}
.perfil-header p i { color: #00c9a7; }

.perfil-layout {
  display: grid;
  grid-template-columns: 1fr 1.3fr;
  gap: 16px;
  align-items: start;
}

.perfil-card {
  border-radius: 12px;
  background: #0d1f30;
  border: 1px solid #1e3a52;
  padding: 18px 20px;
}

.card-header {
  display: flex; align-items: center; justify-content: space-between; gap: 8px;
  color: #f2fbff; font-weight: 750; font-size: .88rem;
  padding-bottom: 12px; margin-bottom: 12px;
  border-bottom: 1px solid #19354d;
}
.card-header i { color: #00c9a7; margin-right: 6px; }
.card-sub { margin: -6px 0 14px; color: #8ea9bf; font-size: .72rem; }

.count-badge {
  padding: 2px 9px; border-radius: 20px;
  background: rgba(0,201,167,.12); border: 1px solid rgba(0,201,167,.25);
  color: #00d2ad; font-size: .68rem; font-weight: 800;
}

/* MIS DATOS */
.datos-lista { display: flex; flex-direction: column; gap: 12px; }
.dato-item dt { color: #647a8e; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.dato-item dd { margin: 3px 0 0; color: #d4e4f0; font-size: .82rem; word-break: break-word; }

/* PERMISOS */
.empty-permisos {
  display: flex; align-items: center; gap: 10px;
  padding: 18px; border-radius: 9px;
  background: rgba(242,139,130,.06); border: 1px solid rgba(242,139,130,.2);
  color: #f0b8b3; font-size: .78rem;
}
.empty-permisos i { font-size: 18px; }

.grupo-permisos { margin-bottom: 14px; }
.grupo-permisos:last-child { margin-bottom: 0; }

.grupo-header {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px; border-radius: 8px;
  background: #0a1826; border: 1px solid #1b354a;
  margin-bottom: 8px;
}
.grupo-header i { color: #55b8ef; font-size: 16px; }
.grupo-titulo { color: #e4f0f7; font-weight: 750; font-size: .78rem; }
.grupo-descripcion { color: #647a8e; font-size: .66rem; margin-top: 1px; }
.grupo-count {
  margin-left: auto; padding: 1px 8px; border-radius: 20px;
  background: rgba(77,179,240,.12); border: 1px solid rgba(77,179,240,.22);
  color: #55b8ef; font-size: .65rem; font-weight: 800;
}

.permiso-item {
  display: flex; align-items: flex-start; gap: 9px;
  padding: 9px 12px 9px 16px;
  border-left: 2px solid rgba(0,201,167,.25);
}
.permiso-item i { color: #00c9a7; font-size: 14px; margin-top: 1px; }
.permiso-nombre { color: #e4f0f7; font-weight: 700; font-size: .74rem; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; }
.permiso-descripcion { color: #8ea9bf; font-size: .69rem; margin-top: 2px; }

@media (max-width: 800px) {
  .perfil-layout { grid-template-columns: 1fr; }
}
</style>
