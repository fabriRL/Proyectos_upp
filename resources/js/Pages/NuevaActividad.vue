<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
    proyecto: {
        type: Object,
        required: true
    }
})

const emit = defineEmits([
    'creada',
    'cancelar'
])

/*
|--------------------------------------------------------------------------
| Estado
|--------------------------------------------------------------------------
*/

const loading = ref(false)
const loadingDatos = ref(true)
const error = ref(null)
const errores = ref({})

/*
|--------------------------------------------------------------------------
| Datos auxiliares
|--------------------------------------------------------------------------
*/

const componentes = ref([])
const actividades = ref([])

/*
|--------------------------------------------------------------------------
| Formulario
|--------------------------------------------------------------------------
|
| "porcentaje_cumplimiento_programado" ya no vive aquí: se calcula solo
| en el backend a partir de fecha_inicio/fecha_fin/estado (igual que
| Excel), así que no tiene sentido pedirlo a mano en el formulario.
|
*/

const form = ref({
    id_componente: '',
    id_actividad_predecesora: '',
    numero: '',
    actividad: '',
    fecha_inicio: '',
    fecha_fin: '',
    duracion_dias: '',
    estado: 'Pendiente',
    porcentaje_cumplimiento_real: 0
})


/*
|--------------------------------------------------------------------------
| ID del proyecto
|--------------------------------------------------------------------------
*/

const proyectoId = computed(() => {
    return props.proyecto?.id_proyecto
})


/*
|--------------------------------------------------------------------------
| Cargar componentes
|--------------------------------------------------------------------------
*/

async function cargarComponentes() {
    const response = await axios.get(
        `/api/proyectos/${proyectoId.value}/componentes`
    )

    componentes.value = response.data
}


/*
|--------------------------------------------------------------------------
| Cargar actividades existentes
|--------------------------------------------------------------------------
*/

async function cargarActividades() {
    const response = await axios.get(
        `/api/proyectos/${proyectoId.value}/actividades`
    )

    actividades.value = response.data
}


/*
|--------------------------------------------------------------------------
| Número automático
|--------------------------------------------------------------------------
*/

const siguienteNumero = computed(() => {

    if (!actividades.value.length) {
        return 1
    }

    const numeros = actividades.value
        .map(a => Number(a.numero))
        .filter(n => !isNaN(n))

    if (!numeros.length) {
        return 1
    }

    return Math.max(...numeros) + 1
})


/*
|--------------------------------------------------------------------------
| Cargar datos iniciales
|--------------------------------------------------------------------------
*/

async function cargarDatos() {

    loadingDatos.value = true
    error.value = null

    try {

        await Promise.all([
            cargarComponentes(),
            cargarActividades()
        ])

        form.value.numero = siguienteNumero.value

    } catch (e) {

        console.error(e)

        error.value =
            'No se pudieron cargar los datos del proyecto.'

    } finally {

        loadingDatos.value = false

    }
}

onMounted(cargarDatos)


/*
|--------------------------------------------------------------------------
| Calcular duración — campo bloqueado, solo lectura
|--------------------------------------------------------------------------
|
| Se recalcula automáticamente cada vez que cambia fecha_inicio o
| fecha_fin. El input está deshabilitado en el template para que
| nadie pueda escribir un valor manual que no coincida con las fechas.
|
*/

function calcularDuracion() {

    if (
        !form.value.fecha_inicio ||
        !form.value.fecha_fin
    ) {
        form.value.duracion_dias = ''
        return
    }

    const inicio = new Date(
        `${form.value.fecha_inicio}T00:00:00`
    )

    const fin = new Date(
        `${form.value.fecha_fin}T00:00:00`
    )

    const diferencia =
        Math.round(
            (fin - inicio) / 86400000
        ) + 1

    if (diferencia > 0) {

        form.value.duracion_dias =
            diferencia

    } else {

        form.value.duracion_dias = ''

    }
}


/*
|--------------------------------------------------------------------------
| Validación
|--------------------------------------------------------------------------
*/

