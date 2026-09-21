<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import { useToast } from '@/composables/useToast.js'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },

    proyectoId: {
        type: [Number, String],
        required: true
    },

    numeroInicial: {
        type: Number,
        default: 1
    }
})

const emit = defineEmits([
    'update:modelValue',
    'guardado'
])

const { showToast } = useToast()

const guardando = ref(false)
const errorFormulario = ref(null)

/*
| "porcentaje_cumplimiento_programado" no se pide aquí: se calcula solo
| en el backend a partir de fecha_inicio/fecha_fin/estado.
*/
const nuevaActividad = ref({
    numero: 1,
    actividad: '',
    fecha_inicio: '',
    fecha_fin: '',
    estado: 'Pendiente',
    porcentaje_cumplimiento_real: 0
})

function resetearFormulario() {
    errorFormulario.value = null

    nuevaActividad.value = {
        numero: props.numeroInicial,
        actividad: '',
        fecha_inicio: '',
        fecha_fin: '',
        estado: 'Pendiente',
        porcentaje_cumplimiento_real: 0
    }
}

function cerrarModal() {
    if (guardando.value) return

    emit('update:modelValue', false)
    errorFormulario.value = null
}

async function guardarActividad() {

    errorFormulario.value = null

    if (!nuevaActividad.value.numero) {
        errorFormulario.value =
            'El número de actividad es obligatorio.'
        return
    }

    if (!nuevaActividad.value.actividad.trim()) {
        errorFormulario.value =
            'Debes escribir el nombre de la actividad.'
        return
    }

    if (!nuevaActividad.value.fecha_inicio) {
        errorFormulario.value =
            'Debes seleccionar la fecha de inicio.'
        return
    }

    if (!nuevaActividad.value.fecha_fin) {
        errorFormulario.value =
            'Debes seleccionar la fecha de finalización.'
        return
    }

    if (
        nuevaActividad.value.fecha_fin <
        nuevaActividad.value.fecha_inicio
    ) {
        errorFormulario.value =
            'La fecha de finalización no puede ser anterior a la fecha de inicio.'
        return
    }

    guardando.value = true

    try {

        const inicio = new Date(
            `${nuevaActividad.value.fecha_inicio}T00:00:00`
        )

        const fin = new Date(
            `${nuevaActividad.value.fecha_fin}T00:00:00`
        )

        const duracionDias =
            Math.floor((fin - inicio) / 86400000) + 1

        const payload = {
            numero: Number(nuevaActividad.value.numero),
            actividad: nuevaActividad.value.actividad.trim(),
            fecha_inicio: nuevaActividad.value.fecha_inicio,
            fecha_fin: nuevaActividad.value.fecha_fin,
            duracion_dias: duracionDias,
            estado: nuevaActividad.value.estado,
            porcentaje_cumplimiento_real:
                Number(
                    nuevaActividad.value
                        .porcentaje_cumplimiento_real || 0
                )
        }

        await axios.post(
            `/api/proyectos/${props.proyectoId}/actividades`,
            payload
        )

        showToast('Actividad registrada correctamente.', 'success')

        cerrarModal()

        emit('guardado')

    } catch (e) {

        console.error(e)

        if (e.response?.status === 422) {

            const errores = e.response.data.errors

            if (errores) {
                errorFormulario.value =
                    Object.values(errores)
                        .flat()
                        .join(' ')
            } else {
                errorFormulario.value =
                    e.response.data.message ??
                    'Los datos enviados no son válidos.'
            }

        } else {

            errorFormulario.value =
                e.response?.data?.message ??
                'No se pudo registrar la actividad.'

            showToast('No se pudo registrar la actividad.', 'error')
        }

    } finally {
        guardando.value = false
    }
}

watch(
    () => props.modelValue,
    abierto => {
        if (abierto) {
            resetearFormulario()
        }
    }
)
</script>

<template>

    <div
        v-if="modelValue"
        class="modal-overlay"
        @click.self="cerrarModal"
    >

        <div class="modal-actividad">

            <div class="modal-header">

                <div>

                    <h3>
                        <i class="ti ti-plus"></i>
                        Nueva actividad
                    </h3>

                    <p>
                        Agrega una actividad al cronograma del proyecto.
                    </p>

                </div>

                <button
                    type="button"
                    class="modal-close"
                    @click="cerrarModal"
                >
                    <i class="ti ti-x"></i>
                </button>

            </div>

            <form
                class="form-actividad"
                @submit.prevent="guardarActividad"
            >

                <div
                    v-if="errorFormulario"
                    class="form-error"
                >
                    <i class="ti ti-alert-circle"></i>

                    {{ errorFormulario }}
                </div>

                <div class="form-grid">

                    <div class="form-group">

                        <label>
                            N° de actividad
                        </label>

                        <input
                            v-model.number="nuevaActividad.numero"
                            type="number"
                            min="1"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label>
                            Estado
                        </label>

                        <select
                            v-model="nuevaActividad.estado"
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

                    <div class="form-group form-full">

                        <label>
                            Actividad
                        </label>

                        <input
                            v-model="nuevaActividad.actividad"
                            type="text"
                            maxlength="255"
                            placeholder="Ej. Excavación y preparación del terreno"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label>
                            Fecha de inicio
                        </label>

                        <input
                            v-model="nuevaActividad.fecha_inicio"
                            type="date"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label>
                            Fecha de finalización
                        </label>

                        <input
                            v-model="nuevaActividad.fecha_fin"
                            type="date"
                            :min="nuevaActividad.fecha_inicio"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label>
                            Cumplimiento real (%)
                        </label>

                        <input
                            v-model.number="
                                nuevaActividad
                                    .porcentaje_cumplimiento_real
                            "
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                        >

                    </div>

                </div>

                <div class="modal-actions">

                    <button
                        type="button"
                        class="btn-cancelar"
                        @click="cerrarModal"
                        :disabled="guardando"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="btn-guardar"
                        :disabled="guardando"
                    >

                        <i
                            v-if="guardando"
                            class="ti ti-loader-2 spin"
                        ></i>

                        <i
                            v-else
                            class="ti ti-device-floppy"
                        ></i>

                        {{
                            guardando
                                ? 'Guardando...'
                                : 'Guardar actividad'
                        }}

                    </button>

                </div>

            </form>

        </div>

    </div>

</template>

<style scoped>
/* AQUÍ VAMOS A MOVER EL CSS DEL MODAL */
</style>