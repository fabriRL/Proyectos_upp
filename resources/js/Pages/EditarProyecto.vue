<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from '@/lib/axios'
import MapaUbicacion from '@/Components/MapaUbicacion.vue'

const router = useRouter()
const route = useRoute()

const form = ref({
  codigo: '',
  numero_sisin_web: '',
  nombre: '',
  fiscal_general: '',
  entidad_ejecutora: '',
  fuente_financiamiento: '',
  norma_financiador: '',
  monto_decreto: '',

  familias_productoras: '',
  total_beneficiarios: '',
  empleos_directos_construccion: '',
  empleos_indirectos_construccion: '',
  empleos_directos_operacion: '',
  empleos_indirectos_operacion: '',

  fecha_inicio_contractual: '',
  fecha_conclusion_inicial_contractual: '',
  plazo_contractual_inicial_dias: '',
  fecha_conclusion_actual: '',
  plazo_contractual_actual_dias: '',
})

const cargando = ref(true)
const guardando = ref(false)
const error = ref('')
const erroresCampo = ref({})

// --- Componentes / Líneas y Capacidades ---
const componentes = ref([])

function agregarComponente() {
  componentes.value.push({ nombre: '', descripcion: '', productos: [] })
}

function eliminarComponente(index) {
  componentes.value.splice(index, 1)
}

function agregarProducto(indexComponente) {
  componentes.value[indexComponente].productos.push({ nombre: '', cantidad: null, unidad: '' })
}

function eliminarProducto(indexComponente, indexProducto) {
  componentes.value[indexComponente].productos.splice(indexProducto, 1)
}

// --- Ubicaciones (una o más) ---
const ubicaciones = ref([])

function agregarUbicacion() {
  ubicaciones.value.push({
    departamento: '', provincia: '', municipio: '', comunidad_localidad: '',
    coordenada_norte: '', coordenada_este: '', zona_utm: '',
  })
}

function eliminarUbicacion(index) {
  ubicaciones.value.splice(index, 1)
}

// --- Wizard de pasos ---
const steps = [
  { n: 1, label: 'Datos del proyecto' },
  { n: 2, label: 'Beneficiarios y ubicación' },
  { n: 3, label: 'Fechas y plazos' },
]
const currentStep = ref(1)
const errorPaso = ref('')

function irAPaso(n) {
  errorPaso.value = ''
  currentStep.value = n
}

function pasoAnterior() {
  errorPaso.value = ''
  currentStep.value = Math.max(currentStep.value - 1, 1)
}

function validarPasoActual() {
  errorPaso.value = ''
  if (currentStep.value === 1) {
    if (!form.value.codigo?.trim() || !form.value.nombre?.trim()) {
      errorPaso.value = 'Completa al menos el código y el nombre del proyecto antes de continuar.'
      return false
    }
  }
  return true
}

function onSubmit() {
  if (currentStep.value < steps.length) {
    if (!validarPasoActual()) return
    currentStep.value += 1
    return
  }
  guardar()
}

// --- Normaliza fechas que puedan llegar con hora/timezone (ej. "2025-11-21T00:00:00.000000Z") ---
function soloFecha(valor) {
  if (!valor) return ''
  return String(valor).split('T')[0]
}

// --- Busca un valor de beneficiarios por categoría + tipo, tal como los guarda el backend ---
function buscarBeneficiario(lista, categoria, tipo) {
  const fila = (lista || []).find(b => b.categoria === categoria && b.tipo === tipo)
  return fila?.cantidad ?? ''
}

