<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from '@/lib/axios'

/* ---------------- Etiquetas ---------------- */

const TIPO_LABELS = {
  Proyecto: 'Proyecto',
  ContratoProyecto: 'Contrato',
  PlanillaContrato: 'Planilla de pago',
  ModificacionContractual: 'Modificación contractual',
  Problema: 'Problema',
  DecretoSupremo: 'Decreto Supremo',
  ComponenteProyecto: 'Componente',
  Producto: 'Producto',
  UbicacionProyecto: 'Ubicación',
  BeneficiarioProyecto: 'Beneficiario',
  Actividad: 'Actividad (cronograma)',
  PartidaPresupuestaria: 'Partida presupuestaria',
  ObjetoGastoFinanciero: 'Objeto de gasto',
}

const ACCION_META = {
  creado: { label: 'Creado', badge: 'badge-success', icon: 'ti-plus' },
  actualizado: { label: 'Actualizado', badge: 'badge-info', icon: 'ti-pencil' },
  eliminado: { label: 'Eliminado', badge: 'badge-danger', icon: 'ti-trash' },
  eliminado_permanente: { label: 'Eliminado permanente', badge: 'badge-danger', icon: 'ti-trash-x' },
  restaurado: { label: 'Restaurado', badge: 'badge-warning', icon: 'ti-history-toggle' },
}

function tipoLabel(tipo) {
  return TIPO_LABELS[tipo] || tipo
}
function accionMeta(accion) {
  return ACCION_META[accion] || { label: accion, badge: 'badge-muted', icon: 'ti-circle' }
}

/* ---------------- Estado ---------------- */

const cargando = ref(true)
const error = ref(null)

const registros = ref([])
const paginacion = ref({ current_page: 1, last_page: 1, total: 0, per_page: 30 })

const resumen = ref({ total_registros: 0, cambios_hoy: 0, usuarios_activos: 0, por_accion: {} })

const opciones = ref({ usuarios: [], tipos_registro: [], acciones: [] })

const filtros = ref({
  id_usuario: '',
  tipo_registro: '',
  accion: '',
  desde: '',
  hasta: '',
  buscar: '',
})

let debounceBuscar = null

/* ---------------- Carga ---------------- */

async function cargarResumenYFiltros() {
  try {
    const [resResumen, resFiltros] = await Promise.all([
      axios.get('/api/auditoria/resumen'),
      axios.get('/api/auditoria/filtros'),
    ])
    resumen.value = resResumen.data
    opciones.value = resFiltros.data
  } catch (e) {
    console.error(e)
  }
}

async function cargarRegistros(pagina = 1) {
  cargando.value = true
  error.value = null

  try {
    const params = { page: pagina }
    if (filtros.value.id_usuario) params.id_usuario = filtros.value.id_usuario
    if (filtros.value.tipo_registro) params.tipo_registro = filtros.value.tipo_registro
    if (filtros.value.accion) params.accion = filtros.value.accion
    if (filtros.value.desde) params.desde = filtros.value.desde
    if (filtros.value.hasta) params.hasta = filtros.value.hasta
    if (filtros.value.buscar.trim()) params.buscar = filtros.value.buscar.trim()

    const { data } = await axios.get('/api/auditoria', { params })

    registros.value = data.data
    paginacion.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      total: data.total,
      per_page: data.per_page,
    }
  } catch (e) {
    console.error(e)
    error.value = e.response?.status === 403
      ? 'No tienes permiso para ver la auditoría del sistema.'
      : 'No se pudo cargar el historial de auditoría.'
  } finally {
    cargando.value = false
  }
}

function aplicarFiltros() {
  cargarRegistros(1)
}

function limpiarFiltros() {
  filtros.value = { id_usuario: '', tipo_registro: '', accion: '', desde: '', hasta: '', buscar: '' }
  cargarRegistros(1)
}

function irAPagina(pagina) {
  if (pagina < 1 || pagina > paginacion.value.last_page) return
  cargarRegistros(pagina)
}

watch(() => filtros.value.buscar, () => {
  clearTimeout(debounceBuscar)
  debounceBuscar = setTimeout(() => cargarRegistros(1), 400)
})

onMounted(() => {
  cargarResumenYFiltros()
  cargarRegistros(1)
})

/* ---------------- Detalle (diff) ---------------- */

const registroSeleccionado = ref(null)

function abrirDetalle(registro) {
  registroSeleccionado.value = registro
}
function cerrarDetalle() {
  registroSeleccionado.value = null
}

