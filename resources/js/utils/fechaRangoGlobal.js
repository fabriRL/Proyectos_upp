// Aplica un rango de fechas válido (2000-2060) a CUALQUIER
// input[type="date"] de toda la app — sin tocar cada formulario uno
// por uno. Usa un MutationObserver porque los campos de fecha de esta
// app se crean dinámicamente (modales de Vue que se abren/cierran),
// así que hay que "vigilar" el DOM y aplicar el límite apenas
// aparece un campo nuevo, no solo al cargar la página.

const FECHA_MINIMA = '2000-01-01'
const FECHA_MAXIMA = '2060-12-31'
const MENSAJE_AVISO = 'Ingresa una fecha entre el año 2000 y el 2060'
const DURACION_VISIBLE_MS = 2500

let elementoAviso = null
let timeoutOcultar = null

function esInputFecha(el) {
  return el instanceof HTMLInputElement && el.type === 'date'
}

function aplicarLimites(input) {
  if (!input.min) input.min = FECHA_MINIMA
  if (!input.max) input.max = FECHA_MAXIMA
}

// Recorre todo el documento una vez al cargar, por si ya hay campos
// de fecha visibles antes de que el observer empiece a vigilar.
function aplicarATodosLosExistentes() {
  document.querySelectorAll('input[type="date"]').forEach(aplicarLimites)
}

function obtenerElementoAviso() {
  if (elementoAviso) return elementoAviso

  elementoAviso = document.createElement('div')
  elementoAviso.textContent = MENSAJE_AVISO
  Object.assign(elementoAviso.style, {
    position: 'fixed',
    zIndex: '2147483647',
    background: '#2a1414',
    border: '1px solid rgba(248, 113, 113, 0.4)',   
    color: '#fca5a5',
    fontSize: '0.72rem',
    fontFamily: 'Segoe UI, Arial, sans-serif',
    padding: '5px 10px',
    borderRadius: '6px',
    boxShadow: '0 6px 16px rgba(0,0,0,0.35)',
    pointerEvents: 'none',
    opacity: '0',
    transition: 'opacity .12s ease',
    whiteSpace: 'nowrap',
  })
  document.body.appendChild(elementoAviso)
  return elementoAviso
}

function mostrarAvisoDebajoDe(input) {
  const aviso = obtenerElementoAviso()
  const rect = input.getBoundingClientRect()

  aviso.style.top = `${rect.bottom + 4}px`
  aviso.style.left = `${rect.left}px`
  aviso.style.opacity = '1'

  clearTimeout(timeoutOcultar)
  timeoutOcultar = setTimeout(() => {
    aviso.style.opacity = '0'
  }, DURACION_VISIBLE_MS)
}

// Si el usuario escribe manualmente una fecha fuera de rango (no todos
// los navegadores lo bloquean solo con min/max mientras se escribe),
// se limpia el campo y se avisa, apenas se detecta el valor inválido.
function revisarValorFueraDeRango(event) {
  const input = event.target
  if (!esInputFecha(input)) return
  if (!input.value) return

  if (input.value < FECHA_MINIMA || input.value > FECHA_MAXIMA) {
    input.value = ''
    input.dispatchEvent(new Event('input'))
    mostrarAvisoDebajoDe(input)
  }
}

document.addEventListener('input', revisarValorFueraDeRango, true)
document.addEventListener('change', revisarValorFueraDeRango, true)

// Vigila el DOM completo: cada vez que Vue agrega un modal/formulario
// nuevo con campos de fecha adentro, se les aplica el límite al instante.
const observer = new MutationObserver((mutaciones) => {
  for (const mutacion of mutaciones) {
    mutacion.addedNodes.forEach((nodo) => {
      if (!(nodo instanceof HTMLElement)) return

      if (esInputFecha(nodo)) {
        aplicarLimites(nodo)
      }

      nodo.querySelectorAll?.('input[type="date"]').forEach(aplicarLimites)
    })
  }
})

observer.observe(document.body, { childList: true, subtree: true })

aplicarATodosLosExistentes()