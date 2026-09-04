<script setup>
import { ref, onMounted, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import proj4 from 'proj4'

const props = defineProps({
  modelValue: Object
})
const emit = defineEmits(['update:modelValue'])

const mapContainer = ref(null)
const busqueda = ref('')
const buscando = ref(false)
const resultados = ref([])
const mensajeError = ref('')

let map = null
let marcador = null

const BOLIVIA_CENTER = [-16.5, -64.6]
const BOLIVIA_ZOOM = 5

onMounted(() => {
  map = L.map(mapContainer.value).setView(BOLIVIA_CENTER, BOLIVIA_ZOOM)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(map)

  if (props.modelValue?.coordenada_norte && props.modelValue?.coordenada_este && props.modelValue?.zona_utm) {
    const latlng = utmALatLng(props.modelValue.coordenada_este, props.modelValue.coordenada_norte, props.modelValue.zona_utm)
    if (latlng) {
      colocarMarcador(latlng.lat, latlng.lng)
      map.setView([latlng.lat, latlng.lng], 14)
    }
  }

  map.on('click', (e) => {
    colocarMarcador(e.latlng.lat, e.latlng.lng)
    reversaGeocodificar(e.latlng.lat, e.latlng.lng)
  })
})

function colocarMarcador(lat, lng) {
  if (marcador) {
    marcador.setLatLng([lat, lng])
  } else {
    marcador = L.marker([lat, lng], { draggable: true }).addTo(map)
    marcador.on('dragend', () => {
      const pos = marcador.getLatLng()
      actualizarCoordenadas(pos.lat, pos.lng)
      reversaGeocodificar(pos.lat, pos.lng)
    })
  }
  actualizarCoordenadas(lat, lng)
}

function actualizarCoordenadas(lat, lng) {
  const utm = latLngAUtm(lat, lng)
  emit('update:modelValue', {
    ...props.modelValue,
    coordenada_norte: utm.norte,
    coordenada_este: utm.este,
    zona_utm: utm.zona,
  })
}

/* ================================
   CONVERSIÓN LAT/LNG <-> UTM
================================ */
function bandaUtm(lat) {
  const letras = 'CDEFGHJKLMNPQRSTUVWXX'
  if (lat < -80 || lat > 84) return ''
  const indice = Math.floor((lat + 80) / 8)
  return letras[indice]
}

function latLngAUtm(lat, lng) {
  const zonaNumero = Math.floor((lng + 180) / 6) + 1
  const hemisferio = lat >= 0 ? 'north' : 'south'
  const proyeccion = `+proj=utm +zone=${zonaNumero} +${hemisferio} +ellps=WGS84 +datum=WGS84 +units=m +no_defs`

  const [este, norte] = proj4('EPSG:4326', proyeccion, [lng, lat])

  return {
    este: Math.round(este * 100) / 100,
    norte: Math.round(norte * 100) / 100,
    zona: `${zonaNumero} ${bandaUtm(lat)}`,
  }
}

function utmALatLng(este, norte, zonaTexto) {
  if (!zonaTexto) return null

  // Los datos que llegan de la API pueden venir como string (ej. campos "decimal"
  // de Laravel se serializan como texto), y proj4 exige números reales — se
  // convierten y validan aquí antes de tocar proj4.
  const esteNum = Number(este)
  const norteNum = Number(norte)

  if (!Number.isFinite(esteNum) || !Number.isFinite(norteNum)) {
    console.warn('MapaUbicacion: coordenadas UTM no numéricas, se omite el marcador', { este, norte })
    return null
  }

  const zonaNumero = parseInt(zonaTexto, 10)
  if (!zonaNumero) return null

  const banda = zonaTexto.trim().slice(-1).toUpperCase()
  const hemisferio = banda >= 'N' ? 'north' : 'south'
  const proyeccion = `+proj=utm +zone=${zonaNumero} +${hemisferio} +ellps=WGS84 +datum=WGS84 +units=m +no_defs`

  try {
    const [lng, lat] = proj4(proyeccion, 'EPSG:4326', [esteNum, norteNum])

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
      console.warn('MapaUbicacion: la conversión UTM→lat/lng no dio un resultado válido', { esteNum, norteNum, zonaTexto })
      return null
    }

    return { lat, lng }
  } catch (e) {
    console.error('MapaUbicacion: error al convertir UTM a lat/lng', e)
    return null
  }
}

/* ================================
   BÚSQUEDA POR DIRECCIÓN (Nominatim)
================================ */
let temporizador = null

watch(busqueda, (valor) => {
  clearTimeout(temporizador)
  resultados.value = []

  if (valor.trim().length < 4) return

  temporizador = setTimeout(() => buscarDireccion(valor), 500)
})

