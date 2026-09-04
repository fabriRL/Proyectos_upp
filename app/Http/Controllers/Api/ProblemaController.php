<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Problema;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProblemaController extends Controller
{
    // GET /api/proyectos/{proyecto}/problemas
    public function index(Proyecto $proyecto)
    {
        $problemas = $proyecto->problemas()
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
        ]);

        // Regla de negocio: no se puede marcar "Resuelto" sin el PDF que lo
        // respalde. Se valida en el servidor, no solo bloqueando la opción
        // en el frontend — así nadie puede saltárselo llamando a la API
        // directo o editando el HTML.
        if (($validado['estado'] ?? null) === 'Resuelto' && !$request->hasFile('archivo_resolucion')) {
            return response()->json([
                'message' => 'No se puede marcar como Resuelto sin adjuntar el documento de resolución.',
                'errors' => [
                    'estado' => ['Debes adjuntar el PDF de resolución antes de marcar este problema como Resuelto.'],
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

        return response()->json($problema, 201);
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
        ]);

        // Estado final después de este update (si no viene "estado" en el
        // request, se queda el que ya tenía el problema).
        $estadoFinal = $validado['estado'] ?? $problema->estado;

        // ¿Ya existe un PDF, o se está subiendo uno nuevo en este mismo
        // request? Cualquiera de las dos vale para poder marcar Resuelto.
        $tendraArchivo = $problema->archivo_resolucion_path || $request->hasFile('archivo_resolucion');

        if ($estadoFinal === 'Resuelto' && !$tendraArchivo) {
            return response()->json([
                'message' => 'No se puede marcar como Resuelto sin adjuntar el documento de resolución.',
                'errors' => [
                    'estado' => ['Debes adjuntar el PDF de resolución antes de marcar este problema como Resuelto.'],
                ],
            ], 422);
        }

        if ($request->hasFile('archivo_resolucion')) {
            // Si ya había un PDF anterior, se borra del disco antes de
            // guardar el nuevo — no dejamos archivos huérfanos.
            if ($problema->archivo_resolucion_path) {
                Storage::disk('public')->delete($problema->archivo_resolucion_path);
            }
            $archivo = $request->file('archivo_resolucion');
            $validado['archivo_resolucion_path'] = $archivo->store('problemas/resolucion', 'public');
            $validado['archivo_resolucion_nombre_original'] = $archivo->getClientOriginalName();
        }

        $validado['id_usuario_actualizador'] = $request->user()->id_usuario ?? $request->user()->id;

        $problema->update($validado);

        return response()->json($problema);
    }

    // DELETE /api/problemas/{problema}
    public function destroy(Problema $problema)
    {
        if ($problema->archivo_resolucion_path) {
            Storage::disk('public')->delete($problema->archivo_resolucion_path);
        }

        $problema->delete();

        return response()->json(null, 204);
    }
}