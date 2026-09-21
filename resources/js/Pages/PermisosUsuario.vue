<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from '@/lib/axios'
import { useToast } from '@/composables/useToast.js'

const { showToast } = useToast()

const roles = ref([])
const permisosDisponibles = ref([])
const idRolSeleccionado = ref(null)
const cargando = ref(true)
const error = ref(null)
const guardando = ref(false)

const mostrarModalRol = ref(false)
const nuevoRolNombre = ref('')
const nuevoRolDescripcion = ref('')
const guardandoRol = ref(false)

const rolSeleccionado = computed(() =>
  roles.value.find(r => r.id_rol === idRolSeleccionado.value) ?? null
)

const permisosMarcados = ref(new Set())

function sincronizarMarcados() {
  if (!rolSeleccionado.value) {
    permisosMarcados.value = new Set()
    return
  }
  permisosMarcados.value = new Set(rolSeleccionado.value.permisos.map(p => p.id_permiso))
}

function seleccionarRol(idRol) {
  idRolSeleccionado.value = idRol
  sincronizarMarcados()
}

function toggle(idPermiso) {
  if (permisosMarcados.value.has(idPermiso)) {
    permisosMarcados.value.delete(idPermiso)
  } else {
    permisosMarcados.value.add(idPermiso)
  }
  permisosMarcados.value = new Set(permisosMarcados.value)
}

