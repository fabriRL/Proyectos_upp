<script setup>
import { useRoute } from 'vue-router'
import { ref, watch, nextTick } from 'vue'
import axios from '@/lib/axios'

const route = useRoute()

const tabs = [
  { name: 'datos', label: '1. Datos generales', icon: 'ti-file-text' },
  { name: 'cronograma', label: '2. Cronograma', icon: 'ti-calendar-event' },
  { name: 'problemas', label: '3. Problemas', icon: 'ti-alert-circle' },
  { name: 'indicadores', label: '4. Resumen', icon: 'ti-chart-dots-3' },
  { name: 'contratos', label: '6. Contratos', icon: 'ti-clipboard-list' },
  { name: 'modificaciones', label: 'Modif. Contractuales', icon: 'ti-file-diff' },
  { name: 'planillas', label: 'Planillas', icon: 'ti-receipt' },
  { name: 'decretos', label: '7. Decreto Supremo', icon: 'ti-file-certificate' },
  { name: 'financiero', label: '8. Prog. financiera', icon: 'ti-chart-bar' },
]

const nombreProyecto = ref('')
const cargandoNombre = ref(true)

async function cargarProyecto(codigo) {
  if (!codigo) return

  cargandoNombre.value = true

  try {
    const { data } = await axios.get(`/api/proyectos/${codigo}`)

    nombreProyecto.value = data?.nombre || ''
  } catch (e) {
    console.error(
      'No se pudo cargar el nombre del proyecto:',
      e
    )

    nombreProyecto.value = ''
  } finally {
    cargandoNombre.value = false
  }
}

watch(
  () => route.params.codigo,
  (nuevoCodigo) => {
    cargarProyecto(nuevoCodigo)
  },
  {
    immediate: true
  }
)

/*
 * Cuando cambia de pestaña en móvil, intentamos
 * mantener visible la pestaña activa.
 */
watch(
  () => route.name,
  async () => {
    await nextTick()

    const activeTab = document.querySelector(
      '.project-tabs a.active'
    )

    if (activeTab) {
      activeTab.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest',
        inline: 'center'
      })
    }
  }
)
</script>

<template>
  <div class="project-layout">

    <!-- =====================================================
         NAVEGACIÓN DEL PROYECTO
         ===================================================== -->

    <div class="project-nav">

      <!-- CONTEXTO DEL PROYECTO -->

      <div class="project-context">

        <span class="project-context-label">
          Proyecto activo
        </span>

        <strong
          v-if="cargandoNombre"
          class="project-context-name"
        >
          {{ route.params.codigo }} · Cargando…
        </strong>

        <strong
          v-else-if="nombreProyecto"
          class="project-context-name"
          :title="nombreProyecto"
        >
          {{ route.params.codigo }} · {{ nombreProyecto }}
        </strong>

        <strong
          v-else
          class="project-context-name"
        >
          {{ route.params.codigo }}
        </strong>

      </div>


      <!-- ===================================================
           PESTAÑAS
           =================================================== -->

      <nav
        class="project-tabs"
        aria-label="Módulos del proyecto"
      >

        <RouterLink
          v-for="tab in tabs"
          :key="tab.name"
          :to="{
            name: tab.name,
            params: {
              codigo: route.params.codigo
            }
          }"
          class="project-tab"
          :class="{
            active: route.name === tab.name
          }"
        >

          <i
            :class="`ti ${tab.icon}`"
            aria-hidden="true"
          ></i>

          <span>
            {{ tab.label }}
          </span>

        </RouterLink>

      </nav>

    </div>


    <!-- =====================================================
         CONTENIDO DE LA PÁGINA
         ===================================================== -->

    <div class="project-content">
      <RouterView />
    </div>

  </div>
</template>


<style scoped>

/* =========================================================
   CONTENEDOR PRINCIPAL
   ========================================================= */

.project-layout {
  width: 100%;
  min-width: 0;
}


/* =========================================================
   NAVEGACIÓN DEL PROYECTO
   ========================================================= */

.project-nav {
  background: #0d1f30;

  border-bottom: 1px solid #1e3a52;

  padding: 10px 20px 0;

  width: 100%;

  min-width: 0;
}


/* =========================================================
   CONTEXTO DEL PROYECTO
   ========================================================= */

.project-context {
  display: flex;

  align-items: baseline;

  gap: 8px;

  padding-bottom: 10px;

  min-width: 0;
}