function validarFormulario() {

    errores.value = {}

    if (!form.value.actividad?.trim()) {

        errores.value.actividad =
            'El nombre de la actividad es obligatorio.'

    }

    if (!form.value.fecha_inicio) {

        errores.value.fecha_inicio =
            'La fecha de inicio es obligatoria.'

    }

    if (!form.value.fecha_fin) {

        errores.value.fecha_fin =
            'La fecha de fin es obligatoria.'

    }

    if (
        form.value.fecha_inicio &&
        form.value.fecha_fin &&
        form.value.fecha_fin <
        form.value.fecha_inicio
    ) {

        errores.value.fecha_fin =
            'La fecha de fin debe ser posterior o igual a la fecha de inicio.'

    }

    return Object.keys(errores.value).length === 0
}


/*
|--------------------------------------------------------------------------
| Guardar actividad
|--------------------------------------------------------------------------
*/

async function guardar() {

    if (!validarFormulario()) {
        return
    }

    loading.value = true
    error.value = null

    try {

        const payload = {

            id_componente:
                form.value.id_componente || null,

            id_actividad_predecesora:
                form.value.id_actividad_predecesora || null,

            numero:
                Number(form.value.numero),

            actividad:
                form.value.actividad.trim(),

            fecha_inicio:
                form.value.fecha_inicio,

            fecha_fin:
                form.value.fecha_fin,

            duracion_dias:
                form.value.duracion_dias
                    ? Number(form.value.duracion_dias)
                    : null,

            estado:
                form.value.estado,

            porcentaje_cumplimiento_real:
                Number(
                    form.value
                        .porcentaje_cumplimiento_real
                )
        }


        const response = await axios.post(
            `/api/proyectos/${proyectoId.value}/actividades`,
            payload
        )


        /*
        |--------------------------------------------------------------------------
        | Avisar al padre
        |--------------------------------------------------------------------------
        */

        emit('creada', response.data)


        /*
        |--------------------------------------------------------------------------
        | Limpiar formulario
        |--------------------------------------------------------------------------
        */

        form.value = {

            id_componente: '',
            id_actividad_predecesora: '',
            numero: siguienteNumero.value + 1,
            actividad: '',
            fecha_inicio: '',
            fecha_fin: '',
            duracion_dias: '',
            estado: 'Pendiente',
            porcentaje_cumplimiento_real: 0

        }

    } catch (e) {

        console.error(e)

        if (e.response?.status === 422) {

            errores.value =
                e.response.data.errors ?? {}

            error.value =
                'Revisa los campos del formulario.'

        } else {

            error.value =
                'No se pudo registrar la actividad.'

        }

    } finally {

        loading.value = false

    }
}


/*
|--------------------------------------------------------------------------
| Actividades disponibles como predecesoras
|--------------------------------------------------------------------------
*/

const actividadesPredecesoras = computed(() => {

    return actividades.value
        .filter(a =>
            Number(a.numero) <
            Number(form.value.numero)
        )
        .sort(
            (a, b) =>
                Number(a.numero) -
                Number(b.numero)
        )

})
</script>


