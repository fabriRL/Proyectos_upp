<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from '@/lib/axios'

const router = useRouter()

const proyectos = ref([])
const cargando = ref(true)
const error = ref(null)
const busqueda = ref('')

/*
|--------------------------------------------------------------------------
| FILTRO DE CRONOGRAMA
|--------------------------------------------------------------------------
*/

const filtroCronograma = ref('todos') // 'todos' | 'Vigente' | 'Vencido' | 'Sin fecha'

const opcionesFiltro = computed(() => {
  const conteo = { todos: proyectos.value.length, Vigente: 0, Vencido: 0, 'Sin fecha': 0 }
  proyectos.value.forEach(p => {
    conteo[estadoCronograma(p).label]++
  })
  return [
    { value: 'todos', label: 'Todos', count: conteo.todos },
    { value: 'Vigente', label: 'Vigente', count: conteo.Vigente },
    { value: 'Vencido', label: 'Vencido', count: conteo.Vencido },
    { value: 'Sin fecha', label: 'Sin fecha', count: conteo['Sin fecha'] },
  ]
})

/*
|--------------------------------------------------------------------------
| FILTRAR PROYECTOS (búsqueda + estado del cronograma)
|--------------------------------------------------------------------------
*/

const proyectosFiltrados = computed(() => {
  let lista = proyectos.value

  if (filtroCronograma.value !== 'todos') {
    lista = lista.filter(p => estadoCronograma(p).label === filtroCronograma.value)
  }

  if (busqueda.value.trim()) {
    const termino = busqueda.value.toLowerCase().trim()
    lista = lista.filter(p =>
      (p.codigo || '').toLowerCase().includes(termino) ||
      (p.nombre || '').toLowerCase().includes(termino) ||
      (p.entidad_ejecutora || '').toLowerCase().includes(termino) ||
      (p.fuente_financiamiento || '').toLowerCase().includes(termino)
    )
  }

  return lista
})

/*
|--------------------------------------------------------------------------
| PAGINACIÓN (del lado del cliente, sobre la lista ya filtrada)
|--------------------------------------------------------------------------
*/

const paginaActual = ref(1)
const porPagina = ref(10)

const totalPaginas = computed(() =>
  Math.max(1, Math.ceil(proyectosFiltrados.value.length / porPagina.value))
)

const proyectosPaginados = computed(() => {
  const inicio = (paginaActual.value - 1) * porPagina.value
  return proyectosFiltrados.value.slice(inicio, inicio + porPagina.value)
})

const rangoMostrado = computed(() => {
  if (!proyectosFiltrados.value.length) return { desde: 0, hasta: 0 }
  const desde = (paginaActual.value - 1) * porPagina.value + 1
  const hasta = Math.min(paginaActual.value * porPagina.value, proyectosFiltrados.value.length)
  return { desde, hasta }
})

// Cada vez que cambia la búsqueda, el filtro, o el tamaño de página, vuelve
// a la página 1 — si no, podrías quedarte "varado" en una página que ya no
// existe con los nuevos resultados filtrados.
watch([busqueda, filtroCronograma, porPagina], () => {
  paginaActual.value = 1
})

function irAPagina(n) {
  if (n < 1 || n > totalPaginas.value) return
  paginaActual.value = n
}

// Ventana de números de página a mostrar (máx. 5 alrededor de la actual),
// para no imprimir 40 botones si hay muchas páginas.
const numerosPagina = computed(() => {
  const total = totalPaginas.value
  const actual = paginaActual.value
  const ventana = 2
  let inicio = Math.max(1, actual - ventana)
  let fin = Math.min(total, actual + ventana)

  if (fin - inicio < ventana * 2) {
    if (inicio === 1) fin = Math.min(total, inicio + ventana * 2)
    else if (fin === total) inicio = Math.max(1, fin - ventana * 2)
  }

  const numeros = []
  for (let i = inicio; i <= fin; i++) numeros.push(i)
  return numeros
})

/*
|--------------------------------------------------------------------------
| CARGAR PROYECTOS
|--------------------------------------------------------------------------
*/

async function cargarProyectos() {
  cargando.value = true
  error.value = null

  try {
    const { data } = await axios.get('/api/proyectos')

    proyectos.value = Array.isArray(data) ? data : []

  } catch (e) {
    error.value = 'No se pudieron cargar los proyectos.'
    console.error('Error cargando proyectos:', e)
  } finally {
    cargando.value = false
  }
}

/*
|--------------------------------------------------------------------------
| NUEVO PROYECTO
|--------------------------------------------------------------------------
*/