async function cargarProyecto() {
  cargando.value = true
  error.value = ''

  try {
    const { data } = await axios.get(`/api/proyectos/${route.params.codigo}`)

    form.value = {
      codigo: data.codigo ?? '',
      numero_sisin_web: data.numero_sisin_web ?? '',
      nombre: data.nombre ?? '',
      fiscal_general: data.fiscal_general ?? '',
      entidad_ejecutora: data.entidad_ejecutora ?? '',
      fuente_financiamiento: data.fuente_financiamiento ?? '',
      norma_financiador: data.norma_financiador ?? '',
      monto_decreto: data.monto_decreto ?? '',

      familias_productoras: buscarBeneficiario(data.beneficiarios, 'Beneficiarios', 'Familias productoras'),
      total_beneficiarios: buscarBeneficiario(data.beneficiarios, 'Beneficiarios', 'Total beneficiarios'),
      empleos_directos_construccion: buscarBeneficiario(data.beneficiarios, 'Empleo - Construcción', 'Directos'),
      empleos_indirectos_construccion: buscarBeneficiario(data.beneficiarios, 'Empleo - Construcción', 'Indirectos'),
      empleos_directos_operacion: buscarBeneficiario(data.beneficiarios, 'Empleo - Operación', 'Directos'),
      empleos_indirectos_operacion: buscarBeneficiario(data.beneficiarios, 'Empleo - Operación', 'Indirectos'),

      fecha_inicio_contractual: soloFecha(data.fecha_inicio_contractual),
      fecha_conclusion_inicial_contractual: soloFecha(data.fecha_conclusion_inicial_contractual),
      plazo_contractual_inicial_dias: data.plazo_contractual_inicial_dias ?? '',
      fecha_conclusion_actual: soloFecha(data.fecha_conclusion_actual),
      plazo_contractual_actual_dias: data.plazo_contractual_actual_dias ?? '',
    }

    ubicaciones.value = (data.ubicaciones ?? []).map(u => ({
      departamento: u.departamento ?? '',
      provincia: u.provincia ?? '',
      municipio: u.municipio ?? '',
      comunidad_localidad: u.comunidad_localidad ?? '',
      coordenada_norte: u.coordenada_norte ?? '',
      coordenada_este: u.coordenada_este ?? '',
      zona_utm: u.zona_utm ?? '',
    }))

    componentes.value = (data.componentes ?? []).map(c => ({
      nombre: c.nombre ?? '',
      descripcion: c.descripcion ?? '',
      productos: (c.productos ?? []).map(p => ({
        nombre: p.nombre ?? '',
        cantidad: p.cantidad ?? null,
        unidad: p.unidad ?? '',
      })),
    }))
  } catch (e) {
    console.error('Error al cargar el proyecto:', e)
    if (e.response?.status === 404) {
      error.value = 'El proyecto no existe o fue eliminado.'
    } else {
      error.value = 'No se pudo cargar la información del proyecto.'
    }
  } finally {
    cargando.value = false
  }
}

// --- Cálculo automático de plazos y estado del cronograma ---

function diffDias(desde, hasta) {
  if (!desde || !hasta) return null
  const d1 = new Date(desde + 'T00:00:00')
  const d2 = new Date(hasta + 'T00:00:00')
  if (isNaN(d1.getTime()) || isNaN(d2.getTime())) return null
  return Math.round((d2 - d1) / 86400000)
}

function fmtNum(n) {
  return n === null || n === undefined ? '—' : n.toLocaleString('es-BO')
}

const hoyStr = new Date().toISOString().split('T')[0]

const plazoInicialCalculado = computed(() =>
  diffDias(form.value.fecha_inicio_contractual, form.value.fecha_conclusion_inicial_contractual)
)

const plazoActualCalculado = computed(() =>
  diffDias(form.value.fecha_inicio_contractual, form.value.fecha_conclusion_actual)
)

const diasTranscurridos = computed(() => {
  if (!form.value.fecha_inicio_contractual) return null
  const val = diffDias(form.value.fecha_inicio_contractual, hoyStr)
  return val !== null ? Math.max(val, 0) : null
})

const diasRestantes = computed(() => {
  if (!form.value.fecha_conclusion_actual) return null
  return diffDias(hoyStr, form.value.fecha_conclusion_actual)
})

const extensionPlazo = computed(() => {
  if (plazoInicialCalculado.value === null || plazoActualCalculado.value === null) return null
  return plazoActualCalculado.value - plazoInicialCalculado.value
})

const cronogramaVencido = computed(() => diasRestantes.value !== null && diasRestantes.value < 0)

watch(plazoInicialCalculado, (val) => {
  form.value.plazo_contractual_inicial_dias = val ?? ''
})
watch(plazoActualCalculado, (val) => {
  form.value.plazo_contractual_actual_dias = val ?? ''
})

function volver() {
  router.push({ name: 'datos', params: { codigo: route.params.codigo } })
}

function limpiarVacios(obj) {
  const limpio = {}
  for (const key in obj) {
    limpio[key] = obj[key] === '' ? null : obj[key]
  }
  return limpio
}