async function buscarDireccion(texto) {
  buscando.value = true
  mensajeError.value = ''

  try {
    const url = `https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=5&countrycodes=bo&q=${encodeURIComponent(texto)}`

    const res = await fetch(url, {
      headers: { 'Accept-Language': 'es' },
    })

    resultados.value = await res.json()

  } catch (e) {
    mensajeError.value = 'No se pudo buscar la dirección.'
    console.error(e)
  } finally {
    buscando.value = false
  }
}

async function reversaGeocodificar(lat, lng) {
  try {
    const url = `https://nominatim.openstreetmap.org/reverse?format=json&addressdetails=1&lat=${lat}&lon=${lng}`

    const res = await fetch(url, {
      headers: { 'Accept-Language': 'es' },
    })

    const data = await res.json()
    const dir = data.address || {}

    emit('update:modelValue', {
      ...props.modelValue,
      departamento: dir.state || props.modelValue?.departamento || '',
      provincia: dir.county || props.modelValue?.provincia || '',
      municipio: dir.city || dir.town || dir.municipality || props.modelValue?.municipio || '',
      comunidad_localidad: dir.village || dir.suburb || dir.hamlet || props.modelValue?.comunidad_localidad || '',
      ...latLngAUtmComoCampos(lat, lng),
    })

  } catch (e) {
    console.error('Error en geocodificación inversa:', e)
  }
}

function seleccionarResultado(resultado) {
  const lat = parseFloat(resultado.lat)
  const lng = parseFloat(resultado.lon)

  colocarMarcador(lat, lng)
  map.setView([lat, lng], 15)

  const dir = resultado.address || {}

  emit('update:modelValue', {
    ...props.modelValue,
    departamento: dir.state || props.modelValue?.departamento || '',
    provincia: dir.county || props.modelValue?.provincia || '',
    municipio: dir.city || dir.town || dir.municipality || props.modelValue?.municipio || '',
    comunidad_localidad: dir.village || dir.suburb || dir.hamlet || props.modelValue?.comunidad_localidad || '',
    ...latLngAUtmComoCampos(lat, lng),
  })

  busqueda.value = resultado.display_name
  resultados.value = []
}

function latLngAUtmComoCampos(lat, lng) {
  const utm = latLngAUtm(lat, lng)
  return {
    coordenada_norte: utm.norte,
    coordenada_este: utm.este,
    zona_utm: utm.zona,
  }
}
</script>

<template>
  <div class="mapa-ubicacion">

    <div class="search-box">
      <i class="ti ti-search"></i>
      <input
        v-model="busqueda"
        type="text"
        placeholder="Buscar dirección, comunidad o municipio..."
      />
      <i v-if="buscando" class="ti ti-loader-2 spin"></i>
    </div>

    <ul v-if="resultados.length" class="resultados">
      <li
        v-for="r in resultados"
        :key="r.place_id"
        @click="seleccionarResultado(r)"
      >
        {{ r.display_name }}
      </li>
    </ul>

    <p v-if="mensajeError" class="mensaje-error">{{ mensajeError }}</p>

    <div ref="mapContainer" class="mapa"></div>

    <p class="hint">
      Busca una dirección, haz clic en el mapa, o arrastra el marcador para ajustar la ubicación exacta.
    </p>

  </div>
</template>

<style scoped>
.mapa-ubicacion {
  position: relative;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  height: 40px;
  padding: 0 12px;
  margin-bottom: 8px;
  border: 1px solid #1e3a52;
  border-radius: 8px;
  background: #0a1624;
}

.search-box i {
  color: #7190a7;
  font-size: 16px;
}

.search-box input {
  flex: 1;
  height: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  color: #dcebf5;
  font-size: .82rem;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.resultados {
  position: absolute;
  top: 48px;
  left: 0;
  right: 0;
  z-index: 1000;
  list-style: none;
  margin: 0;
  padding: 4px;
  background: #0d1f30;
  border: 1px solid #1e3a52;
  border-radius: 8px;
  max-height: 200px;
  overflow-y: auto;
}

.resultados li {
  padding: 8px 10px;
  font-size: .78rem;
  color: #dcebf5;
  cursor: pointer;
  border-radius: 6px;
}

.resultados li:hover {
  background: rgba(0, 201, 167, .1);
}

.mensaje-error {
  color: #fca5a5;
  font-size: .78rem;
  margin: 4px 0;
}

.mapa {
  height: 260px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #1e3a52;
}

.hint {
  margin: 6px 0 0;
  color: #7190a7;
  font-size: .7rem;
}
</style>