function irANuevoProyecto() {
  router.push({
    name: 'nuevo-proyecto'
  })
}

/*
|--------------------------------------------------------------------------
| VER PROYECTO
|--------------------------------------------------------------------------
*/

function verProyecto(codigo) {
  if (!codigo) {
    console.error('El proyecto no tiene código.')
    return
  }

  router.push({
    name: 'datos',
    params: {
      codigo: codigo
    }
  })
}

/*
|--------------------------------------------------------------------------
| EDITAR PROYECTO
|--------------------------------------------------------------------------
*/

function editarProyecto(codigo) {
  if (!codigo) {
    console.error('El proyecto no tiene código.')
    return
  }

  router.push({
    name: 'editar-proyecto',
    params: {
      codigo: codigo
    }
  })
}

/*
|--------------------------------------------------------------------------
| FORMATEAR MONTO
|--------------------------------------------------------------------------
*/

function formatearMonto(monto) {
  if (
    monto === null ||
    monto === undefined ||
    monto === '' ||
    Number(monto) === 0
  ) {
    return '—'
  }

  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(Number(monto))
}

/*
|--------------------------------------------------------------------------
| FORMATEAR FECHA
|--------------------------------------------------------------------------
*/

const meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic']

function formatearFecha(fecha) {
  if (!fecha) return '—'
  const soloFecha = String(fecha).split('T')[0]
  const d = new Date(soloFecha + 'T00:00:00')
  if (isNaN(d.getTime())) return '—'
  return `${d.getDate()}-${meses[d.getMonth()]}-${String(d.getFullYear()).slice(2)}`
}

/*
|--------------------------------------------------------------------------
| ESTADO DEL CRONOGRAMA (vigente / vencido, según fecha de conclusión actual)
|--------------------------------------------------------------------------
*/

function estadoCronograma(proyecto) {
  if (!proyecto.fecha_conclusion_actual) {
    return { label: 'Sin fecha', clase: 'badge-neutro' }
  }

  const hoy = new Date().toISOString().split('T')[0]
  const fin = String(proyecto.fecha_conclusion_actual).split('T')[0]

  return fin < hoy
    ? { label: 'Vencido', clase: 'badge-vencido' }
    : { label: 'Vigente', clase: 'badge-vigente' }
}

/*
|--------------------------------------------------------------------------
| CARGA INICIAL
|--------------------------------------------------------------------------
*/

onMounted(() => {
  cargarProyectos()
})
</script>

