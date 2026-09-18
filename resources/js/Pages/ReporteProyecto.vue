<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/lib/axios'
import { useToast } from '@/composables/useToast.js'

const route = useRoute()
const { showToast } = useToast()

const codigo = computed(() => route.params.codigo)

const cargandoSecciones = ref(true)
const secciones = ref([])
const seleccionadas = ref([])

const generando = ref(false)
const generandoPdf = ref(false)
const generandoExcel = ref(false)
const error = ref(null)

const reporte = ref(null)

async function cargarSecciones() {
  cargandoSecciones.value = true
  try {
    const { data } = await axios.get('/api/reporte-proyecto/secciones')
    secciones.value = data
    seleccionadas.value = data.map(s => s.clave)
  } catch (e) {
    console.error(e)
    showToast('No se pudieron cargar los módulos disponibles.', 'error')
  } finally {
    cargandoSecciones.value = false
  }
}

function toggleSeccion(clave) {
  seleccionadas.value = seleccionadas.value.includes(clave)
    ? seleccionadas.value.filter(c => c !== clave)
    : [...seleccionadas.value, clave]
}

function seleccionarTodos() {
  seleccionadas.value = secciones.value.map(s => s.clave)
}
function quitarTodos() {
  seleccionadas.value = []
}

async function generarVistaPrevia() {
  if (!seleccionadas.value.length) {
    showToast('Selecciona al menos un módulo para el reporte.', 'warning')
    return
  }

  generando.value = true
  error.value = null

  try {
    const { data } = await axios.get(`/api/proyectos/${codigo.value}/reporte`, {
      params: { secciones: seleccionadas.value.join(',') },
    })
    reporte.value = data
  } catch (e) {
    console.error(e)
    error.value = 'No se pudo generar el reporte del proyecto.'
  } finally {
    generando.value = false
  }
}

async function descargar(formato) {
  if (!seleccionadas.value.length) {
    showToast('Selecciona al menos un módulo para el reporte.', 'warning')
    return
  }

  const estadoRef = formato === 'pdf' ? generandoPdf : generandoExcel
  estadoRef.value = true

  try {
    const { data } = await axios.get(`/api/proyectos/${codigo.value}/reporte/${formato}`, {
      params: { secciones: seleccionadas.value.join(',') },
      responseType: 'blob',
    })

    const tipoMime = formato === 'pdf'
      ? 'application/pdf'
      : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'

    const url = window.URL.createObjectURL(new Blob([data], { type: tipoMime }))
    const link = document.createElement('a')
    link.href = url
    link.download = `reporte-${codigo.value}-${new Date().toISOString().slice(0, 10)}.${formato === 'pdf' ? 'pdf' : 'xlsx'}`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    console.error(e)
    showToast(`No se pudo generar el ${formato === 'pdf' ? 'PDF' : 'Excel'}.`, 'error')
  } finally {
    estadoRef.value = false
  }
}

function esClaveValor(seccion) {
  return JSON.stringify(seccion.encabezados) === JSON.stringify(['Campo', 'Valor'])
    || JSON.stringify(seccion.encabezados) === JSON.stringify(['Indicador', 'Valor'])
}

function celda(valor, tipo) {
  if (valor === null || valor === undefined || valor === '') return '—'
  if (tipo === 'monto') return Number(valor).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
  if (tipo === 'porcentaje') return Number(valor).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%'
  return String(valor)
}

onMounted(cargarSecciones)
</script>