async function guardar() {
  error.value = ''
  erroresCampo.value = {}
  guardando.value = true

  try {
    const payload = limpiarVacios(form.value)
    payload.ubicaciones = ubicaciones.value
    payload.componentes = componentes.value

    const { data } = await axios.put(`/api/proyectos/${route.params.codigo}`, payload)

    router.push({ name: 'datos', params: { codigo: data.codigo } })

  } catch (e) {
    console.error('Error al actualizar proyecto:', e)

    if (e.response?.status === 422) {
      erroresCampo.value = e.response.data.errors || {}
      error.value = 'Revisa los campos marcados en rojo.'
    } else if (e.response?.status === 401) {
      error.value = 'Tu sesión expiró. Vuelve a iniciar sesión.'
    } else {
      error.value = 'No se pudo guardar los cambios. Intenta nuevamente.'
    }
  } finally {
    guardando.value = false
  }
}

onMounted(cargarProyecto)
</script>

<template>
  <div class="page">

    <div v-if="cargando" class="state-loading">
      <i class="ti ti-loader-2 spinner"></i>
      <span>Cargando información del proyecto…</span>
    </div>

    <template v-else>

      <p class="page-intro">Edite la información base del proyecto de inversión pública.</p>

      <!-- LÍNEA DE PASOS -->
      <div class="stepper">
        <template v-for="(s, idx) in steps" :key="s.n">
          <div
            class="stepper-item"
            :class="{ active: currentStep === s.n, done: currentStep > s.n }"
            @click="irAPaso(s.n)"
          >
            <div class="stepper-circle">
              <i v-if="currentStep > s.n" class="ti ti-check"></i>
              <span v-else>{{ s.n }}</span>
            </div>
            <div class="stepper-label">{{ s.label }}</div>
          </div>
          <div v-if="idx < steps.length - 1" class="stepper-connector" :class="{ done: currentStep > s.n }"></div>
        </template>
      </div>

      <p v-if="error" class="form-error">
        <i class="ti ti-alert-circle"></i>
        {{ error }}
      </p>

      <p v-if="errorPaso" class="form-error">
        <i class="ti ti-alert-circle"></i>
        {{ errorPaso }}
      </p>

      <form @submit.prevent="onSubmit">

        <!-- ========================================================
             PASO 1 — Datos del proyecto + Componentes/Productos
        ========================================================= -->
        <div v-show="currentStep === 1" class="row-2col">

          <!-- IDENTIFICACIÓN -->
          <div class="card">
            <div class="card-header">
              <h2>Identificación del proyecto</h2>
              <p>Datos administrativos y de financiamiento.</p>
            </div>

            <div class="card-body grid-3">
              <div class="field">
                <label>Código del proyecto</label>
                <input v-model="form.codigo" type="text" placeholder="PRY-002" :disabled="guardando" />
                <span v-if="erroresCampo.codigo" class="field-error">{{ erroresCampo.codigo[0] }}</span>
              </div>

              <div class="field">
                <label>Número SISIN Web</label>
                <input v-model="form.numero_sisin_web" type="text" placeholder="Ej. 0041-04174-00000" :disabled="guardando" />
              </div>

              <div class="field">
                <label>Monto del decreto (Bs)</label>
                <input v-model="form.monto_decreto" type="number" step="0.01" placeholder="0.00" :disabled="guardando" />
              </div>

              <div class="field span-full">
                <label>Nombre del proyecto</label>
                <input v-model="form.nombre" type="text" placeholder="Ingrese el nombre completo del proyecto" :disabled="guardando" />
                <span v-if="erroresCampo.nombre" class="field-error">{{ erroresCampo.nombre[0] }}</span>
              </div>

              <div class="field">
                <label>Fiscal general</label>
                <input v-model="form.fiscal_general" type="text" placeholder="Nombre completo" :disabled="guardando" />
              </div>

              <div class="field">
                <label>Entidad ejecutora</label>
                <input v-model="form.entidad_ejecutora" type="text" :disabled="guardando" />
              </div>

              <div class="field">
                <label>Fuente de financiamiento</label>
                <input v-model="form.fuente_financiamiento" type="text" placeholder="Ej. 92 - FINPRO" :disabled="guardando" />
              </div>

              <div class="field span-full">
                <label>Norma financiador</label>
                <input v-model="form.norma_financiador" type="text" placeholder="Ej. D.S. N° 4826 del 16 de noviembre de 2022" :disabled="guardando" />
              </div>
            </div>
          </div>

          <!-- COMPONENTES O LÍNEAS Y CAPACIDADES -->
          <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
              <div>
                <h2>Componentes o Líneas y Capacidades</h2>
                <p>Opcional. Ej. "MATADERO", "CONFINAMIENTO", con sus productos.</p>
              </div>
              <button
                type="button"
                @click="agregarComponente"
                :disabled="guardando"
                style="flex-shrink:0;display:inline-flex;align-items:center;gap:6px;padding:7px 12px;border:none;border-radius:7px;background:#00c9a7;color:#04211c;font-weight:700;font-size:.72rem;cursor:pointer;"
              >
                <i class="ti ti-plus"></i> Componente
              </button>
            </div>

            <div class="card-body">
              <div v-if="!componentes.length" style="text-align:center;padding:20px 0;color:#647a8e;font-size:.78rem;">
                Sin componentes agregados todavía.
              </div>

              <div
                v-for="(comp, iComp) in componentes"
                :key="iComp"
                class="subcard"
                style="position:relative;"
              >
                <button
                  type="button"
                  @click="eliminarComponente(iComp)"
                  :disabled="guardando"
                  style="position:absolute;top:12px;right:12px;background:none;border:none;color:#f87171;cursor:pointer;font-size:.85rem;"
                  title="Quitar componente"
                >✕</button>

                <div class="field" style="margin-bottom:12px;padding-right:24px;">
                  <label>Nombre del componente</label>
                  <input v-model="comp.nombre" type="text" placeholder="Ej. MATADERO" :disabled="guardando" />
                </div>

                <div class="field" style="margin-bottom:14px;">
                  <label>Capacidades (una por línea)</label>
                  <textarea
                    v-model="comp.descripcion"
                    :disabled="guardando"
                    rows="4"
                    style="width:100%;box-sizing:border-box;background:#091520;border:1px solid #1e3a52;border-radius:6px;padding:8px;color:#c8dae7;font-size:.8rem;font-family:inherit;line-height:1.5;resize:vertical;"
                    placeholder="250 cabezas día, para la producción de 50 Ton/día...