/* ============================================================
   AGRUPACIÓN EN "CARPETAS" POR MÓDULO
   ============================================================
   "Gestión de Proyectos" reúne los 9 permisos que corresponden a
   pestañas DENTRO de un proyecto (van siempre juntos en la práctica:
   Datos Generales, Cronograma, Problemas, Resumen, Contratos,
   Modificaciones, Planillas, Decreto Supremo, Prog. Financiera).
   "Sistema General" son las 3 pantallas que no dependen de ningún
   proyecto en particular.
============================================================ */
const definicionGrupos = [
  {
    key: 'proyectos',
    icono: 'ti-building',
    titulo: 'Gestión de Proyectos',
    descripcion: 'Módulos que viven dentro de un proyecto — se usan juntos casi siempre.',
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

const grupos = computed(() =>
  definicionGrupos.map(g => ({
    ...g,
    items: g.permisos
      .map(nombre => permisosDisponibles.value.find(p => p.nombre === nombre))
      .filter(Boolean),
  }))
)

function contarMarcadosEnGrupo(grupo) {
  return grupo.items.filter(p => permisosMarcados.value.has(p.id_permiso)).length
}

const gruposAbiertos = ref(new Set([definicionGrupos[0].key]))

function toggleGrupo(key) {
  const nuevo = new Set(gruposAbiertos.value)
  nuevo.has(key) ? nuevo.delete(key) : nuevo.add(key)
  gruposAbiertos.value = nuevo
}

function marcarTodoElGrupo(grupo) {
  const nuevo = new Set(permisosMarcados.value)
  grupo.items.forEach(p => nuevo.add(p.id_permiso))
  permisosMarcados.value = nuevo
}

function quitarTodoElGrupo(grupo) {
  const nuevo = new Set(permisosMarcados.value)
  grupo.items.forEach(p => nuevo.delete(p.id_permiso))
  permisosMarcados.value = nuevo
}

async function cargar() {
  cargando.value = true
  error.value = null
  try {
    const [resRoles, resPermisos] = await Promise.all([
      axios.get('/api/roles'),
      axios.get('/api/permisos'),
    ])
    roles.value = resRoles.data
    permisosDisponibles.value = resPermisos.data

    if (roles.value.length && !idRolSeleccionado.value) {
      seleccionarRol(roles.value[0].id_rol)
    }
  } catch (e) {
    console.error(e)
    if (e.response?.status === 403) {
      error.value = 'No tienes permiso para administrar roles y permisos.'
    } else {
      error.value = 'No se pudieron cargar los roles y permisos.'
    }
  } finally {
    cargando.value = false
  }
}

async function guardarPermisos() {
  if (!rolSeleccionado.value) return
  guardando.value = true
  try {
    const { data } = await axios.put(`/api/roles/${rolSeleccionado.value.id_rol}/permisos`, {
      permisos: Array.from(permisosMarcados.value),
    })
    const idx = roles.value.findIndex(r => r.id_rol === data.id_rol)
    if (idx !== -1) roles.value[idx] = data
    showToast(`Permisos de "${data.nombre}" actualizados correctamente.`, 'success')
  } catch (e) {
    console.error(e)
    showToast('No se pudieron guardar los permisos.', 'error')
  } finally {
    guardando.value = false
  }
}

async function crearRol() {
  if (!nuevoRolNombre.value.trim()) {
    showToast('Escribe un nombre para el rol.', 'warning')
    return
  }
  guardandoRol.value = true
  try {
    const { data } = await axios.post('/api/roles', {
      nombre: nuevoRolNombre.value.trim(),
      descripcion: nuevoRolDescripcion.value.trim() || null,
    })
    roles.value.push(data)
    seleccionarRol(data.id_rol)
    mostrarModalRol.value = false
    nuevoRolNombre.value = ''
    nuevoRolDescripcion.value = ''
    showToast(`Rol "${data.nombre}" creado correctamente.`, 'success')
  } catch (e) {
    console.error(e)
    const msg = e.response?.data?.errors?.nombre?.[0]
    showToast(msg ?? 'No se pudo crear el rol.', 'error')
  } finally {
    guardandoRol.value = false
  }
}

onMounted(cargar)
</script>

<template>
  <div class="p-5 permisos-page">
    <div class="page-header">
      <div>
        <h1>Permisos de Usuario</h1>
        <p>Define qué módulos puede usar cada rol del sistema.</p>
      </div>
      <button class="btn-nuevo-rol" @click="mostrarModalRol = true">
        <i class="ti ti-plus"></i> Nuevo rol
      </button>
    </div>

    <div v-if="cargando" class="state-message">
      <i class="ti ti-loader-2 spinner"></i>
      <span>Cargando roles y permisos...</span>
    </div>

    <div v-else-if="error" class="state-message state-error">
      <i class="ti ti-alert-circle"></i>
      <span>{{ error }}</span>
    </div>

    <div v-else class="permisos-layout">

      <!-- LISTA DE ROLES -->
      <div class="roles-panel">
        <div class="roles-panel-header">Roles</div>
        <div
          v-for="r in roles"
          :key="r.id_rol"
          class="rol-item"
          :class="{ activo: r.id_rol === idRolSeleccionado }"
          @click="seleccionarRol(r.id_rol)"
        >
          <div class="rol-item-nombre">{{ r.nombre }}</div>
          <div class="rol-item-count">{{ r.permisos.length }} permiso(s)</div>
        </div>
      </div>

      <!-- CARPETAS DE PERMISOS DEL ROL SELECCIONADO -->
      <div class="permisos-panel">
        <template v-if="rolSeleccionado">
          <div class="permisos-panel-header">
            <div>
              <div class="permisos-panel-title">{{ rolSeleccionado.nombre }}</div>
              <p v-if="rolSeleccionado.descripcion">{{ rolSeleccionado.descripcion }}</p>
            </div>
            <button class="btn-guardar" @click="guardarPermisos" :disabled="guardando">
              <i v-if="guardando" class="ti ti-loader-2 spin-icon"></i>
              <i v-else class="ti ti-device-floppy"></i>
              {{ guardando ? 'Guardando...' : 'Guardar cambios' }}
            </button>
          </div>

          <div class="carpetas-wrap">
            <div v-for="grupo in grupos" :key="grupo.key" class="carpeta">

              <button type="button" class="carpeta-header" @click="toggleGrupo(grupo.key)">
                <div class="carpeta-header-left">
                  <i class="ti ti-chevron-right carpeta-chevron" :class="{ abierta: gruposAbiertos.has(grupo.key) }"></i>
                  <i :class="`ti ${grupo.icono} carpeta-icono`"></i>
                  <div>
                    <div class="carpeta-titulo">{{ grupo.titulo }}</div>
                    <div class="carpeta-descripcion">{{ grupo.descripcion }}</div>
                  </div>
                </div>
                <span class="carpeta-contador" :class="{ completo: contarMarcadosEnGrupo(grupo) === grupo.items.length }">
                  {{ contarMarcadosEnGrupo(grupo) }} / {{ grupo.items.length }}
                </span>
              </button>

              <div v-if="gruposAbiertos.has(grupo.key)" class="carpeta-body">
                <div class="carpeta-acciones">
                  <button type="button" class="accion-mini" @click="marcarTodoElGrupo(grupo)">Marcar todos</button>
                  <span class="accion-separador">·</span>
                  <button type="button" class="accion-mini" @click="quitarTodoElGrupo(grupo)">Quitar todos</button>
                </div>

                <label
                  v-for="p in grupo.items"
                  :key="p.id_permiso"
                  class="permiso-checkbox"
                  :class="{ marcado: permisosMarcados.has(p.id_permiso) }"
                >
                  <input
                    type="checkbox"
                    :checked="permisosMarcados.has(p.id_permiso)"
                    @change="toggle(p.id_permiso)"
                  />
                  <div>
                    <div class="permiso-nombre">{{ p.nombre }}</div>
                    <div class="permiso-descripcion">{{ p.descripcion }}</div>
                  </div>
                </label>
              </div>
            </div>
          </div>
        </template>

        <div v-else class="state-message">
          Selecciona un rol para ver y editar sus permisos.
        </div>
      </div>
    </div>

    <!-- MODAL: NUEVO ROL -->
    <Teleport to="body">
      <Transition name="modal-fade">
      <div v-if="mostrarModalRol" class="modal-overlay" @click.self="mostrarModalRol = false">
        <div class="modal-box">
          <h2>Nuevo rol</h2>
          <div class="field">
            <label>Nombre del rol</label>
            <input v-model="nuevoRolNombre" type="text" placeholder="Ej. Supervisor" :disabled="guardandoRol" />
          </div>
          <div class="field">
            <label>Descripción (opcional)</label>
            <input v-model="nuevoRolDescripcion" type="text" placeholder="Ej. Supervisa contratos y planillas" :disabled="guardandoRol" />
          </div>
          <div class="modal-actions">
            <button class="btn-cancelar" @click="mostrarModalRol = false" :disabled="guardandoRol">Cancelar</button>
            <button class="btn-guardar" @click="crearRol" :disabled="guardandoRol">
              {{ guardandoRol ? 'Guardando...' : 'Crear rol' }}
            </button>
          </div>
        </div>
      </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.permisos-page { max-width: 1200px; margin: auto; }

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 12px;
}
.page-header h1 { margin: 0; font-size: 1.25rem; font-weight: 800; color: #f2fbff; }
.page-header p { margin: 2px 0 0; color: #8ea9bf; font-size: .8rem; }

.btn-nuevo-rol {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 16px;
  border: none;
  border-radius: 8px;
  background: linear-gradient(135deg, #00d0ae, #00aa91);
  color: #052029;
  font-weight: 700;
  font-size: .82rem;
  cursor: pointer;
}

.state-message {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 44px 20px;
  color: #8ea9bf;
  font-size: .85rem;
}
.state-error { color: #fca5a5; }
.spinner { animation: spin 1s linear infinite; }
.spin-icon { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.permisos-layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 16px;
  align-items: start;
}

.roles-panel {
  border-radius: 12px;
  background: #0d1f30;
  border: 1px solid #1e3a52;
  overflow: hidden;
}
.roles-panel-header {
  padding: 14px 16px;
  border-bottom: 1px solid #19354d;
  color: #9cb5c6;
  font-size: .68rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .04em;
}
.rol-item {
  padding: 12px 16px;
  border-bottom: 1px solid #152a3e;
  cursor: pointer;
  transition: background .15s;
}
.rol-item:last-child { border-bottom: 0; }
.rol-item:hover { background: rgba(0, 201, 167, .05); }
.rol-item.activo { background: rgba(0, 201, 167, .1); border-left: 3px solid #00c9a7; }
.rol-item-nombre { color: #e4f0f7; font-weight: 700; font-size: .85rem; }
.rol-item-count { color: #647a8e; font-size: .68rem; margin-top: 2px; }

.permisos-panel {
  border-radius: 12px;
  background: #0d1f30;
  border: 1px solid #1e3a52;
  overflow: hidden;
}
.permisos-panel-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  padding: 16px 20px;
  border-bottom: 1px solid #19354d;
  flex-wrap: wrap;
}
.permisos-panel-title { color: #f2fbff; font-weight: 700; font-size: .95rem; }
.permisos-panel-header p { margin: 4px 0 0; color: #8ea9bf; font-size: .78rem; }

.btn-guardar {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border: none;
  border-radius: 8px;
  background: linear-gradient(135deg, #00d0ae, #00aa91);
  color: #052029;
  font-weight: 700;
  font-size: .8rem;
  cursor: pointer;
  flex-shrink: 0;
}
.btn-guardar:disabled { opacity: .6; cursor: not-allowed; }

/* --- Carpetas --- */
.carpetas-wrap { padding: 14px 16px; display: flex; flex-direction: column; gap: 10px; }

.carpeta {
  border: 1px solid #1e3a52;
  border-radius: 10px;
  overflow: hidden;
  background: #0a1624;
}

.carpeta-header {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 12px 14px;
  background: transparent;
  border: none;
  cursor: pointer;
  text-align: left;
}
.carpeta-header:hover { background: rgba(0, 201, 167, .04); }

.carpeta-header-left { display: flex; align-items: center; gap: 10px; min-width: 0; }

.carpeta-chevron {
  color: #647a8e;
  font-size: .8rem;
  transition: transform .15s;
  flex-shrink: 0;
}
.carpeta-chevron.abierta { transform: rotate(90deg); }

.carpeta-icono {
  color: #00c9a7;
  font-size: 1.05rem;
  flex-shrink: 0;
}

.carpeta-titulo { color: #f2fbff; font-weight: 700; font-size: .85rem; }
.carpeta-descripcion { color: #647a8e; font-size: .68rem; margin-top: 2px; }

.carpeta-contador {
  flex-shrink: 0;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: .68rem;
  font-weight: 700;
  background: rgba(142, 169, 191, .12);
  color: #8ea9bf;
  border: 1px solid rgba(142, 169, 191, .25);
}
.carpeta-contador.completo {
  background: rgba(0, 201, 167, .12);
  color: #00c9a7;
  border-color: rgba(0, 201, 167, .3);
}

.carpeta-body {
  padding: 4px 14px 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  border-top: 1px solid #152a3e;
}

.carpeta-acciones {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 2px 2px;
}
.accion-mini {
  background: none;
  border: none;
  color: #00c9a7;
  font-size: .68rem;
  font-weight: 700;
  cursor: pointer;
  padding: 0;
}
.accion-mini:hover { text-decoration: underline; }
.accion-separador { color: #3d5a72; font-size: .68rem; }

.permiso-checkbox {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px 12px;
  border: 1px solid #1e3a52;
  border-radius: 8px;
  background: #0d1f30;
  cursor: pointer;
  transition: .15s;
}
.permiso-checkbox:hover { border-color: #2e4d68; }
.permiso-checkbox.marcado { border-color: rgba(0, 201, 167, .4); background: rgba(0, 201, 167, .06); }
.permiso-checkbox input { margin-top: 3px; accent-color: #00c9a7; cursor: pointer; flex-shrink: 0; }
.permiso-nombre { color: #e4f0f7; font-weight: 700; font-size: .76rem; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; }
.permiso-descripcion { color: #8ea9bf; font-size: .69rem; margin-top: 2px; }

.modal-overlay {
  position: fixed; inset: 0; z-index: 999999;
  background: rgba(0,0,0,.75);
  display: flex; align-items: center; justify-content: center;
}
.modal-box {
  background: #0d1f30; border: 1px solid #1e3a52; border-radius: 12px;
  padding: 24px; width: 420px; max-width: 92vw; color: #c8dae7;
}
.modal-box h2 { margin: 0 0 16px; font-size: 1.05rem; }
.field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
.field label { font-size: .72rem; color: #8ea9bf; }
.field input {
  background: #091520; border: 1px solid #1e3a52; border-radius: 6px;
  padding: 9px 10px; color: #c8dae7; font-size: .85rem;
}
.modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 18px; }
.btn-cancelar {
  padding: 9px 16px; border-radius: 6px; border: 1px solid #1e3a52;
  background: transparent; color: #8ea9bf; cursor: pointer; font-size: .82rem;
}

@media (max-width: 800px) {
  .permisos-layout { grid-template-columns: 1fr; }
}
</style>