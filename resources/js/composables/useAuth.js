import { computed, ref } from 'vue'

const STORAGE_KEY = 'sipip-session'
const TOKEN_KEY = 'sipip-token'

/**
 * Normaliza el usuario que manda el backend (con columnas en español,
 * ej. "nombre", "correo_electronico") a un formato consistente que
 * usan los componentes del frontend ("name", "email", "role").
 * Conserva también los campos originales por si algún componente
 * los necesita directamente.
 */
function normalizarUsuario(raw) {
  if (!raw) return null

  const nombre = raw.nombre || ''
  const email = raw.correo_electronico || ''
  const role = raw.rol?.nombre || ''

  const iniciales = nombre
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map(p => p[0])
    .join('')
    .toUpperCase()

  return {
    ...raw,
    name: nombre,
    email,
    role,
    initials: iniciales || 'US',
  }
}
const storedUser =
  localStorage.getItem(STORAGE_KEY) ||
  sessionStorage.getItem(STORAGE_KEY)

const storedToken =
  localStorage.getItem(TOKEN_KEY) ||
  sessionStorage.getItem(TOKEN_KEY)

const currentUser = ref(
  storedUser ? JSON.parse(storedUser) : null
)

const authToken = ref(
  storedToken || null
)


export function useAuth() {

  const isAuthenticated = computed(() => {
    return Boolean(
      currentUser.value &&
      authToken.value
    )
  })


  /**
   * =========================================
   * LOGIN
   * =========================================
   */
  async function login(
    email,
    password,
    remember = false
  ) {

    try {

      const response = await fetch('/api/login', {
        method: 'POST',

        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },

        body: JSON.stringify({
          correo_electronico: email.trim(),
          contrasena: password,
        }),
      })


      const data = await response.json()


      if (!response.ok) {

        return {
          ok: false,

          message:
            data.message ||
            'Correo o contraseña incorrectos.',
        }
      }


      const token = data.token
      const user = normalizarUsuario(data.usuario)


      if (!token || !user) {

        return {
          ok: false,

          message:
            'La respuesta del servidor no es válida.',
        }
      }


      /**
       * Guardamos en memoria
       */
      currentUser.value = user
      authToken.value = token


      /**
       * Elegimos almacenamiento
       */
      const storage = remember
        ? localStorage
        : sessionStorage


      /**
       * Limpiamos sesiones anteriores
       */
      localStorage.removeItem(STORAGE_KEY)
      sessionStorage.removeItem(STORAGE_KEY)

      localStorage.removeItem(TOKEN_KEY)
      sessionStorage.removeItem(TOKEN_KEY)


      /**
       * Guardamos sesión
       */
      storage.setItem(
        STORAGE_KEY,
        JSON.stringify(user)
      )

      storage.setItem(
        TOKEN_KEY,
        token
      )


      return {
        ok: true,

        user,

        token,
      }

    } catch (error) {

      console.error(
        'Error de autenticación:',
        error
      )

      return {
        ok: false,

        message:
          'No se pudo conectar con el servidor.',
      }
    }
  }


  /**
   * =========================================
   * OBTENER TOKEN
   * =========================================
   */
  function getToken() {

    return authToken.value
  }


  /**
   * =========================================
   * OBTENER USUARIO DESDE LARAVEL
   * =========================================
   */
  async function fetchUser() {

    const token = authToken.value

    if (!token) {
      return null
    }


    try {

      const response = await fetch('/api/user', {
        method: 'GET',

        headers: {
          'Accept': 'application/json',

          'Authorization':
            `Bearer ${token}`,
        },
      })


      if (!response.ok) {

        if (response.status === 401) {
          logout()
        }

        return null
      }


      const data = await response.json()


      const user =
        normalizarUsuario(data.usuario || data)


      currentUser.value = user


      /**
       * Actualizamos almacenamiento
       */
      const storage =
        localStorage.getItem(TOKEN_KEY)
          ? localStorage
          : sessionStorage


      storage.setItem(
        STORAGE_KEY,
        JSON.stringify(user)
      )


      return user

    } catch (error) {

      console.error(
        'Error obteniendo usuario:',
        error
      )

      return null
    }
  }


  /**
   * =========================================
   * LOGOUT
   * =========================================
   */
  async function logout() {

    const token = authToken.value


    /**
     * Avisamos a Laravel
     */
    if (token) {

      try {

        await fetch('/api/logout', {
          method: 'POST',

          headers: {
            'Accept': 'application/json',

            'Authorization':
              `Bearer ${token}`,
          },
        })

      } catch (error) {

        console.error(
          'Error cerrando sesión:',
          error
        )
      }
    }


    /**
     * Limpiamos memoria
     */
    currentUser.value = null
    authToken.value = null


    /**
     * Limpiamos almacenamiento
     */
    localStorage.removeItem(STORAGE_KEY)
    sessionStorage.removeItem(STORAGE_KEY)

    localStorage.removeItem(TOKEN_KEY)
    sessionStorage.removeItem(TOKEN_KEY)
  }


  return {
    currentUser,
    isAuthenticated,
    login,
    logout,
    getToken,
    fetchUser,
  }
}