3.239 Ton/año cortes especiales"
                  ></textarea>
                </div>

                <label style="display:block;font-size:.68rem;color:#8ea9bf;margin-bottom:6px;">Productos</label>

                <div
                  v-for="(prod, iProd) in comp.productos"
                  :key="iProd"
                  style="display:flex;gap:6px;margin-bottom:6px;align-items:center;"
                >
                  <input v-model="prod.nombre" type="text" placeholder="Nombre del producto" :disabled="guardando" style="flex:2;min-width:0;background:#091520;border:1px solid #1e3a52;border-radius:6px;padding:6px 8px;color:#c8dae7;font-size:.76rem;" />
                  <input v-model.number="prod.cantidad" type="number" step="0.01" placeholder="Cant." :disabled="guardando" style="flex:1;min-width:0;background:#091520;border:1px solid #1e3a52;border-radius:6px;padding:6px 8px;color:#c8dae7;font-size:.76rem;" />
                  <input v-model="prod.unidad" type="text" placeholder="Unidad" :disabled="guardando" style="flex:1;min-width:0;background:#091520;border:1px solid #1e3a52;border-radius:6px;padding:6px 8px;color:#c8dae7;font-size:.76rem;" />
                  <button type="button" @click="eliminarProducto(iComp, iProd)" :disabled="guardando" style="flex-shrink:0;background:none;border:none;color:#f87171;cursor:pointer;font-size:.75rem;">✕</button>
                </div>

                <button
                  type="button"
                  @click="agregarProducto(iComp)"
                  :disabled="guardando"
                  style="margin-top:6px;padding:5px 10px;border:1px dashed #1e3a52;border-radius:6px;background:transparent;color:#8ea9bf;font-size:.72rem;cursor:pointer;"
                >
                  + Agregar producto
                </button>
              </div>
            </div>
          </div>

        </div>

        <!-- ========================================================
             PASO 2 — Beneficiarios + Ubicaciones
        ========================================================= -->
        <div v-show="currentStep === 2" class="row-2col">

          <!-- BENEFICIARIOS -->
          <div class="card">
            <div class="card-header">
              <h2>Beneficiarios</h2>
              <p>Población y empleos proyectados por el proyecto.</p>
            </div>

            <div class="card-body">

              <div class="subcard">
                <div class="subcard-header">
                  <i class="ti ti-users"></i>
                  <span>Población beneficiaria</span>
                </div>
                <div class="subcard-body grid-2">
                  <div class="field">
                    <label>Familias productoras</label>
                    <input v-model="form.familias_productoras" type="number" min="0" placeholder="0" :disabled="guardando" />
                  </div>
                  <div class="field">
                    <label>Total beneficiarios</label>
                    <input v-model="form.total_beneficiarios" type="number" min="0" placeholder="0" :disabled="guardando" />
                  </div>
                </div>
              </div>

              <div class="dates-grid">
                <div class="subcard">
                  <div class="subcard-header">
                    <i class="ti ti-building-factory-2"></i>
                    <span>Empleos — construcción</span>
                  </div>
                  <div class="subcard-body grid-2">
                    <div class="field">
                      <label>Empleos directos</label>
                      <input v-model="form.empleos_directos_construccion" type="number" min="0" placeholder="0" :disabled="guardando" />
                    </div>
                    <div class="field">
                      <label>Empleos indirectos</label>
                      <input v-model="form.empleos_indirectos_construccion" type="number" min="0" placeholder="0" :disabled="guardando" />
                    </div>
                  </div>
                </div>

                <div class="subcard subcard-accent">
                  <div class="subcard-header">
                    <i class="ti ti-settings-cog"></i>
                    <span>Empleos — operación</span>
                  </div>
                  <div class="subcard-body grid-2">
                    <div class="field">
                      <label>Empleos directos</label>
                      <input v-model="form.empleos_directos_operacion" type="number" min="0" placeholder="0" :disabled="guardando" />
                    </div>
                    <div class="field">
                      <label>Empleos indirectos</label>
                      <input v-model="form.empleos_indirectos_operacion" type="number" min="0" placeholder="0" :disabled="guardando" />
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <!-- UBICACIONES -->
          <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
              <div>
                <h2>Ubicación geográfica</h2>
                <p>Este proyecto puede tener una o más ubicaciones.</p>
              </div>
              <button
                type="button"
                @click="agregarUbicacion"
                :disabled="guardando"
                style="flex-shrink:0;display:inline-flex;align-items:center;gap:6px;padding:7px 12px;border:none;border-radius:7px;background:#00c9a7;color:#04211c;font-weight:700;font-size:.72rem;cursor:pointer;"
              >
                <i class="ti ti-plus"></i> Ubicación
              </button>
            </div>

            <div class="card-body">
              <div v-if="!ubicaciones.length" style="text-align:center;padding:20px 0;color:#647a8e;font-size:.78rem;">
                Sin ubicaciones agregadas todavía.
              </div>

              <div
                v-for="(ubi, iUbi) in ubicaciones"
                :key="iUbi"
                class="subcard"
                style="position:relative;"
              >
                <button
                  type="button"
                  @click="eliminarUbicacion(iUbi)"
                  :disabled="guardando"
                  style="position:absolute;top:12px;right:12px;background:none;border:none;color:#f87171;cursor:pointer;font-size:.85rem;z-index:2;"
                  title="Quitar ubicación"
                >✕</button>

                <div class="grid-3" style="display:grid;gap:14px;padding-right:24px;">
                  <div class="field">
                    <label>Departamento</label>
                    <input v-model="ubi.departamento" type="text" placeholder="Ej. Beni" :disabled="guardando" />
                  </div>
                  <div class="field">
                    <label>Provincia</label>
                    <input v-model="ubi.provincia" type="text" :disabled="guardando" />
                  </div>
                  <div class="field">
                    <label>Municipio</label>
                    <input v-model="ubi.municipio" type="text" :disabled="guardando" />
                  </div>
                  <div class="field span-full">
                    <label>Comunidad / localidad</label>
                    <input v-model="ubi.comunidad_localidad" type="text" :disabled="guardando" />
                  </div>

                  <div class="field span-full">
                    <MapaUbicacion :model-value="ubi" @update:model-value="val => ubicaciones[iUbi] = val" />
                  </div>

                  <div class="section-tag span-full">Coordenadas UTM</div>

                  <div class="field">
                    <label>Norte (X)</label>
                    <input v-model="ubi.coordenada_norte" type="number" step="0.01" :disabled="guardando" />
                  </div>
                  <div class="field">
                    <label>Este (Y)</label>
                    <input v-model="ubi.coordenada_este" type="number" step="0.01" :disabled="guardando" />
                  </div>
                  <div class="field">
                    <label>Zona</label>
                    <input v-model="ubi.zona_utm" type="text" :disabled="guardando" />
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- ========================================================
             PASO 3 — Fechas y plazos
        ========================================================= -->
        <div v-show="currentStep === 3" class="paso3-wrap">
          <div class="card">
            <div class="card-header">
              <h2>Fechas y plazos</h2>
              <p>Los plazos se calculan automáticamente a partir de las fechas.</p>
            </div>

            <div class="card-body">
              <div class="field" style="margin-bottom:16px;max-width:280px;">
                <label><i class="ti ti-flag-3"></i> Fecha de inicio (contractual)</label>
                <input v-model="form.fecha_inicio_contractual" type="date" :disabled="guardando" />
                <span class="field-hint">Punto de partida para ambas programaciones.</span>
              </div>

              <div class="dates-grid">
                <div class="subcard">
                  <div class="subcard-header">
                    <i class="ti ti-calendar-event"></i>
                    <span>Programación inicial</span>
                  </div>
                  <div class="subcard-body">
                    <div class="field">
                      <label>Fecha de conclusión inicial</label>
                      <input v-model="form.fecha_conclusion_inicial_contractual" type="date" :disabled="guardando" />
                    </div>
                    <div class="field">
                      <label>
                        Plazo contractual inicial (días)
                        <span class="tag-auto">Automático</span>
                      </label>
                      <input
                        :value="plazoInicialCalculado !== null ? fmtNum(plazoInicialCalculado) + ' días' : '—'"
                        type="text"
                        disabled
                        class="field-calculated"
                      />
                      <span v-if="plazoInicialCalculado !== null && plazoInicialCalculado < 0" class="field-error">
                        La conclusión inicial no puede ser anterior a la fecha de inicio.
                      </span>
                    </div>
                  </div>
                </div>

                <div class="subcard subcard-accent">
                  <div class="subcard-header">
                    <i class="ti ti-calendar-repeat"></i>
                    <span>Programación (s/modificaciones)</span>
                  </div>
                  <div class="subcard-body">
                    <div class="field">
                      <label>Fecha de conclusión (s/modificaciones)</label>
                      <input v-model="form.fecha_conclusion_actual" type="date" :disabled="guardando" />
                    </div>
                    <div class="field">
                      <label>
                        Plazo contractual (días s/modificaciones)
                        <span class="tag-auto">Automático</span>
                      </label>
                      <input
                        :value="plazoActualCalculado !== null ? fmtNum(plazoActualCalculado) + ' días' : '—'"
                        type="text"
                        disabled
                        class="field-calculated"
                      />
                      <span v-if="plazoActualCalculado !== null && plazoActualCalculado < 0" class="field-error">
                        La conclusión no puede ser anterior a la fecha de inicio.
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <div
                v-if="extensionPlazo !== null && extensionPlazo !== 0"
                class="plazo-comparativo"
                :class="extensionPlazo > 0 ? 'plazo-ampliacion' : 'plazo-reduccion'"
              >
                <i class="ti" :class="extensionPlazo > 0 ? 'ti-trending-up' : 'ti-trending-down'"></i>
                <span class="plazo-comparativo-texto">
                  {{ extensionPlazo > 0 ? 'Ampliación de plazo:' : 'Reducción de plazo:' }}
                  <strong>{{ extensionPlazo > 0 ? '+' : '' }}{{ fmtNum(extensionPlazo) }} días</strong>
                </span>
                <span class="plazo-comparativo-hint">respecto a la programación inicial</span>
              </div>

              <div v-if="diasTranscurridos !== null || diasRestantes !== null" class="progreso-section">
                <div class="progreso-header">
                  <span><i class="ti ti-clock"></i> Estado del cronograma</span>
                </div>
                <div class="cronograma-stats">
                  <div class="stat-chip">
                    <div class="stat-label">Días transcurridos</div>
                    <div class="stat-value">{{ diasTranscurridos !== null ? fmtNum(diasTranscurridos) : '—' }}</div>
                  </div>
                  <div class="stat-chip" :class="{ 'stat-danger': cronogramaVencido }">
                    <div class="stat-label">Días restantes</div>
                    <div class="stat-value">
                      {{ diasRestantes !== null ? fmtNum(Math.abs(diasRestantes)) : '—' }}
                      <span v-if="cronogramaVencido" class="stat-suffix">(vencido)</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- NAVEGACIÓN -->
        <div class="form-actions">
          <button
            v-if="currentStep === 1"
            type="button"
            class="btn-secondary"
            @click="volver"
            :disabled="guardando"
          >
            Cancelar
          </button>
          <button
            v-else
            type="button"
            class="btn-secondary"
            @click="pasoAnterior"
            :disabled="guardando"
          >
            Atrás
          </button>

          <button type="submit" class="btn-primary" :disabled="guardando">
            <span v-if="currentStep < steps.length">Siguiente</span>
            <span v-else>{{ guardando ? 'Guardando...' : 'Guardar cambios' }}</span>
          </button>
        </div>

      </form>

    </template>

  </div>