<template>
  <div class="reporte-proyecto-page">

    <div class="page-header">
      <div class="header-title">
        <div class="header-icon"><i class="ti ti-report-analytics"></i></div>
        <div>
          <h2>Reporte del proyecto</h2>
          <p>Elige qué módulos incluir y genera el reporte en pantalla, PDF o Excel.</p>
        </div>
      </div>
    </div>

    <!-- SELECTOR DE MÓDULOS -->
    <section class="selector-card">

      <div class="selector-header">
        <span><i class="ti ti-list-check"></i> Módulos a incluir</span>
        <div class="selector-acciones">
          <button type="button" @click="seleccionarTodos">Seleccionar todos</button>
          <button type="button" @click="quitarTodos">Quitar todos</button>
        </div>
      </div>

      <div v-if="cargandoSecciones" class="selector-estado">
        <i class="ti ti-loader-2 spin"></i> Cargando módulos disponibles...
      </div>

      <div v-else class="checkbox-grid">
        <label v-for="s in secciones" :key="s.clave" class="checkbox-item" :class="{ marcado: seleccionadas.includes(s.clave) }">
          <input type="checkbox" :checked="seleccionadas.includes(s.clave)" @change="toggleSeccion(s.clave)" />
          <span>{{ s.label }}</span>
        </label>
      </div>

      <div class="selector-botones">
        <button type="button" class="btn-generar" :disabled="generando" @click="generarVistaPrevia">
          <i v-if="generando" class="ti ti-loader-2 spin"></i>
          <i v-else class="ti ti-eye"></i>
          {{ generando ? 'Generando...' : 'Ver reporte en pantalla' }}
        </button>
        <button type="button" class="btn-exportar" :disabled="generandoPdf" @click="descargar('pdf')">
          <i v-if="generandoPdf" class="ti ti-loader-2 spin"></i>
          <i v-else class="ti ti-file-type-pdf"></i>
          PDF
        </button>
        <button type="button" class="btn-exportar" :disabled="generandoExcel" @click="descargar('excel')">
          <i v-if="generandoExcel" class="ti ti-loader-2 spin"></i>
          <i v-else class="ti ti-file-spreadsheet"></i>
          Excel
        </button>
      </div>

    </section>

    <!-- VISTA PREVIA -->
    <div v-if="error" class="preview-estado preview-error">
      <i class="ti ti-alert-triangle"></i> {{ error }}
    </div>

    <template v-else-if="reporte">

      <div class="preview-meta">
        <strong>{{ reporte.proyecto.codigo }}</strong> — {{ reporte.proyecto.nombre }}
        <span>Generado el {{ reporte.generado_en }}</span>
      </div>

      <section v-for="seccion in reporte.secciones" :key="seccion.clave" class="seccion-card">

        <div class="seccion-header">{{ seccion.titulo }}</div>

        <div v-if="!seccion.filas.length" class="seccion-vacia">
          Sin registros para este módulo.
        </div>

        <div v-else-if="esClaveValor(seccion)" class="tabla-kv">
          <div v-for="(fila, i) in seccion.filas" :key="i" class="kv-fila">
            <div class="kv-campo">{{ fila[0] }}</div>
            <div class="kv-valor">{{ fila[1] }}</div>
          </div>
        </div>

        <div v-else class="tabla-scroll">
          <table class="tabla-seccion">
            <thead>
              <tr>
                <th v-for="(enc, i) in seccion.encabezados" :key="i" :class="seccion.tipos[i] === 'texto' ? 'col-izq' : ''">{{ enc }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(fila, i) in seccion.filas" :key="i">
                <td v-for="(valor, j) in fila" :key="j" :class="seccion.tipos[j] === 'texto' ? 'col-izq' : ''">
                  {{ celda(valor, seccion.tipos[j]) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </section>

    </template>

    <div v-else class="preview-estado">
      <i class="ti ti-report-search"></i>
      <span>Selecciona los módulos y presiona "Ver reporte en pantalla" para generar la vista previa.</span>
    </div>

  </div>
</template>

<style scoped>
.reporte-proyecto-page { width: 100%; }

.page-header { display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 18px; }
.header-title { display: flex; align-items: center; gap: 13px; }
.header-icon {
  width: 42px; height: 42px; display: flex; justify-content: center; align-items: center;
  border: 1px solid rgba(0,201,167,.2); border-radius: 10px;
  background: linear-gradient(145deg, rgba(0,201,167,.18), rgba(0,201,167,.04));
  color: #00c9a7; font-size: 21px; box-shadow: 0 8px 25px rgba(0,0,0,.16);
}
.page-header h2 { margin: 0; color: #e4f0f7; font-size: 1.08rem; font-weight: 750; }
.page-header p { margin: 5px 0 0; color: #7894aa; font-size: .74rem; }

/* SELECTOR */
.selector-card { margin-bottom: 18px; padding: 16px 18px; border-radius: 12px; background: #0d1f30; border: 1px solid #1e3a52; }
.selector-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 12px; color: #d4e4f0; font-size: .8rem; font-weight: 750; }
.selector-header i { color: #00c9a7; margin-right: 5px; }
.selector-acciones { display: flex; gap: 8px; }
.selector-acciones button {
  padding: 5px 11px; border: 1px solid #31516a; border-radius: 6px; background: transparent;
  color: #91aabd; font-size: .68rem; font-weight: 700; cursor: pointer; transition: .15s;
}
.selector-acciones button:hover { background: rgba(255,255,255,.04); border-color: #45657b; }

.selector-estado { display: flex; align-items: center; gap: 8px; padding: 16px; color: #8ea9bf; font-size: .78rem; }
.spin { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.checkbox-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 8px; margin-bottom: 16px; }
.checkbox-item {
  display: flex; align-items: center; gap: 8px; padding: 9px 12px;
  border: 1px solid #29465c; border-radius: 8px; background: #081a29; cursor: pointer; transition: .15s;
}
.checkbox-item:hover { border-color: #3a5a72; }
.checkbox-item.marcado { border-color: rgba(0,201,167,.4); background: rgba(0,201,167,.06); }
.checkbox-item input { accent-color: #00c9a7; cursor: pointer; }
.checkbox-item span { color: #d4e4f0; font-size: .74rem; font-weight: 600; }

.selector-botones { display: flex; flex-wrap: wrap; gap: 10px; padding-top: 14px; border-top: 1px solid #19354c; }
.btn-generar, .btn-exportar {
  display: inline-flex; align-items: center; gap: 7px; min-height: 38px; padding: 0 16px;
  border-radius: 8px; font-size: .74rem; font-weight: 800; cursor: pointer; transition: .15s;
}
.btn-generar { border: 1px solid rgba(0,201,167,.4); background: linear-gradient(135deg, #00d3ae, #00b99b); color: #04151b; }
.btn-generar:hover:not(:disabled) { filter: brightness(1.06); transform: translateY(-1px); }
.btn-exportar { border: 1px solid #31516a; background: transparent; color: #91aabd; }
.btn-exportar:hover:not(:disabled) { background: rgba(255,255,255,.04); border-color: #45657b; color: #d4e4f0; }
.btn-generar:disabled, .btn-exportar:disabled { opacity: .55; cursor: not-allowed; }

/* PREVIEW */
.preview-estado {
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px;
  padding: 50px 20px; border-radius: 12px; background: #0d1f30; border: 1px dashed #29465c;
  color: #7894aa; font-size: .82rem; text-align: center;
}
.preview-estado i { font-size: 30px; color: #4a6478; }
.preview-error { flex-direction: row; border: 1px solid rgba(242,139,130,.2); color: #f0b8b3; background: rgba(242,139,130,.06); }

.preview-meta {
  display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
  margin-bottom: 14px; padding: 10px 16px; border-radius: 9px;
  background: rgba(0,201,167,.06); border: 1px solid rgba(0,201,167,.18);
  color: #cfe6df; font-size: .78rem;
}
.preview-meta strong { color: #00d2ad; }
.preview-meta span { margin-left: auto; color: #7ba39a; font-size: .68rem; }

.seccion-card { margin-bottom: 16px; border-radius: 12px; overflow: hidden; background: #0d1f30; border: 1px solid #1e3a52; }
.seccion-header {
  padding: 11px 16px; background: linear-gradient(135deg, #0e2436, #0d1f30);
  border-bottom: 1px solid #1e3a52; color: #d8e8f1; font-size: .82rem; font-weight: 750; text-transform: uppercase; letter-spacing: .03em;
}
.seccion-vacia { padding: 16px; color: #7894aa; font-size: .76rem; font-style: italic; }

/* KV */
.tabla-kv { padding: 4px 16px; }
.kv-fila { display: grid; grid-template-columns: 260px 1fr; gap: 12px; padding: 9px 0; border-bottom: 1px solid #152a3e; }
.kv-fila:last-child { border-bottom: none; }
.kv-campo { color: #9cb5c6; font-size: .72rem; font-weight: 700; }
.kv-valor { color: #d4e4f0; font-size: .76rem; word-break: break-word; }

/* TABLA */
.tabla-scroll { overflow-x: auto; }
.tabla-seccion { width: 100%; border-collapse: collapse; }
.tabla-seccion th {
  padding: 9px 10px; background: #081a29; color: #8da6b8; font-size: .62rem; font-weight: 800;
  text-transform: uppercase; letter-spacing: .04em; border-bottom: 1px solid #1e3a52; white-space: nowrap; text-align: center;
}
.tabla-seccion td { padding: 8px 10px; border-bottom: 1px solid #152a3e; color: #c9dce8; font-size: .72rem; text-align: center; white-space: nowrap; }
.tabla-seccion tbody tr:hover { background: rgba(0,201,167,.03); }
.tabla-seccion tbody tr:last-child td { border-bottom: none; }
.col-izq { text-align: left !important; white-space: normal !important; }

@media (max-width: 700px) {
  .kv-fila { grid-template-columns: 1fr; gap: 3px; }
  .preview-meta span { margin-left: 0; }
}
</style>
