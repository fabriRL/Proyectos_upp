// Bloquea a nivel GLOBAL las teclas "e", "E", "+", "-" en CUALQUIER
// input[type="number"] de toda la app, y muestra un aviso flotante
// justo debajo del campo cuando alguien lo intenta.
//
// El aviso NO se inserta como hijo del formulario (para no interferir
// con cómo Vue actualiza esa parte de la pantalla) — es un único
// elemento reutilizable pegado al <body>, posicionado con coordenadas
// sobre el campo activo cada vez que hace falta.

const TECLAS_BLOQUEADAS = ['e', 'E', '+', '-']
const MENSAJE_AVISO = 'No se permite ingresar "e", "+" ni "-" en este campo'
const DURACION_VISIBLE_MS = 2200

let elementoAviso = null
let timeoutOcultar = null

function esInputNumerico(target) {
  return target instanceof HTMLInputElement && target.type === 'number'
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

document.addEventListener('keydown', (event) => {
  if (!esInputNumerico(event.target)) return
  if (TECLAS_BLOQUEADAS.includes(event.key)) {
    event.preventDefault()
    mostrarAvisoDebajoDe(event.target)
  }
})

document.addEventListener('input', (event) => {
  if (!esInputNumerico(event.target)) return

  const valorLimpio = event.target.value.replace(/[eE+\-]/g, '')
  if (valorLimpio !== event.target.value) {
    event.target.value = valorLimpio
    event.target.dispatchEvent(new Event('input'))
    mostrarAvisoDebajoDe(event.target)
  }
})

// Oculta el aviso de inmediato si el usuario hace scroll o cambia de
// ventana — evita que quede "flotando" en una posición vieja.
window.addEventListener('scroll', () => {
  if (elementoAviso) elementoAviso.style.opacity = '0'
}, true)