<template>
  <div class="page">

    <!-- =========================================================
         ENCABEZADO
    ========================================================== -->

    <div class="page-header">

      <div>
        <h1>Proyectos</h1>

        <p>
          Gestión de proyectos de inversión pública
        </p>
      </div>

      <button
        class="btn-primary"
        type="button"
        @click="irANuevoProyecto"
      >
        <i class="ti ti-plus"></i>

        <span>
          Nuevo proyecto
        </span>
      </button>

    </div>


    <!-- =========================================================
         BUSCADOR
    ========================================================== -->

    <div class="search-bar">

      <i class="ti ti-search"></i>

      <input
        v-model="busqueda"
        type="text"
        placeholder="Buscar por código, nombre o entidad ejecutora..."
      />

      <button
        v-if="busqueda"
        type="button"
        class="btn-clear-search"
        title="Limpiar búsqueda"
        @click="busqueda = ''"
      >
        <i class="ti ti-x"></i>
      </button>

    </div>


    <!-- =========================================================
         FILTROS DE CRONOGRAMA
    ========================================================== -->

    <div class="filtros-cronograma">
      <button
        v-for="opcion in opcionesFiltro"
        :key="opcion.value"
        type="button"
        class="filtro-chip"
        :class="{ activo: filtroCronograma === opcion.value }"
        @click="filtroCronograma = opcion.value"
      >
        {{ opcion.label }}
        <span class="filtro-chip-count">{{ opcion.count }}</span>
      </button>
    </div>


    <!-- =========================================================
         CARGANDO
    ========================================================== -->

    <div
      v-if="cargando"
      class="state-message"
    >
      <i class="ti ti-loader-2 spinner"></i>

      <span>
        Cargando proyectos…
      </span>
    </div>


    <!-- =========================================================
         ERROR
    ========================================================== -->

    <div
      v-else-if="error"
      class="state-message error"
    >
      <i class="ti ti-alert-circle"></i>

      <span>
        {{ error }}
      </span>
    </div>


    <!-- =========================================================
         SIN PROYECTOS
    ========================================================== -->

    <div
      v-else-if="proyectosFiltrados.length === 0"
      class="state-message"
    >
      <i class="ti ti-folder-off"></i>

      <span v-if="busqueda || filtroCronograma !== 'todos'">
        No se encontraron proyectos con los filtros aplicados.
      </span>

      <span v-else>
        Aún no hay proyectos registrados.
        Crea el primero.
      </span>
    </div>


    <!-- =========================================================
         TABLA
    ========================================================== -->

    <template v-else>

      <div class="table-wrapper">

        <table class="proyectos-table">

          <thead>

            <tr>

              <th>
                Código
              </th>

              <th>
                Nombre
              </th>

              <th>
                Entidad ejecutora
              </th>

              <th>
                Fuente de financiamiento
              </th>

              <th>
                Monto (decreto)
              </th>

              <th>
                Inicio
              </th>

              <th>
                Conclusión vigente
              </th>

              <th>
                Plazo (días)
              </th>

              <th>
                Cronograma
              </th>

              <th class="th-actions">
                Acciones
              </th>

            </tr>

          </thead>


          <tbody>

            <tr
              v-for="p in proyectosPaginados"
              :key="p.codigo"
              class="proyecto-row"
              @click="verProyecto(p.codigo)"
            >

              <!-- CÓDIGO -->

              <td>

                <span class="badge-codigo">
                  {{ p.codigo }}
                </span>

              </td>


              <!-- NOMBRE -->

              <td class="nombre-cell">

                <div class="nombre-proyecto">
                  {{ p.nombre }}
                </div>

                <div v-if="p.numero_sisin_web" class="sisin-web">
                  SISIN: {{ p.numero_sisin_web }}
                </div>

              </td>


              <!-- ENTIDAD -->

              <td class="muted">

                {{ p.entidad_ejecutora || '—' }}

              </td>


              <!-- FINANCIAMIENTO -->

              <td class="muted">

                {{ p.fuente_financiamiento || '—' }}

              </td>


              <!-- MONTO -->

              <td>

                {{ formatearMonto(p.monto_decreto) }}

              </td>


              <!-- INICIO -->

              <td class="muted">

                {{ formatearFecha(p.fecha_inicio_contractual) }}

              </td>


              <!-- CONCLUSIÓN VIGENTE -->

              <td class="muted">

                {{ formatearFecha(p.fecha_conclusion_actual) }}

              </td>


              <!-- PLAZO -->

              <td class="muted">

                {{
                  p.plazo_contractual_actual_dias ??
                  p.plazo_contractual_inicial_dias ??
                  '—'
                }}

              </td>


              <!-- ESTADO CRONOGRAMA -->

              <td>

                <span :class="['badge-estado', estadoCronograma(p).clase]">
                  {{ estadoCronograma(p).label }}
                </span>

              </td>


              <!-- ACCIONES -->

              <td
                class="acciones-cell"
                @click.stop
              >

                <!-- VER -->

                <button
                  type="button"
                  class="btn-icon"
                  title="Ver proyecto"
                  @click="verProyecto(p.codigo)"
                >

                  <i class="ti ti-eye"></i>

                </button>


                <!-- EDITAR -->

                <button
                  type="button"
                  class="btn-icon"
                  title="Editar proyecto"
                  @click="editarProyecto(p.codigo)"
                >

                  <i class="ti ti-pencil"></i>

                </button>

              </td>

            </tr>

          </tbody>

        </table>

      </div>


      <!-- =========================================================
           PAGINACIÓN
      ========================================================== -->

      <div class="paginacion">

        <div class="paginacion-info">
          Mostrando {{ rangoMostrado.desde }}–{{ rangoMostrado.hasta }} de {{ proyectosFiltrados.length }}

          <select v-model.number="porPagina" class="select-por-pagina">
            <option :value="10">10 por página</option>
            <option :value="25">25 por página</option>
            <option :value="50">50 por página</option>
          </select>
        </div>

        <div class="paginacion-botones">
          <button
            type="button"
            class="btn-paginacion"
            :disabled="paginaActual === 1"
            @click="irAPagina(paginaActual - 1)"
          >
            <i class="ti ti-chevron-left"></i>
          </button>

          <button
            v-if="numerosPagina[0] > 1"
            type="button"
            class="btn-paginacion"
            @click="irAPagina(1)"
          >
            1
          </button>
          <span v-if="numerosPagina[0] > 2" class="paginacion-puntos">…</span>

          <button
            v-for="n in numerosPagina"
            :key="n"
            type="button"
            class="btn-paginacion"
            :class="{ activo: n === paginaActual }"
            @click="irAPagina(n)"
          >
            {{ n }}
          </button>

          <span v-if="numerosPagina[numerosPagina.length - 1] < totalPaginas - 1" class="paginacion-puntos">…</span>
          <button
            v-if="numerosPagina[numerosPagina.length - 1] < totalPaginas"
            type="button"
            class="btn-paginacion"
            @click="irAPagina(totalPaginas)"
          >
            {{ totalPaginas }}
          </button>

          <button
            type="button"
            class="btn-paginacion"
            :disabled="paginaActual === totalPaginas"
            @click="irAPagina(paginaActual + 1)"
          >
            <i class="ti ti-chevron-right"></i>
          </button>
        </div>

      </div>

    </template>

  </div>