</template>

<style scoped>

.page {
  padding: 24px;
  color: #dcebf5;
  font-family: 'Segoe UI', Arial, sans-serif;
  max-width: 1680px;
  margin: 0 auto;
}

.state-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 80px 20px;
  color: #8ea9bf;
  font-size: .9rem;
}

.spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.page-intro {
  color: #8ea9bf;
  font-size: .82rem;
  margin: 0 0 18px;
}

/* --- Stepper --- */
.stepper {
  display: flex;
  align-items: flex-start;
  margin-bottom: 20px;
  max-width: 620px;
}

.stepper-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  flex-shrink: 0;
}

.stepper-circle {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #1e3a52;
  background: #0d1f30;
  color: #8ea9bf;
  font-weight: 700;
  font-size: .85rem;
  transition: .2s;
}

.stepper-item.active .stepper-circle {
  border-color: #00c9a7;
  background: rgba(0, 201, 167, .12);
  color: #00c9a7;
}

.stepper-item.done .stepper-circle {
  border-color: #00c9a7;
  background: #00c9a7;
  color: #04211c;
}

.stepper-label {
  font-size: .66rem;
  color: #8ea9bf;
  font-weight: 600;
  white-space: nowrap;
  max-width: 100px;
  text-align: center;
  line-height: 1.2;
}

