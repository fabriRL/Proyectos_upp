<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const { login } = useAuth()
const router = useRouter()

const email = ref('')
const password = ref('')
const remember = ref(false)
const showPassword = ref(false)
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''

  if (!email.value || !password.value) {
    error.value = 'Ingresa tu correo institucional y contraseña.'
    return
  }

  loading.value = true

  try {
    const result = await login(
      email.value,
      password.value,
      remember.value
    )

    if (!result.ok) {
      error.value = result.message
      return
    }

    await router.push({
      name: 'dashboard'
    })

  } catch (e) {
    console.error('Error al iniciar sesión:', e)

    error.value = 'No se pudo conectar con el servidor.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="login-page">

    <!-- PANEL IZQUIERDO -->
    <section class="login-brand">
      <div class="brand-content">

        <div class="brand-mark">
          <i class="ti ti-building-community"></i>
        </div>

        <p class="eyebrow">EMAPA</p>

        <h1>SIPIP</h1>

        <p class="brand-description">
          Sistema de Información de Proyectos de Inversión Pública
        </p>

        <div class="brand-line"></div>

        <p class="brand-footer">
          Gestión transparente para el desarrollo productivo.
        </p>

      </div>
    </section>

    <!-- PANEL LOGIN -->
    <section class="login-panel">

      <form
        class="login-card"
        @submit.prevent="submit"
      >

        <!-- MARCA MOBILE -->
        <div class="mobile-brand">

          <div class="mobile-mark">
            <i class="ti ti-building-community"></i>
          </div>

          <span>SIPIP</span>

        </div>

        <p class="eyebrow">
          ACCESO SEGURO
        </p>

        <h2>
          Bienvenido/a
        </h2>

        <p class="intro">
          Ingresa con tus credenciales institucionales para continuar.
        </p>

        <!-- CORREO -->
        <label for="email">
          Correo institucional
        </label>

        <div class="field">

          <i class="ti ti-mail"></i>

          <input
            id="email"
            v-model="email"
            type="email"
            autocomplete="email"
            placeholder="nombre@emapa.gob.bo"
            :disabled="loading"
          />

        </div>

        <!-- CONTRASEÑA -->
        <label for="password">
          Contraseña
        </label>

        <div class="field">

          <i class="ti ti-lock"></i>

          <input
            id="password"
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="current-password"
            placeholder="Ingresa tu contraseña"
            :disabled="loading"
          />

          <button
            type="button"
            :aria-label="
              showPassword
                ? 'Ocultar contraseña'
                : 'Mostrar contraseña'
            "
            @click="showPassword = !showPassword"
            :disabled="loading"
          >
            <i
              :class="
                showPassword
                  ? 'ti ti-eye-off'
                  : 'ti ti-eye'
              "
            ></i>
          </button>

        </div>

        <!-- ERROR -->
        <p
          v-if="error"
          class="error-message"
        >
          <i class="ti ti-alert-circle"></i>

          {{ error }}
        </p>

        <!-- OPCIONES -->
        <div class="options">

          <label class="remember">

            <input
              v-model="remember"
              type="checkbox"
              :disabled="loading"
            />

            <span>
              Recordarme
            </span>

          </label>

          <a
            href="#"
            @click.prevent
          >
            ¿Olvidaste tu contraseña?
          </a>

        </div>

        <!-- BOTÓN -->
        <button
          class="submit"
          type="submit"
          :disabled="loading"
        >

          <span>
            {{
              loading
                ? 'Iniciando sesión...'
                : 'Iniciar sesión'
            }}
          </span>

          <i
            :class="
              loading
                ? 'ti ti-loader-2'
                : 'ti ti-arrow-right'
            "
          ></i>

        </button>

        <!-- AYUDA -->
        <p class="help">
          ¿Necesitas ayuda?

          <a href="mailto:soporte@emapa.gob.bo">
            Contacta a soporte técnico
          </a>
        </p>

      </form>

    </section>

  </main>
</template>

<style scoped>

.login-page {
  min-height: 100vh;
  display: grid;
  grid-template-columns: minmax(340px, 45%) 1fr;
  background: #091520;
  font-family: 'Segoe UI', Arial, sans-serif;
}

/* ========================================
   PANEL IZQUIERDO
======================================== */

.login-brand {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px;

  background:
    radial-gradient(
      circle at 15% 20%,
      rgba(0, 201, 167, .16),
      transparent 32%
    ),
    linear-gradient(
      145deg,
      #0d2635,
      #07121e
    );

  border-right: 1px solid #19354d;
  overflow: hidden;
}

.login-brand:after {
  content: '';

  position: absolute;

  width: 530px;
  height: 530px;

  border: 1px solid rgba(0, 201, 167, .1);

  border-radius: 50%;

  right: -290px;
  bottom: -240px;

  box-shadow:
    0 0 0 55px rgba(0, 201, 167, .025),
    0 0 0 110px rgba(0, 201, 167, .018);
}

.brand-content {
  position: relative;
  z-index: 1;
  max-width: 330px;
}

.brand-mark,
.mobile-mark {
  display: grid;
  place-items: center;

  background:
    linear-gradient(
      135deg,
      #00e5c0,
      #00a98f
    );

  color: #06202a;

  border-radius: 16px;

  box-shadow:
    0 12px 32px rgba(0, 201, 167, .2);
}

.brand-mark {
  width: 68px;
  height: 68px;

  font-size: 35px;

  margin-bottom: 34px;
}

/* ========================================
   TEXTOS
======================================== */

.eyebrow {
  font-size: .7rem;

  letter-spacing: .18em;

  color: #00c9a7;

  font-weight: 700;

  margin: 0 0 9px;
}

.brand-content h1 {
  color: #f2fbff;

  font-size: 3.4rem;

  line-height: 1;

  margin: 0;

  font-weight: 800;

  letter-spacing: .06em;
}

.brand-description {
  font-size: 1.13rem;

  line-height: 1.55;

  color: #b4c9d9;

  margin: 18px 0;
}

.brand-line {
  width: 48px;
  height: 3px;

  background: #00c9a7;

  border-radius: 3px;

  margin: 28px 0;
}

.brand-footer {
  font-size: .82rem;
  color: #7190a7;
}

/* ========================================
   PANEL LOGIN
======================================== */

.login-panel {
  display: grid;
  place-items: center;

  padding: 36px;

  background: #0a1624;
}

.login-card {
  width: min(100%, 410px);
}

.login-card h2 {
  margin: 0;

  color: #e6f3fa;

  font-size: 2rem;

  line-height: 1.25;
}

.intro {
  color: #8ea9bf;

  line-height: 1.5;

  margin: 8px 0 29px;
}

.login-card > label {
  display: block;

  color: #b4c9d9;

  font-size: .78rem;

  font-weight: 700;

  margin: 18px 0 7px;
}

/* ========================================
   CAMPOS
======================================== */

.field {
  height: 48px;

  border: 1px solid #1e3a52;

  background: #0d1f30;

  border-radius: 8px;

  display: flex;

  align-items: center;

  transition: .2s;
}

.field:focus-within {
  border-color: #00c9a7;

  box-shadow:
    0 0 0 3px rgba(0, 201, 167, .1);
}

.field > i {
  color: #7190a7;

  font-size: 18px;

  margin: 0 12px;
}

.field input {
  width: 100%;
  height: 100%;

  border: 0;
  outline: 0;

  background: transparent;

  color: #dcebf5;

  font-size: .9rem;
}

.field input::placeholder {
  color: #557087;
}

.field input:disabled {
  opacity: .65;
  cursor: not-allowed;
}

.field button {
  padding: 10px 13px;

  color: #8ea9bf;

  background: transparent;

  border: 0;

  cursor: pointer;
}

.field button:hover {
  color: #00c9a7;
}

.field button:disabled {
  opacity: .5;
  cursor: not-allowed;
}

/* ========================================
   ERROR
======================================== */

.error-message {
  display: flex;

  align-items: center;

  gap: 6px;

  margin: 10px 0 -2px;

  color: #fca5a5;

  font-size: .78rem;
}

/* ========================================
   OPCIONES
======================================== */

.options {
  display: flex;

  justify-content: space-between;

  align-items: center;

  margin: 17px 0 23px;

  font-size: .78rem;
}

.options a,
.help a {
  color: #00c9a7;

  text-decoration: none;
}

.options a:hover,
.help a:hover {
  text-decoration: underline;
}

.remember {
  display: flex;

  align-items: center;

  gap: 7px;

  color: #8ea9bf;

  cursor: pointer;
}

.remember input {
  accent-color: #00c9a7;
}

/* ========================================
   BOTÓN
======================================== */

.submit {
  width: 100%;
  height: 48px;

  display: flex;

  align-items: center;

  justify-content: center;

  gap: 10px;

  border-radius: 8px;

  border: 0;

  background:
    linear-gradient(
      135deg,
      #00d0ae,
      #00aa91
    );

  color: #052029;

  font-weight: 800;

  box-shadow:
    0 8px 22px rgba(0, 201, 167, .18);

  transition: .2s;

  cursor: pointer;
}

.submit:hover {
  filter: brightness(1.08);

  transform: translateY(-1px);
}

.submit:disabled {
  opacity: .7;

  cursor: not-allowed;

  transform: none;

  filter: none;
}

/* ========================================
   AYUDA
======================================== */

.help {
  text-align: center;

  margin-top: 25px;

  color: #7190a7;

  font-size: .74rem;
}

/* ========================================
   MOBILE
======================================== */

.mobile-brand {
  display: none;
}

/* ========================================
   RESPONSIVE
======================================== */

@media (max-width: 760px) {

  .login-page {
    display: block;
  }

  .login-brand {
    display: none;
  }

  .login-panel {
    min-height: 100vh;

    padding: 28px 23px;
  }

  .mobile-brand {
    display: flex;

    align-items: center;

    gap: 10px;

    margin-bottom: 48px;

    color: #f0faff;

    font-weight: 800;

    font-size: 1.3rem;

    letter-spacing: .08em;
  }

  .mobile-mark {
    width: 37px;
    height: 37px;

    font-size: 21px;

    border-radius: 10px;
  }
}

@media (max-width: 370px) {

  .login-panel {
    padding: 24px 18px;
  }

  .options {
    gap: 8px;
  }

  .options a {
    white-space: nowrap;
  }

  .mobile-brand {
    margin-bottom: 34px;
  }

}

</style>