</template>


<style scoped>

.page {
  padding: 24px;
  color: #dcebf5;
  font-family: 'Segoe UI', Arial, sans-serif;
}


/* =========================================================
   ENCABEZADO
========================================================= */

.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;

  margin-bottom: 20px;

  flex-wrap: wrap;
  gap: 14px;
}

.page-header h1 {
  margin: 0;

  font-size: 1.5rem;
  font-weight: 800;

  color: #f2fbff;

  letter-spacing: .02em;
}

.page-header p {
  margin: 4px 0 0;

  color: #8ea9bf;

  font-size: .85rem;
}


/* =========================================================
   BOTÓN NUEVO
========================================================= */

.btn-primary {
  display: flex;
  align-items: center;
  gap: 8px;

  padding: 10px 18px;

  border: 0;
  border-radius: 8px;

  background: linear-gradient(
    135deg,
    #00d0ae,
    #00aa91
  );

  color: #052029;

  font-weight: 700;
  font-size: .85rem;

  cursor: pointer;

  box-shadow:
    0 8px 22px rgba(0, 201, 167, .18);

  transition: .2s;
}

.btn-primary:hover {
  filter: brightness(1.08);

  transform: translateY(-1px);
}


/* =========================================================
   BUSCADOR
========================================================= */

.search-bar {
  display: flex;
  align-items: center;
  gap: 10px;

  height: 44px;

  padding: 0 14px;

  border: 1px solid #1e3a52;
  border-radius: 8px;

  background: #0d1f30;

  margin-bottom: 14px;

  max-width: 520px;
}

.search-bar > i {
  color: #7190a7;

  font-size: 17px;
}

.search-bar input {
  flex: 1;

  height: 100%;

  border: 0;
  outline: 0;

  background: transparent;

  color: #dcebf5;

  font-size: .85rem;
}

.search-bar input::placeholder {
  color: #557087;
}

.btn-clear-search {
  display: grid;
  place-items: center;

  width: 26px;
  height: 26px;

  padding: 0;

  border: 0;
  border-radius: 5px;

  background: transparent;

  color: #7190a7;

  cursor: pointer;
}

.btn-clear-search:hover {
  background: #172f43;

  color: #dcebf5;
}


/* =========================================================
   FILTROS DE CRONOGRAMA
========================================================= */

.filtros-cronograma {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 20px;
}

.filtro-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;

  padding: 7px 14px;

  border: 1px solid #1e3a52;
  border-radius: 999px;

  background: #0d1f30;

  color: #8ea9bf;

  font-size: .78rem;
  font-weight: 600;

  cursor: pointer;

  transition: .15s;
}

.filtro-chip:hover {
  border-color: #2e4d68;
  color: #c8dae7;
}

.filtro-chip.activo {
  border-color: #00c9a7;
  background: rgba(0, 201, 167, .1);
  color: #00c9a7;
}

.filtro-chip-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  min-width: 18px;
  height: 18px;
  padding: 0 5px;

  border-radius: 999px;

  background: rgba(142, 169, 191, .15);

  font-size: .68rem;
  font-weight: 700;
}

.filtro-chip.activo .filtro-chip-count {
  background: rgba(0, 201, 167, .2);
}


/* =========================================================
   ESTADOS
========================================================= */

.state-message {
  display: flex;
  align-items: center;
  justify-content: center;

  gap: 8px;

  padding: 50px 20px;

  color: #8ea9bf;

  font-size: .88rem;

  text-align: center;
}

.state-message.error {
  color: #fca5a5;
}


/* =========================================================
   TABLA
========================================================= */

.table-wrapper {
  border: 1px solid #1e3a52;

  border-radius: 12px;

  overflow-x: auto;

  background: #0d1f30;
}

.proyectos-table {
  width: 100%;

  border-collapse: collapse;

  font-size: .82rem;

  min-width: 1100px;
}

.proyectos-table thead tr {
  background: #0a1826;

  border-bottom: 1px solid #1e3a52;
}