<template>

    <div class="actividad-form">

        <!-- CABECERA -->

        <div class="form-header">

            <div>

                <h2>Nueva actividad</h2>

                <p>
                    Registra una nueva actividad para el
                    cronograma del proyecto.
                </p>

            </div>

            <button
                type="button"
                class="btn-close"
                @click="$emit('cancelar')"
            >
                ×
            </button>

        </div>


        <!-- CARGANDO -->

        <div
            v-if="loadingDatos"
            class="form-state"
        >

            <i class="ti ti-loader-2 spin"></i>

            Cargando información del proyecto...

        </div>


        <template v-else>

            <!-- ERROR -->

            <div
                v-if="error"
                class="alert-error"
            >

                <i class="ti ti-alert-triangle"></i>

                {{ error }}

            </div>


            <!-- FORMULARIO -->

            <form @submit.prevent="guardar">


                <!-- INFORMACIÓN GENERAL -->

                <div class="form-section">

                    <div class="section-title">

                        <i class="ti ti-list"></i>

                        Información de la actividad

                    </div>


                    <div class="form-grid">


                        <!-- NÚMERO -->

                        <div class="form-group">

                            <label>
                                N° actividad
                            </label>

                            <input
                                v-model="form.numero"
                                type="number"
                                min="1"
                                required
                            />

                        </div>


                        <!-- COMPONENTE -->

                        <div class="form-group">

                            <label>
                                Componente
                            </label>

                            <select
                                v-model="form.id_componente"
                            >

                                <option value="">
                                    Sin componente
                                </option>

                                <option
                                    v-for="componente in componentes"
                                    :key="componente.id_componente"
                                    :value="componente.id_componente"
                                >

                                    {{ componente.orden }}.
                                    {{ componente.nombre }}

                                </option>

                            </select>

                        </div>


                        <!-- ACTIVIDAD -->

                        <div class="form-group full">

                            <label>
                                Actividad
                                <span>*</span>
                            </label>

                            <input
                                v-model="form.actividad"
                                type="text"
                                maxlength="255"
                                placeholder="Ej. Desarrollo del módulo de usuarios"
                                required
                            />

                            <small
                                v-if="errores.actividad"
                                class="field-error"
                            >
                                {{ errores.actividad[0] ?? errores.actividad }}
                            </small>

                        </div>


                        <!-- PREDECESORA -->

                        <div class="form-group">

                            <label>
                                Actividad predecesora
                            </label>

                            <select
                                v-model="form.id_actividad_predecesora"
                            >

                                <option value="">
                                    Ninguna
                                </option>

                                <option
                                    v-for="actividad in actividadesPredecesoras"
                                    :key="actividad.id_actividad"
                                    :value="actividad.id_actividad"
                                >

                                    {{ actividad.numero }}.
                                    {{ actividad.actividad }}

                                </option>

                            </select>

                            <small>
                                Actividad que debe completarse
                                antes de comenzar esta.
                            </small>

                        </div>


                        <!-- ESTADO -->

                        <div class="form-group">

                            <label>
                                Estado
                                <span>*</span>
                            </label>

                            <select
                                v-model="form.estado"
                                required
                            >

                                <option value="Pendiente">
                                    Pendiente
                                </option>

                                <option value="En curso">
                                    En curso
                                </option>

                                <option value="Concluida">
                                    Concluida
                                </option>

                                <option value="Retrasada">
                                    Retrasada
                                </option>

                            </select>

                        </div>

                    </div>

                </div>


                <!-- FECHAS -->

                <div class="form-section">

                    <div class="section-title">

                        <i class="ti ti-calendar"></i>

                        Programación

                    </div>


                    <div class="form-grid">


                        <!-- INICIO -->

                        <div class="form-group">

                            <label>
                                Fecha de inicio
                                <span>*</span>
                            </label>

                            <input
                                v-model="form.fecha_inicio"
                                @change="calcularDuracion"
                                type="date"
                                required
                            />

                            <small
                                v-if="errores.fecha_inicio"
                                class="field-error"
                            >
                                {{ errores.fecha_inicio[0] ?? errores.fecha_inicio }}
                            </small>

                        </div>


                        <!-- FIN -->

                        <div class="form-group">

                            <label>
                                Fecha de finalización
                                <span>*</span>
                            </label>

                            <input
                                v-model="form.fecha_fin"
                                @change="calcularDuracion"
                                type="date"
                                required
                            />

                            <small
                                v-if="errores.fecha_fin"
                                class="field-error"
                            >
                                {{ errores.fecha_fin[0] ?? errores.fecha_fin }}
                            </small>

                        </div>


                        <!-- DURACIÓN (calculada, bloqueada) -->

                        <div class="form-group">

                            <label>
                                Duración
                                <small class="label-hint">(calculada)</small>
                            </label>

                            <div class="input-suffix">

                                <input
                                    v-model="form.duracion_dias"
                                    type="number"
                                    disabled
                                    readonly
                                    tabindex="-1"
                                    title="Se calcula automáticamente a partir de las fechas de inicio y fin."
                                />

                                <span>
                                    días
                                </span>

                            </div>

                            <small>
                                Se calcula sola con las fechas de inicio y fin.
                            </small>

                        </div>

                    </div>

                </div>


                <!-- AVANCE -->

                <div class="form-section">

                    <div class="section-title">

                        <i class="ti ti-chart-line"></i>

                        Avance

                    </div>


                    <div class="form-grid form-grid-single">


                        <!-- REAL -->

                        <div class="form-group">

                            <label>
                                Cumplimiento real
                            </label>

                            <div class="range-row">

                                <input
                                    v-model.number="
                                        form.porcentaje_cumplimiento_real
                                    "
                                    type="range"
                                    min="0"
                                    max="100"
                                    step="1"
                                />

                                <strong>
                                    {{ form.porcentaje_cumplimiento_real }}%
                                </strong>

                            </div>

                            <small>
                                El % programado se calcula automáticamente
                                según las fechas y el estado de la actividad.
                            </small>

                        </div>

                    </div>

                </div>


                <!-- BOTONES -->

                <div class="form-actions">

                    <button
                        type="button"
                        class="btn-secondary"
                        @click="$emit('cancelar')"
                    >

                        Cancelar

                    </button>


                    <button
                        type="submit"
                        class="btn-primary"
                        :disabled="loading"
                    >

                        <i
                            v-if="loading"
                            class="ti ti-loader-2 spin"
                        ></i>

                        <i
                            v-else
                            class="ti ti-device-floppy"
                        ></i>

                        {{ loading
                            ? 'Guardando...'
                            : 'Guardar actividad'
                        }}

                    </button>

                </div>

            </form>

        </template>

    </div>