.stepper-item.active .stepper-label,
.stepper-item.done .stepper-label {
  color: #d0dde8;
}

.stepper-connector {
  flex: 1;
  height: 2px;
  background: #1e3a52;
  margin-top: 17px;
  transition: .2s;
}

.stepper-connector.done {
  background: #00c9a7;
}

.form-error {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  margin-bottom: 18px;
  border-radius: 8px;
  background: rgba(248, 113, 113, .1);
  border: 1px solid rgba(248, 113, 113, .3);
  color: #fca5a5;
  font-size: .82rem;
}

.row-2col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px;
  align-items: start;
  margin-bottom: 18px;
}

.paso3-wrap {
  max-width: 900px;
  margin-bottom: 18px;
}

.card {
  border: 1px solid #1e3a52;
  border-radius: 12px;
  background: #0d1f30;
  padding: 20px;
}

.card-header h2 {
  margin: 0;
  font-size: .95rem;
  font-weight: 800;
  color: #f2fbff;
}

.card-header p {
  margin: 2px 0 12px;
  color: #8ea9bf;
  font-size: .76rem;
}

.card-body {
  border-top: 1px solid #1e3a52;
  padding-top: 16px;
  display: grid;
  gap: 16px;
}

.grid-2 { grid-template-columns: repeat(2, 1fr); }
.grid-3 { grid-template-columns: repeat(3, 1fr); }