.project-context-label {
  flex-shrink: 0;

  font-size: 0.65rem;

  text-transform: uppercase;

  letter-spacing: 0.06em;

  color: #00c9a7;

  font-weight: 700;
}


.project-context-name {
  color: #c8dae7;

  font-size: 0.77rem;

  font-weight: 600;

  min-width: 0;

  overflow: hidden;

  text-overflow: ellipsis;

  white-space: nowrap;
}


/* =========================================================
   PESTAÑAS
   ========================================================= */

.project-tabs {
  display: flex;

  align-items: stretch;

  gap: 2px;

  width: 100%;

  min-width: 0;

  overflow-x: auto;

  overflow-y: hidden;

  -webkit-overflow-scrolling: touch;

  scrollbar-width: none;
}


/* Ocultar scrollbar en Chrome, Edge y Safari */

.project-tabs::-webkit-scrollbar {
  display: none;
}


/* =========================================================
   PESTAÑA
   ========================================================= */

.project-tab {
  display: inline-flex;

  align-items: center;

  justify-content: center;

  flex: 0 0 auto;

  white-space: nowrap;

  min-height: 38px;

  padding: 9px 10px;

  color: #8ea9bf;

  font-size: 0.72rem;

  text-decoration: none;

  border-bottom: 2px solid transparent;

  border-radius: 4px 4px 0 0;

  transition:
    color 0.2s ease,
    background-color 0.2s ease,
    border-color 0.2s ease;

  -webkit-tap-highlight-color: transparent;
}


/* =========================================================
   ICONO
   ========================================================= */

.project-tab i {
  flex-shrink: 0;

  margin-right: 5px;

  font-size: 0.85rem;
}


/* =========================================================
   HOVER
   ========================================================= */

.project-tab:hover {
  color: #d4e4f0;

  background: rgba(0, 201, 167, 0.05);
}


/* =========================================================
   PESTAÑA ACTIVA
   ========================================================= */

.project-tab.active {
  color: #00c9a7;

  border-bottom-color: #00c9a7;

  background: rgba(0, 201, 167, 0.08);

  font-weight: 600;
}


/* =========================================================
   CONTENIDO
   ========================================================= */

.project-content {
  width: 100%;

  min-width: 0;
}


/* =========================================================
   TABLET
   ========================================================= */

@media (max-width: 768px) {

  .project-nav {
    padding-left: 14px;

    padding-right: 14px;

    padding-top: 9px;
  }


  .project-context {
    gap: 6px;

    padding-bottom: 8px;
  }


  .project-context-label {
    font-size: 0.62rem;
  }


  .project-context-name {
    font-size: 0.75rem;
  }


  .project-tab {
    min-height: 40px;

    padding: 9px 10px;

    font-size: 0.71rem;
  }

}


/* =========================================================
   MÓVIL
   ========================================================= */

@media (max-width: 640px) {

  .project-nav {
    padding-left: 10px;

    padding-right: 10px;

    padding-top: 8px;
  }


  /* -------------------------------------------------------
     PROYECTO ACTIVO
     ------------------------------------------------------- */

  .project-context {
    display: block;

    padding-bottom: 8px;

    min-width: 0;
  }


  .project-context-label {
    display: block;

    margin-bottom: 3px;

    font-size: 0.58rem;

    line-height: 1.2;
  }


  .project-context-name {
    display: block;

    width: 100%;

    font-size: 0.74rem;

    line-height: 1.3;
  }


  /* -------------------------------------------------------
     PESTAÑAS
     ------------------------------------------------------- */

  .project-tabs {
    margin-left: -2px;

    margin-right: -2px;

    width: calc(100% + 4px);

    gap: 1px;
  }


  .project-tab {
    min-height: 42px;

    padding: 10px 11px;

    font-size: 0.70rem;

    border-radius: 4px 4px 0 0;
  }


  .project-tab i {
    margin-right: 4px;

    font-size: 0.82rem;
  }

}


/* =========================================================
   MÓVIL PEQUEÑO
   ========================================================= */

@media (max-width: 400px) {

  .project-nav {
    padding-left: 8px;

    padding-right: 8px;
  }


  .project-context {
    padding-bottom: 7px;
  }


  .project-context-label {
    font-size: 0.56rem;
  }


  .project-context-name {
    font-size: 0.70rem;
  }


  .project-tab {
    min-height: 40px;

    padding-left: 9px;

    padding-right: 9px;

    font-size: 0.67rem;
  }


  .project-tab i {
    font-size: 0.78rem;
  }

}

</style>