</template>


<style scoped>

.actividad-form {
    background: #0d1f30;
    border: 1px solid #1e3a52;
    border-radius: 10px;
    color: #c8dae7;
    overflow: hidden;
}

.form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #1e3a52;
}

.form-header h2 {
    margin: 0;
    color: #d4e4f0;
    font-size: 1.15rem;
}

.form-header p {
    margin: 5px 0 0;
    color: #8ea9bf;
    font-size: .78rem;
}

.btn-close {
    border: 0;
    background: transparent;
    color: #8ea9bf;
    font-size: 25px;
    cursor: pointer;
}

.form-section {
    padding: 20px 24px;
    border-bottom: 1px solid #19354d;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 17px;
    color: #d4e4f0;
    font-weight: 700;
    font-size: .82rem;
}

.section-title i {
    color: #00c9a7;
    font-size: 18px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.form-grid-single {
    grid-template-columns: 1fr;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group.full {
    grid-column: 1 / -1;
}

.form-group label {
    display: flex;
    align-items: baseline;
    gap: 6px;
    color: #b4c9d9;
    font-size: .72rem;
    font-weight: 700;
}

.form-group label span {
    color: #f28b82;
}

.label-hint {
    color: #5f7c91;
    font-size: .64rem;
    font-weight: 600;
}

.form-group input,
.form-group select {
    width: 100%;
    box-sizing: border-box;
    padding: 10px 12px;
    border-radius: 6px;
    border: 1px solid #29465e;
    background: #091a29;
    color: #d4e4f0;
    outline: none;
    font-size: .78rem;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #00c9a7;
}

.form-group input:disabled {
    background: #0c2334;
    color: #7893a7;
    cursor: not-allowed;
    opacity: .85;
}

.form-group small {
    color: #718da3;
    font-size: .67rem;
}

.field-error {
    color: #f28b82 !important;
}

.input-suffix {
    display: flex;
    align-items: center;
}

.input-suffix input {
    border-radius: 6px 0 0 6px;
}

.input-suffix input:disabled {
    border-right: 0;
}

.input-suffix span {
    padding: 10px 12px;
    background: #122b40;
    border: 1px solid #29465e;
    border-left: 0;
    border-radius: 0 6px 6px 0;
    color: #8ea9bf;
    font-size: .75rem;
}

.range-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

.range-row input[type="range"] {
    flex: 1;
    padding: 0;
}

.range-row strong {
    min-width: 42px;
    color: #00c9a7;
    font-size: .8rem;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 18px 24px;
}

.btn-primary,
.btn-secondary {
    border: 0;
    border-radius: 6px;
    padding: 10px 17px;
    font-size: .76rem;
    font-weight: 700;
    cursor: pointer;
}

.btn-primary {
    display: flex;
    align-items: center;
    gap: 7px;
    background: #00c9a7;
    color: #061820;
}

.btn-primary:disabled {
    opacity: .6;
    cursor: not-allowed;
}

.btn-secondary {
    background: #172d40;
    color: #a9bfd0;
    border: 1px solid #29465e;
}

.form-state {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    padding: 50px;
    color: #8ea9bf;
}

.alert-error {
    margin: 18px 24px 0;
    padding: 11px 13px;
    border-radius: 6px;
    background: rgba(242, 139, 130, .1);
    border: 1px solid rgba(242, 139, 130, .25);
    color: #f28b82;
    font-size: .76rem;
}

.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 700px) {

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-group.full {
        grid-column: auto;
    }

    .form-header,
    .form-section,
    .form-actions {
        padding-left: 16px;
        padding-right: 16px;
    }

}

</style>