.dates-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.subcard {
  border: 1px solid #1e3a52;
  border-radius: 10px;
  background: #0a1624;
  padding: 14px 16px 16px;
}

.subcard-accent {
  border-color: rgba(0, 201, 167, .35);
}

.subcard-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-bottom: 10px;
  margin-bottom: 14px;
  border-bottom: 1px solid #1e3a52;
  color: #00c9a7;
  font-size: .74rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .03em;
}

.subcard-header i {
  font-size: .95rem;
}

.subcard-body {
  display: grid;
  gap: 14px;
}

.field-hint {
  color: #4d6478;
  font-size: .68rem;
  margin-top: 2px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field.span-full { grid-column: 1 / -1; }

.field label {
  color: #8ea9bf;
  font-size: .68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .04em;
  display: flex;
  align-items: center;
  gap: 6px;
}

.tag-auto {
  text-transform: none;
  letter-spacing: 0;
  font-weight: 700;
  font-size: .62rem;
  padding: 1px 6px;
  border-radius: 4px;
  background: rgba(0, 201, 167, .12);
  color: #00c9a7;
}

.field input {
  padding: 9px 11px;
  border: 1px solid #1e3a52;
  border-radius: 7px;
  background: #0a1624;
  color: #dcebf5;
  font-size: .85rem;
  outline: 0;
  transition: .15s;
  width: 100%;
  box-sizing: border-box;
}

.subcard .field input {
  background: #0d1f30;
}

.field-calculated {
  background: #091622 !important;
  color: #00c9a7 !important;
  font-weight: 700;
  cursor: default;
}

.field input:focus {
  border-color: #00c9a7;
  box-shadow: 0 0 0 3px rgba(0, 201, 167, .1);
}

.field input:disabled { opacity: .85; cursor: not-allowed; }
.field input::placeholder { color: #4d6478; }

.field-error {
  color: #fca5a5;
  font-size: .72rem;
}

.section-tag {
  padding: 6px 10px;
  border-radius: 6px;
  background: rgba(0, 201, 167, .1);
  color: #00c9a7;
  font-size: .74rem;
  font-weight: 700;
}

.plazo-comparativo {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  padding: 10px 14px;
  border-radius: 8px;
  font-size: .78rem;
  margin-top: 16px;
}

.plazo-comparativo i {
  font-size: 1rem;
}

.plazo-ampliacion {
  background: rgba(245, 158, 11, .1);
  border: 1px solid rgba(245, 158, 11, .3);
  color: #fbbf24;
}

.plazo-reduccion {
  background: rgba(0, 201, 167, .1);
  border: 1px solid rgba(0, 201, 167, .3);
  color: #00c9a7;
}

.plazo-comparativo-texto strong {
  font-weight: 800;
}

.plazo-comparativo-hint {
  color: #8ea9bf;
  font-size: .7rem;
}

.progreso-section {
  border-top: 1px solid #1e3a52;
  padding-top: 16px;
  margin-top: 16px;
  display: grid;
  gap: 10px;
}

.progreso-header {
  color: #8ea9bf;
  font-size: .74rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .04em;
}

.progreso-header i {
  margin-right: 4px;
}

.cronograma-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 16px;
}

.stat-chip {
  border: 1px solid #1e3a52;
  border-radius: 10px;
  background: #0a1624;
  padding: 14px 16px;
  text-align: center;
}

.stat-chip.stat-danger {
  border-color: rgba(248, 113, 113, .4);
}

.stat-label {
  color: #8ea9bf;
  font-size: .68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .04em;
  margin-bottom: 6px;
}

.stat-value {
  color: #f2fbff;
  font-size: 1.3rem;
  font-weight: 800;
}

.stat-chip.stat-danger .stat-value {
  color: #fca5a5;
}

.stat-suffix {
  font-size: .7rem;
  font-weight: 600;
  vertical-align: middle;
}

.form-actions {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  margin-top: 8px;
}

.btn-secondary {
  padding: 10px 18px;
  border: 1px solid #1e3a52;
  border-radius: 8px;
  background: transparent;
  color: #8ea9bf;
  font-weight: 600;
  font-size: .85rem;
  cursor: pointer;
}

.btn-secondary:hover { border-color: #8ea9bf; color: #dcebf5; }

.btn-primary {
  padding: 10px 22px;
  border: 0;
  border-radius: 8px;
  background: linear-gradient(135deg, #00d0ae, #00aa91);
  color: #052029;
  font-weight: 700;
  font-size: .85rem;
  cursor: pointer;
  box-shadow: 0 8px 22px rgba(0, 201, 167, .18);
}

.btn-primary:hover { filter: brightness(1.08); }
.btn-primary:disabled, .btn-secondary:disabled { opacity: .6; cursor: not-allowed; }

@media (max-width: 860px) {
  .row-2col { grid-template-columns: 1fr; }
}

@media (max-width: 640px) {
  .grid-2, .grid-3 { grid-template-columns: 1fr; }
  .stepper-label { max-width: 70px; font-size: .6rem; }
}

</style>