function formatValor(v) {
  if (v === null || v === undefined || v === '') return '—'
  if (typeof v === 'boolean') return v ? 'Sí' : 'No'
  if (typeof v === 'object') return JSON.stringify(v)
  return String(v)
}

const camposComparados = computed(() => {
  const r = registroSeleccionado.value
  if (!r) return []

  if (r.accion === 'actualizado') {
    const campos = Object.keys(r.valores_nuevos || {})
    return campos.map(campo => ({
      campo,
      anterior: formatValor(r.valores_anteriores?.[campo]),
      nuevo: formatValor(r.valores_nuevos?.[campo]),
    }))
  }

  const fuente = r.valores_nuevos || r.valores_anteriores || {}
  return Object.keys(fuente).map(campo => ({
    campo,
    anterior: null,
    nuevo: formatValor(fuente[campo]),
  }))
})

function formatFecha(value) {
  if (!value) return '—'
  const d = new Date(value)
  if (isNaN(d)) return '—'
  return d.toLocaleString('es-BO', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div class="p-5 auditoria-page">

    <div class="page-header">
      <div class="header-title">
        <div class="header-icon"><i class="ti ti-history"></i></div>
        <div>
          <h1>Auditoría del sistema</h1>
          <p>Historial de cambios realizados por los usuarios en el sistema.</p>
        </div>
      </div>
    </div>

    <!-- RESUMEN -->
    <section class="auditoria-summary">
      <div class="summary-item">
        <div class="summary-icon"><i class="ti ti-list-details"></i></div>
        <div><strong>{{ resumen.total_registros }}</strong><span>Registros totales</span></div>
      </div>
      <div class="summary-item">
        <div class="summary-icon blue"><i class="ti ti-calendar-event"></i></div>
        <div><strong>{{ resumen.cambios_hoy }}</strong><span>Cambios hoy</span></div>
      </div>
      <div class="summary-item">
        <div class="summary-icon purple"><i class="ti ti-users"></i></div>
        <div><strong>{{ resumen.usuarios_activos }}</strong><span>Usuarios con actividad</span></div>
      </div>
      <div class="summary-item">
        <div class="summary-icon success"><i class="ti ti-plus"></i></div>
        <div><strong>{{ resumen.por_accion?.creado || 0 }}</strong><span>Creaciones</span></div>
      </div>
      <div class="summary-item">
        <div class="summary-icon blue"><i class="ti ti-pencil"></i></div>
        <div><strong>{{ resumen.por_accion?.actualizado || 0 }}</strong><span>Actualizaciones</span></div>
      </div>
      <div class="summary-item">
        <div class="summary-icon danger"><i class="ti ti-trash"></i></div>
        <div><strong>{{ (resumen.por_accion?.eliminado || 0) + (resumen.por_accion?.eliminado_permanente || 0) }}</strong><span>Eliminaciones</span></div>
      </div>
    </section>

    <!-- FILTROS -->
    <section class="filtros-card">
      <div class="filtros-grid">

        <div class="filtro-group">
          <label>Usuario</label>
          <select v-model="filtros.id_usuario" @change="aplicarFiltros">
            <option value="">Todos</option>
            <option v-for="u in opciones.usuarios" :key="u.id_usuario" :value="u.id_usuario">{{ u.nombre }}</option>
          </select>
        </div>

        <div class="filtro-group">
          <label>Módulo</label>
          <select v-model="filtros.tipo_registro" @change="aplicarFiltros">
            <option value="">Todos</option>
            <option v-for="t in opciones.tipos_registro" :key="t" :value="t">{{ tipoLabel(t) }}</option>
          </select>
        </div>

        <div class="filtro-group">
          <label>Acción</label>
          <select v-model="filtros.accion" @change="aplicarFiltros">
            <option value="">Todas</option>
            <option v-for="a in opciones.acciones" :key="a" :value="a">{{ accionMeta(a).label }}</option>
          </select>
        </div>

        <div class="filtro-group">
          <label>Desde</label>
          <input type="date" v-model="filtros.desde" @change="aplicarFiltros" />
        </div>

        <div class="filtro-group">
          <label>Hasta</label>
          <input type="date" v-model="filtros.hasta" @change="aplicarFiltros" />
        </div>

        <div class="filtro-group filtro-buscar">
          <label>Buscar</label>
          <div class="input-wrap">
            <i class="ti ti-search"></i>
            <input type="text" v-model="filtros.buscar" placeholder="Usuario, módulo o ruta..." />
          </div>
        </div>

        <button type="button" class="btn-limpiar" @click="limpiarFiltros">
          <i class="ti ti-filter-x"></i> Limpiar
        </button>

      </div>
    </section>

    <!-- TABLA -->
    <section class="tabla-card">

      <div v-if="cargando" class="tabla-estado">
        <i class="ti ti-loader-2 spin"></i> Cargando historial...
      </div>

      <div v-else-if="error" class="tabla-estado tabla-estado-error">
        <i class="ti ti-alert-triangle"></i> {{ error }}
      </div>

      <div v-else-if="!registros.length" class="tabla-estado">
        <i class="ti ti-history-off"></i> No hay registros que coincidan con los filtros.
      </div>

      <template v-else>
        <table class="tabla-auditoria">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Usuario</th>
              <th>Acción</th>
              <th>Módulo</th>
              <th>Registro</th>
              <th>Ruta</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in registros" :key="r.id_registro_auditoria">
              <td class="col-fecha">{{ formatFecha(r.creado_en) }}</td>
              <td>{{ r.usuario?.nombre || 'Sistema' }}</td>
              <td>
                <span class="badge" :class="accionMeta(r.accion).badge">
                  <i :class="'ti ' + accionMeta(r.accion).icon"></i> {{ accionMeta(r.accion).label }}
                </span>
              </td>
              <td>{{ tipoLabel(r.tipo_registro) }}</td>
              <td class="col-centro">#{{ r.id_registro }}</td>
              <td class="col-ruta" :title="r.ruta">{{ r.ruta }}</td>
              <td class="col-accion">
                <button type="button" class="btn-detalle" @click="abrirDetalle(r)" title="Ver detalle del cambio">
                  <i class="ti ti-eye"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="paginacion">
          <span>Página {{ paginacion.current_page }} de {{ paginacion.last_page }} · {{ paginacion.total }} registro(s)</span>
          <div class="paginacion-botones">
            <button type="button" :disabled="paginacion.current_page <= 1" @click="irAPagina(paginacion.current_page - 1)">
              <i class="ti ti-chevron-left"></i> Anterior
            </button>
            <button type="button" :disabled="paginacion.current_page >= paginacion.last_page" @click="irAPagina(paginacion.current_page + 1)">
              Siguiente <i class="ti ti-chevron-right"></i>
            </button>
          </div>
        </div>
      </template>

    </section>

    <!-- MODAL DE DETALLE -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="registroSeleccionado" class="modal-overlay" @click.self="cerrarDetalle">
          <div class="modal-detalle">

            <div class="modal-header">
              <div class="modal-title">
                <div class="modal-icon"><i :class="'ti ' + accionMeta(registroSeleccionado.accion).icon"></i></div>
                <div>
                  <h3>{{ tipoLabel(registroSeleccionado.tipo_registro) }} #{{ registroSeleccionado.id_registro }}</h3>
                  <p>
                    <span class="badge" :class="accionMeta(registroSeleccionado.accion).badge">{{ accionMeta(registroSeleccionado.accion).label }}</span>
                    por <strong>{{ registroSeleccionado.usuario?.nombre || 'Sistema' }}</strong> · {{ formatFecha(registroSeleccionado.creado_en) }}
                  </p>
                </div>
              </div>
              <button type="button" class="modal-close" @click="cerrarDetalle"><i class="ti ti-x"></i></button>
            </div>

            <div class="modal-body">

              <div class="meta-linea">
                <span><i class="ti ti-route"></i> {{ registroSeleccionado.metodo }} {{ registroSeleccionado.ruta }}</span>
                <span><i class="ti ti-map-pin"></i> {{ registroSeleccionado.direccion_ip || '—' }}</span>
              </div>

              <div v-if="!camposComparados.length" class="sin-cambios">
                No hay campos registrados para este evento.
              </div>

              <table v-else class="tabla-diff">
                <thead v-if="registroSeleccionado.accion === 'actualizado'">
                  <tr>
                    <th>Campo</th>
                    <th>Antes</th>
                    <th>Después</th>
                  </tr>
                </thead>
                <thead v-else>
                  <tr>
                    <th>Campo</th>
                    <th>Valor</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="c in camposComparados" :key="c.campo">
                    <td class="col-campo">{{ c.campo }}</td>
                    <td v-if="registroSeleccionado.accion === 'actualizado'" class="col-anterior">{{ c.anterior }}</td>
                    <td class="col-nuevo">{{ c.nuevo }}</td>
                  </tr>
                </tbody>
              </table>

            </div>

          </div>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<style scoped>
.auditoria-page { max-width: 1400px; margin: auto; }

/* HEADER */
.page-header { display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 20px; }
.header-title { display: flex; align-items: center; gap: 13px; }
.header-icon {
  width: 42px; height: 42px; display: flex; justify-content: center; align-items: center;
  border: 1px solid rgba(0,201,167,.2); border-radius: 10px;
  background: linear-gradient(145deg, rgba(0,201,167,.18), rgba(0,201,167,.04));
  color: #00c9a7; font-size: 21px; box-shadow: 0 8px 25px rgba(0,0,0,.16);
}
.page-header h1 { margin: 0; color: #f2fbff; font-size: 1.15rem; font-weight: 800; }
.page-header p { margin: 4px 0 0; color: #8ea9bf; font-size: .78rem; }

/* RESUMEN */
.auditoria-summary {
  display: flex; flex-wrap: wrap; align-items: stretch; margin-bottom: 18px; overflow: hidden;
  border: 1px solid #1b354a; border-radius: 12px;
  background: linear-gradient(135deg, #0d2233, #0b1c2b);
  box-shadow: 0 8px 30px rgba(0,0,0,.1);
}
.summary-item { display: flex; align-items: center; gap: 12px; flex: 1 1 200px; min-width: 190px; padding: 15px 20px; border-right: 1px solid #19354a; border-bottom: 1px solid #19354a; }
.summary-icon { width: 38px; height: 38px; display: flex; justify-content: center; align-items: center; border-radius: 9px; background: rgba(0,201,167,.1); color: #00c9a7; font-size: 18px; }
.summary-icon.blue { background: rgba(77,179,240,.1); color: #55b8ef; }
.summary-icon.purple { background: rgba(164,130,255,.1); color: #a482ff; }
.summary-icon.success { background: rgba(0,201,167,.12); color: #00d2ad; }
.summary-icon.danger { background: rgba(242,139,130,.1); color: #f28b82; }
.summary-item strong { display: block; color: #e0edf5; font-size: 1rem; line-height: 1; }
.summary-item span { display: block; margin-top: 5px; color: #7793a8; font-size: .67rem; }

/* FILTROS */
.filtros-card {
  margin-bottom: 18px; padding: 16px 18px; border-radius: 12px;
  background: #0d1f30; border: 1px solid #1e3a52;
}
.filtros-grid { display: flex; flex-wrap: wrap; align-items: end; gap: 14px; }
.filtro-group { display: flex; flex-direction: column; gap: 6px; min-width: 150px; }
.filtro-buscar { flex: 1 1 220px; }
.filtro-group label { color: #9cb5c6; font-size: .65rem; font-weight: 750; }
.filtro-group select, .filtro-group input {
  min-height: 38px; padding: 8px 10px; border: 1px solid #29465c; border-radius: 7px;
  background: #081a29; color: #d4e4f0; font-size: .74rem; outline: none;
}
.filtro-group select:focus, .filtro-group input:focus { border-color: rgba(0,201,167,.65); }
.input-wrap { position: relative; display: flex; align-items: center; }
.input-wrap i { position: absolute; left: 10px; color: #5f7c91; font-size: 14px; }
.input-wrap input { width: 100%; box-sizing: border-box; padding-left: 32px; }

.btn-limpiar {
  display: inline-flex; align-items: center; gap: 6px; height: 38px; padding: 0 14px;
  border: 1px solid #31516a; border-radius: 7px; background: transparent; color: #91aabd;
  font-size: .72rem; font-weight: 700; cursor: pointer; transition: .15s;
}
.btn-limpiar:hover { background: rgba(255,255,255,.04); border-color: #45657b; }

/* TABLA */
.tabla-card { border-radius: 12px; background: #0d1f30; border: 1px solid #1e3a52; overflow: hidden; }
.tabla-estado {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  padding: 40px 20px; color: #8ea9bf; font-size: .82rem;
}
.tabla-estado-error { color: #fca5a5; }
.spin { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.tabla-auditoria { width: 100%; border-collapse: collapse; }
.tabla-auditoria th {
  text-align: left; padding: 11px 14px; background: #081a29; color: #8da6b8;
  font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em;
  border-bottom: 1px solid #1e3a52;
}
.tabla-auditoria td { padding: 11px 14px; border-bottom: 1px solid #152a3e; color: #c9dce8; font-size: .74rem; }
.tabla-auditoria tbody tr:hover { background: rgba(0,201,167,.035); }
.tabla-auditoria tbody tr:last-child td { border-bottom: none; }
.col-fecha { color: #8ea9bf; white-space: nowrap; }
.col-centro { text-align: center; color: #8ea9bf; }
.col-ruta { color: #7893a7; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: .68rem; max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.col-accion { text-align: right; }

.btn-detalle {
  width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center;
  border: 1px solid rgba(77,179,240,.16); border-radius: 7px; background: rgba(77,179,240,.1);
  color: #55b8ef; cursor: pointer; transition: .15s;
}
.btn-detalle:hover { background: rgba(77,179,240,.18); transform: translateY(-1px); }

/* BADGES */
.badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 20px; font-size: .62rem; font-weight: 800; white-space: nowrap; }
.badge-success { background: rgba(0,201,167,.12); border: 1px solid rgba(0,201,167,.18); color: #00d2ad; }
.badge-info { background: rgba(77,179,240,.1); border: 1px solid rgba(77,179,240,.18); color: #55b8ef; }
.badge-danger { background: rgba(242,139,130,.08); border: 1px solid rgba(242,139,130,.18); color: #f28b82; }
.badge-warning { background: rgba(240,180,60,.1); border: 1px solid rgba(240,180,60,.2); color: #f0b43c; }
.badge-muted { background: rgba(142,169,191,.1); border: 1px solid rgba(142,169,191,.13); color: #91a9ba; }

/* PAGINACIÓN */
.paginacion { display: flex; justify-content: space-between; align-items: center; gap: 14px; padding: 12px 16px; border-top: 1px solid #19354a; flex-wrap: wrap; }
.paginacion > span { color: #7893a7; font-size: .7rem; }
.paginacion-botones { display: flex; gap: 8px; }
.paginacion-botones button {
  display: inline-flex; align-items: center; gap: 5px; padding: 7px 12px;
  border: 1px solid #31516a; border-radius: 7px; background: transparent; color: #91aabd;
  font-size: .7rem; font-weight: 700; cursor: pointer; transition: .15s;
}
.paginacion-botones button:hover:not(:disabled) { background: rgba(255,255,255,.04); border-color: #45657b; }
.paginacion-botones button:disabled { opacity: .4; cursor: not-allowed; }

/* MODAL */
.modal-overlay { position: fixed; inset: 0; z-index: 9999; display: flex; justify-content: center; align-items: center; padding: 20px; background: rgba(1,8,14,.78); backdrop-filter: blur(5px); }
.modal-detalle {
  width: min(720px, 100%); max-height: 88vh; overflow-y: auto; border: 1px solid #29485e; border-radius: 13px;
  background: linear-gradient(145deg, #0d2233, #0b1c2b); box-shadow: 0 25px 90px rgba(0,0,0,.58);
}
.modal-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 15px; padding: 19px 20px; border-bottom: 1px solid #1b394e; }
.modal-title { display: flex; align-items: center; gap: 11px; }
.modal-icon { width: 38px; height: 38px; display: flex; justify-content: center; align-items: center; border: 1px solid rgba(0,201,167,.2); border-radius: 9px; background: rgba(0,201,167,.09); color: #00c9a7; font-size: 18px; }
.modal-header h3 { margin: 0; color: #dceaf2; font-size: .92rem; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; }
.modal-header p { margin: 6px 0 0; color: #718da2; font-size: .72rem; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.modal-close { width: 30px; height: 30px; display: flex; justify-content: center; align-items: center; border: 1px solid transparent; border-radius: 6px; background: transparent; color: #7893a7; font-size: 18px; cursor: pointer; }
.modal-close:hover { border-color: #29465c; background: rgba(255,255,255,.04); color: #e0edf5; }

.modal-body { padding: 18px 20px; }
.meta-linea { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #19354c; color: #7893a7; font-size: .68rem; }
.meta-linea i { color: #00c9a7; margin-right: 4px; }
.sin-cambios { padding: 20px; text-align: center; color: #8ea9bf; font-size: .78rem; }

.tabla-diff { width: 100%; border-collapse: collapse; }
.tabla-diff th { text-align: left; padding: 8px 10px; background: #081a29; color: #8da6b8; font-size: .6rem; font-weight: 800; text-transform: uppercase; border-bottom: 1px solid #1e3a52; }
.tabla-diff td { padding: 8px 10px; border-bottom: 1px solid #152a3e; font-size: .72rem; vertical-align: top; word-break: break-word; }
.col-campo { color: #9cb5c6; font-weight: 700; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; white-space: nowrap; }
.col-anterior { color: #f0b8b3; text-decoration: line-through; text-decoration-color: rgba(242,139,130,.4); }
.col-nuevo { color: #a8f0dd; }

@media (max-width: 800px) {
  .tabla-card { overflow-x: auto; }
  .tabla-auditoria { min-width: 780px; }
}
</style>