.proyectos-table th {
  padding: 12px 16px;

  text-align: left;

  color: #8ea9bf;

  font-weight: 700;

  font-size: .72rem;

  text-transform: uppercase;

  letter-spacing: .04em;

  white-space: nowrap;
}

.proyectos-table .th-actions {
  text-align: right;
}


/* =========================================================
   FILAS
========================================================= */

.proyecto-row {
  border-bottom: 1px solid #152a3e;

  cursor: pointer;

  transition:
    background .15s,
    transform .15s;
}

.proyecto-row:hover {
  background: rgba(0, 201, 167, .06);
}

.proyecto-row:last-child {
  border-bottom: 0;
}

.proyectos-table td {
  padding: 12px 16px;

  color: #dcebf5;

  white-space: nowrap;
}

.nombre-cell {
  max-width: 300px;

  font-weight: 600;

  white-space: normal;
}

.nombre-proyecto {
  line-height: 1.35;
}

.sisin-web {
  margin-top: 3px;

  color: #647a8e;

  font-size: .68rem;

  font-weight: 500;
}

.muted {
  color: #8ea9bf;
}


/* =========================================================
   CÓDIGO
========================================================= */

.badge-codigo {
  display: inline-block;

  padding: 3px 9px;

  border-radius: 6px;

  background: rgba(0, 201, 167, .12);

  border: 1px solid rgba(0, 201, 167, .25);

  color: #00c9a7;

  font-weight: 700;

  font-size: .74rem;
}


/* =========================================================
   ESTADO CRONOGRAMA
========================================================= */

.badge-estado {
  display: inline-block;

  padding: 3px 9px;

  border-radius: 6px;

  font-weight: 700;

  font-size: .7rem;

  text-transform: uppercase;

  letter-spacing: .03em;
}

.badge-vigente {
  background: rgba(0, 201, 167, .12);

  border: 1px solid rgba(0, 201, 167, .25);

  color: #00c9a7;
}

.badge-vencido {
  background: rgba(248, 113, 113, .12);

  border: 1px solid rgba(248, 113, 113, .3);

  color: #fca5a5;
}

.badge-neutro {
  background: rgba(142, 169, 191, .1);

  border: 1px solid rgba(142, 169, 191, .25);

  color: #8ea9bf;
}


/* =========================================================
   ACCIONES
========================================================= */

.acciones-cell {
  display: flex;

  justify-content: flex-end;

  align-items: center;

  gap: 6px;
}

.btn-icon {
  display: grid;

  place-items: center;

  width: 32px;
  height: 32px;

  border: 1px solid #1e3a52;

  border-radius: 6px;

  background: transparent;

  color: #8ea9bf;

  cursor: pointer;

  transition: .15s;
}

.btn-icon:hover {
  border-color: #00c9a7;

  color: #00c9a7;

  background: rgba(0, 201, 167, .06);
}

.btn-icon:disabled {
  opacity: .5;

  cursor: not-allowed;
}


/* =========================================================
   PAGINACIÓN
========================================================= */

.paginacion {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;

  margin-top: 16px;
}

.paginacion-info {
  display: flex;
  align-items: center;
  gap: 10px;

  color: #8ea9bf;
  font-size: .78rem;
}

.select-por-pagina {
  padding: 5px 8px;
  border: 1px solid #1e3a52;
  border-radius: 6px;
  background: #0d1f30;
  color: #c8dae7;
  font-size: .74rem;
  cursor: pointer;
}

.paginacion-botones {
  display: flex;
  align-items: center;
  gap: 4px;
}

.btn-paginacion {
  display: grid;
  place-items: center;

  min-width: 32px;
  height: 32px;
  padding: 0 8px;

  border: 1px solid #1e3a52;
  border-radius: 6px;

  background: #0d1f30;

  color: #8ea9bf;

  font-size: .78rem;
  font-weight: 600;

  cursor: pointer;

  transition: .15s;
}

.btn-paginacion:hover:not(:disabled) {
  border-color: #00c9a7;
  color: #00c9a7;
}

.btn-paginacion.activo {
  border-color: #00c9a7;
  background: rgba(0, 201, 167, .12);
  color: #00c9a7;
}

.btn-paginacion:disabled {
  opacity: .4;
  cursor: not-allowed;
}

.paginacion-puntos {
  color: #557087;
  font-size: .78rem;
  padding: 0 2px;
}


/* =========================================================
   SPINNER
========================================================= */

.spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 760px) {

  .page {
    padding: 16px;
  }

  .paginacion {
    flex-direction: column;
    align-items: stretch;
  }

  .paginacion-botones {
    justify-content: center;
  }

}

</style>