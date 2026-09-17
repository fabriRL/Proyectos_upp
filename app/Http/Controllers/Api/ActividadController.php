<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ActividadController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LISTAR ACTIVIDADES DE UN PROYECTO
    |--------------------------------------------------------------------------
    |
    | GET /api/proyectos/{proyecto}/actividades
    |
    */
    public function index(Proyecto $proyecto)
    {
        $actividades = $proyecto->actividades()
            ->with([
                'componente',
                'predecesora',
            ])
            ->orderBy('numero')
            ->get();

        // Se recalcula el % Cumplimiento Programado en CADA lectura —
        // depende de HOY(), así que un valor guardado ayer ya no sirve
        // hoy, aunque nadie haya tocado la actividad. Se persiste el
        // valor fresco de una vez, para que otros módulos que lean
        // directo la columna (Resumen, Dashboard, Reporte General)
        // también vean un dato razonablemente actualizado.
        foreach ($actividades as $actividad) {
            $this->refrescarCumplimientoProgramado($actividad);
        }

        return response()->json($actividades);
    }


    /*
    |--------------------------------------------------------------------------
    | CREAR ACTIVIDAD
    |--------------------------------------------------------------------------
    |
    | POST /api/proyectos/{proyecto}/actividades
    |
    */
    public function store(Request $request, Proyecto $proyecto)
    {
          $totalActividades = $proyecto->actividades()->count();

            if ($totalActividades >= 15) {
                return response()->json([
                    'message' => 'Este proyecto ya alcanzó el límite de 15 actividades.',
                    'errors' => [
                        'numero' => ['No se pueden registrar más de 15 actividades por proyecto.'],
                    ],
                ], 422);
            }
        $validado = $request->validate([

            /*
            |--------------------------------------------------------------------------
            | IDENTIFICACIÓN
            |--------------------------------------------------------------------------
            */

            'numero' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('actividades', 'numero')
                    ->where(function ($query) use ($proyecto) {
                        return $query
                            ->where('id_proyecto', $proyecto->id_proyecto)
                            ->whereNull('eliminado_en');
                    }),
            ],

            'actividad' => [
                'required',
                'string',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | COMPONENTE
            |--------------------------------------------------------------------------
            */

            'id_componente' => [
                'nullable',
                'integer',
                Rule::exists('componentes_proyecto', 'id_componente')
                    ->where(function ($query) use ($proyecto) {
                        return $query->where(
                            'id_proyecto',
                            $proyecto->id_proyecto
                        );
                    }),
            ],

            /*
            |--------------------------------------------------------------------------
            | PREDECESORA
            |--------------------------------------------------------------------------
            */

            'id_actividad_predecesora' => [
                'nullable',
                'integer',
                Rule::exists('actividades', 'id_actividad')
                    ->where(function ($query) use ($proyecto) {
                        return $query
                            ->where('id_proyecto', $proyecto->id_proyecto)
                            ->whereNull('eliminado_en');
                    }),
            ],

            /*
            |--------------------------------------------------------------------------
            | FECHAS
            |--------------------------------------------------------------------------
            */

            'fecha_inicio' => [
                'required',
                'date',
            ],

            'fecha_fin' => [
                'required',
                'date',
                'after_or_equal:fecha_inicio',
            ],

            /*
            |--------------------------------------------------------------------------
            | DURACIÓN
            |--------------------------------------------------------------------------
            |
            | Aunque venga desde Vue, la calcularemos automáticamente.
            |
            */

            'duracion_dias' => [
                'nullable',
                'integer',
                'min:1',
            ],

            /*
            |--------------------------------------------------------------------------
            | ESTADO
            |--------------------------------------------------------------------------
            */

            'estado' => [
                'required',
                'string',
                'max:100',
            ],

            /*
            |--------------------------------------------------------------------------
            | AVANCE
            |--------------------------------------------------------------------------
            |
            | "porcentaje_cumplimiento_programado" YA NO se acepta del
            | formulario — se calcula solo, con fecha_inicio/fecha_fin/
            | estado. Solo queda como input real "cumplimiento_real".
            |
            */

            'porcentaje_cumplimiento_real' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | PROYECTO
        |--------------------------------------------------------------------------
        */

        $validado['id_proyecto'] = $proyecto->id_proyecto;


        /*
        |--------------------------------------------------------------------------
        | DURACIÓN AUTOMÁTICA
        |--------------------------------------------------------------------------
        |
        | Ejemplo:
        |
        | 01/08/2026 -> 05/08/2026
        |
        | = 5 días
        |
        */

        $inicio = Carbon::parse($validado['fecha_inicio']);
        $fin = Carbon::parse($validado['fecha_fin']);

        $validado['duracion_dias'] = $inicio->diffInDays($fin) + 1;


        /*
        |--------------------------------------------------------------------------
        | VALORES POR DEFECTO
        |--------------------------------------------------------------------------
        */

        $validado['porcentaje_cumplimiento_real']
            = $validado['porcentaje_cumplimiento_real'] ?? 0;


        /*
        |--------------------------------------------------------------------------
        | USUARIO CREADOR
        |--------------------------------------------------------------------------
        */

        $validado['id_usuario_creador'] =
            $request->user()->id_usuario
            ?? $request->user()->id;


        /*
        |--------------------------------------------------------------------------
        | CREAR ACTIVIDAD
        |--------------------------------------------------------------------------
        */

        $actividad = Actividad::create($validado);

        $this->refrescarCumplimientoProgramado($actividad);


        /*
        |--------------------------------------------------------------------------
        | DEVOLVER ACTIVIDAD COMPLETA
        |--------------------------------------------------------------------------
        */

        $actividad->load([
            'componente',
            'predecesora',
        ]);

        return response()->json($actividad, 201);
    }


    /*
    |--------------------------------------------------------------------------
    | MOSTRAR UNA ACTIVIDAD
    |--------------------------------------------------------------------------
    |
    | GET /api/actividades/{actividad}
    |
    */
    public function show(Actividad $actividad)
    {
        $this->refrescarCumplimientoProgramado($actividad);

        $actividad->load([
            'proyecto',
            'componente',
            'predecesora',
            'sucesoras',
        ]);

        return response()->json($actividad);
    }


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR ACTIVIDAD
    |--------------------------------------------------------------------------
    |
    | PUT/PATCH /api/actividades/{actividad}
    |
    */
    public function update(Request $request, Actividad $actividad)
    {
        $validado = $request->validate([

            /*
            |--------------------------------------------------------------------------
            | NÚMERO
            |--------------------------------------------------------------------------
            */

            'numero' => [
                'sometimes',
                'required',
                'integer',
                'min:1',

                Rule::unique('actividades', 'numero')
                    ->where(function ($query) use ($actividad) {
                        return $query
                            ->where(
                                'id_proyecto',
                                $actividad->id_proyecto
                            )
                            ->whereNull('eliminado_en');
                    })
                    ->ignore(
                        $actividad->id_actividad,
                        'id_actividad'
                    ),
            ],

            /*
            |--------------------------------------------------------------------------
            | ACTIVIDAD
            |--------------------------------------------------------------------------
            */

            'actividad' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | COMPONENTE
            |--------------------------------------------------------------------------
            */

            'id_componente' => [
                'nullable',
                'integer',

                Rule::exists(
                    'componentes_proyecto',
                    'id_componente'
                )->where(function ($query) use ($actividad) {
                    return $query->where(
                        'id_proyecto',
                        $actividad->id_proyecto
                    );
                }),
            ],

            /*
            |--------------------------------------------------------------------------
            | PREDECESORA
            |--------------------------------------------------------------------------
            */

            'id_actividad_predecesora' => [
                'nullable',
                'integer',

                Rule::exists(
                    'actividades',
                    'id_actividad'
                )->where(function ($query) use ($actividad) {
                    return $query
                        ->where(
                            'id_proyecto',
                            $actividad->id_proyecto
                        )
                        ->whereNull('eliminado_en');
                }),
            ],

            /*
            |--------------------------------------------------------------------------
            | FECHAS
            |--------------------------------------------------------------------------
            */

            'fecha_inicio' => [
                'sometimes',
                'required',
                'date',
            ],

            'fecha_fin' => [
                'sometimes',
                'required',
                'date',
            ],

            /*
            |--------------------------------------------------------------------------
            | DURACIÓN
            |--------------------------------------------------------------------------
            */

            'duracion_dias' => [
                'nullable',
                'integer',
                'min:1',
            ],

            /*
            |--------------------------------------------------------------------------
            | ESTADO
            |--------------------------------------------------------------------------
            */

            'estado' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            /*
            |--------------------------------------------------------------------------
            | AVANCE
            |--------------------------------------------------------------------------
            |
            | "porcentaje_cumplimiento_programado" ya no se acepta aquí
            | tampoco — se recalcula siempre después de guardar.
            |
            */

            'porcentaje_cumplimiento_real' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | VALIDAR FECHAS FINALES
        |--------------------------------------------------------------------------
        |
        | Si solo se modifica una de las fechas, tomamos la otra
        | que ya existe en la actividad.
        |
        */

        $fechaInicio = isset($validado['fecha_inicio'])
            ? Carbon::parse($validado['fecha_inicio'])
            : Carbon::parse($actividad->fecha_inicio);

        $fechaFin = isset($validado['fecha_fin'])
            ? Carbon::parse($validado['fecha_fin'])
            : Carbon::parse($actividad->fecha_fin);


        if ($fechaFin->lt($fechaInicio)) {
            return response()->json([
                'message' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
                'errors' => [
                    'fecha_fin' => [
                        'La fecha de fin no puede ser anterior a la fecha de inicio.'
                    ]
                ]
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | RECALCULAR DURACIÓN
        |--------------------------------------------------------------------------
        */

        $validado['duracion_dias'] =
            $fechaInicio->diffInDays($fechaFin) + 1;


        /*
        |--------------------------------------------------------------------------
        | USUARIO ACTUALIZADOR
        |--------------------------------------------------------------------------
        */

        $validado['id_usuario_actualizador'] =
            $request->user()->id_usuario
            ?? $request->user()->id;


        /*
        |--------------------------------------------------------------------------
        | ACTUALIZAR
        |--------------------------------------------------------------------------
        */

        $actividad->update($validado);

        $this->refrescarCumplimientoProgramado($actividad);


        $actividad->load([
            'proyecto',
            'componente',
            'predecesora',
            'sucesoras',
        ]);


        return response()->json($actividad);
    }

    public function destroy(Actividad $actividad)
    {
        $actividad->delete();

        return response()->json([
            'message' => 'Actividad eliminada correctamente.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | % CUMPLIMIENTO PROGRAMADO — dato calculado, no editable a mano
    |--------------------------------------------------------------------------
    |
    | Traducción exacta de la fórmula de Excel:
    | =SI(O(T="";U="");"";SI(U<T;"";SI(O(W="Concluida";W="Cancelada");1;
    |   SI(HOY()<T;0;MIN((HOY()-T+1)/(U-T+1);1)))))
    |
    | T = fecha_inicio, U = fecha_fin, W = estado.
    |
    | Se recalcula en CADA lectura (index/show) y después de cada
    | escritura (store/update) — depende de HOY(), así que un valor
    | guardado ayer no sirve hoy, igual que en Excel al reabrir el
    | archivo.
    |
    */
    private function calcularCumplimientoProgramado(Actividad $actividad): ?float
    {
        if (empty($actividad->fecha_inicio) || empty($actividad->fecha_fin)) {
            return null;
        }

        $inicio = Carbon::parse($actividad->fecha_inicio)->startOfDay();
        $fin = Carbon::parse($actividad->fecha_fin)->startOfDay();

        if ($fin->lt($inicio)) {
            return null;
        }

        if (in_array($actividad->estado, ['Concluida', 'Cancelada'], true)) {
            return 100.0;
        }

        $hoy = Carbon::now()->startOfDay();

        if ($hoy->lt($inicio)) {
            return 0.0;
        }

        $duracionTotal = $inicio->diffInDays($fin) + 1;
        $diasTranscurridos = $inicio->diffInDays($hoy) + 1;

        $porcentaje = min($diasTranscurridos / $duracionTotal, 1) * 100;

        return round($porcentaje, 2);
    }

    private function refrescarCumplimientoProgramado(Actividad $actividad): void
    {
        $nuevoValor = $this->calcularCumplimientoProgramado($actividad);

        if ((float) $actividad->porcentaje_cumplimiento_programado !== (float) ($nuevoValor ?? 0)) {
            $actividad->porcentaje_cumplimiento_programado = $nuevoValor;
            $actividad->saveQuietly(); // no dispara updated_at/eventos extra
        }
    }
}