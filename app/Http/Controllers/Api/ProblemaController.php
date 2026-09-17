<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\Problema;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProblemaController extends Controller
{
    // GET /api/proyectos/{proyecto}/problemas
    public function index(Proyecto $proyecto)
    {
        $problemas = $proyecto->problemas()
            ->with('actividad:id_actividad,numero,actividad')
            ->orderBy('fecha_registro', 'desc')
            ->get();

        return response()->json($problemas);
    }

    // POST /api/proyectos/{proyecto}/problemas
    public function store(Request $request, Proyecto $proyecto)
    {
        $validado = $request->validate([
            'fecha_registro' => 'required|date',
            'problema_identificado' => 'required|string',
            'impacto' => 'nullable|string|max:50',
            'solucion_propuesta' => 'nullable|string',
            'responsable' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:50',
            'fecha_cierre' => 'nullable|date|after_or_equal:fecha_registro',
            'archivo_resolucion' => 'nullable|file|mimes:pdf|max:10240',
            'id_actividad' => [
                'nullable',
                'integer',
                Rule::exists('actividades', 'id_actividad')
                    ->where(fn ($q) => $q->where('id_proyecto', $proyecto->id_proyecto)),
            ],
        ]);

        // Ambas validaciones de "Resuelto" van ANTES de tocar la base de
        // datos: si el estado final es Resuelto, exige PDF y fecha de
        // cierre; si falta cualquiera de los dos, no se crea el registro.
        if (($validado['estado'] ?? null) === 'Resuelto' && !$request->hasFile('archivo_resolucion')) {
            return response()->json([
                'message' => 'No se puede marcar como Resuelto sin adjuntar el documento de resolución.',
                'errors' => [
                    'estado' => ['Debes adjuntar el PDF de resolución antes de marcar este problema como Resuelto.'],
                ],
            ], 422);
        }

        if (($validado['estado'] ?? null) === 'Resuelto' && empty($validado['fecha_cierre'] ?? null)) {
            return response()->json([
                'message' => 'No se puede marcar como Resuelto sin indicar la fecha de cierre.',
                'errors' => [
                    'fecha_cierre' => ['Debes indicar la fecha de cierre antes de marcar este problema como Resuelto.'],
                ],
            ], 422);
        }

        if ($request->hasFile('archivo_resolucion')) {
            $archivo = $request->file('archivo_resolucion');
            $validado['archivo_resolucion_path'] = $archivo->store('problemas/resolucion', 'public');
            $validado['archivo_resolucion_nombre_original'] = $archivo->getClientOriginalName();
        }

        $validado['id_proyecto'] = $proyecto->id_proyecto;
        $validado['id_usuario_creador'] = $request->user()->id_usuario ?? $request->user()->id;

        $problema = Problema::create($validado);

        $this->sincronizarEstadoActividad($problema->id_actividad);

        return response()->json($problema->load('actividad:id_actividad,numero,actividad'), 201);
    }

    // PUT/PATCH /api/problemas/{problema}
    public function update(Request $request, Problema $problema)
    {
        $validado = $request->validate([
            'fecha_registro' => 'sometimes|required|date',
            'problema_identificado' => 'sometimes|required|string',
            'impacto' => 'nullable|string|max:50',
            'solucion_propuesta' => 'nullable|string',
            'responsable' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:50',
            'fecha_cierre' => 'nullable|date',
            'archivo_resolucion' => 'nullable|file|mimes:pdf|max:10240',
            'id_actividad' => [
                'nullable',
                'integer',
                Rule::exists('actividades', 'id_actividad')
                    ->where(fn ($q) => $q->where('id_proyecto', $problema->id_proyecto)),
            ],
        ]);

        $estadoFinal = $validado['estado'] ?? $problema->estado;
        $tendraArchivo = $problema->archivo_resolucion_path || $request->hasFile('archivo_resolucion');
        $tendraFechaCierre = $validado['fecha_cierre'] ?? $problema->fecha_cierre;

        // Igual que en store(): ambas validaciones van ANTES de
        // $problema->update(), para no dejar el registro modificado si
        // falta el PDF o la fecha de cierre.
        if ($estadoFinal === 'Resuelto' && !$tendraArchivo) {
            return response()->json([
                'message' => 'No se puede marcar como Resuelto sin adjuntar el documento de resolución.',
                'errors' => [
                    'estado' => ['Debes adjuntar el PDF de resolución antes de marcar este problema como Resuelto.'],
                ],
            ], 422);
        }

        if ($estadoFinal === 'Resuelto' && empty($tendraFechaCierre)) {
            return response()->json([
                'message' => 'No se puede marcar como Resuelto sin indicar la fecha de cierre.',
                'errors' => [
                    'fecha_cierre' => ['Debes indicar la fecha de cierre antes de marcar este problema como Resuelto.'],
                ],
            ], 422);
        }

        if ($request->hasFile('archivo_resolucion')) {
            if ($problema->archivo_resolucion_path) {
                Storage::disk('public')->delete($problema->archivo_resolucion_path);
            }
            $archivo = $request->file('archivo_resolucion');
            $validado['archivo_resolucion_path'] = $archivo->store('problemas/resolucion', 'public');
            $validado['archivo_resolucion_nombre_original'] = $archivo->getClientOriginalName();
        }

        $validado['id_usuario_actualizador'] = $request->user()->id_usuario ?? $request->user()->id;

        // Guarda la actividad de ANTES del update, por si el problema se
        // reasignó a otra actividad distinta — hay que resincronizar ambas.
        $idActividadAnterior = $problema->id_actividad;

        $problema->update($validado);

        $this->sincronizarEstadoActividad($idActividadAnterior);
        if ($problema->id_actividad !== $idActividadAnterior) {
            $this->sincronizarEstadoActividad($problema->id_actividad);
        }

        return response()->json($problema->load('actividad:id_actividad,numero,actividad'));
    }

    // DELETE /api/problemas/{problema}
    public function destroy(Problema $problema)
    {
        if ($problema->archivo_resolucion_path) {
            Storage::disk('public')->delete($problema->archivo_resolucion_path);
        }

        $idActividad = $problema->id_actividad;

        $problema->delete();

        $this->sincronizarEstadoActividad($idActividad);

        return response()->json(null, 204);
    }

    // Recalcula desde cero el Estado de una actividad, según si TODAVÍA
    // tiene algún problema abierto (estado != 'Resuelto') apuntándole.
    // Se recalcula completo cada vez (no incrementalmente) para que nunca
    // se desincronice, sin importar el orden en que se creen/resuelvan/
    // borren los problemas que la afectan — mismo criterio que ya usamos
    // en recalcularContrato() de Planillas.
    private function sincronizarEstadoActividad(?int $idActividad): void
    {
        if (!$idActividad) {
            return;
        }

        $actividad = Actividad::find($idActividad);
        if (!$actividad) {
            return;
        }

        $tieneProblemasAbiertos = Problema::where('id_actividad', $idActividad)
            ->where('estado', '!=', 'Resuelto')
            ->exists();

        if ($tieneProblemasAbiertos) {
            // Solo se guarda el estado previo la PRIMERA vez que se fuerza
            // a "Retrasada" — si ya estaba retrasada por otro problema, no
            // se sobreescribe el valor guardado.
            if ($actividad->estado !== 'Retrasada') {
                $actividad->update([
                    'estado_previo_retraso' => $actividad->estado,
                    'estado' => 'Retrasada',
                ]);
            }
        } else {
            // Ya no queda ningún problema abierto afectándola — se
            // devuelve al estado que tenía antes de forzarla.
            if ($actividad->estado === 'Retrasada' && $actividad->estado_previo_retraso) {
                $actividad->update([
                    'estado' => $actividad->estado_previo_retraso,
                    'estado_previo_retraso' => null,
                ]);
            }
        }
    }
}