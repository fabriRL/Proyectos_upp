<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DecretoSupremo;
use Illuminate\Http\Request;
use App\Models\Proyecto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CatalogoDecretoSupremoController extends Controller
{
    // GET /api/decretos-supremos — alimenta el combobox
    public function index()
    {
        return response()->json(
            DecretoSupremo::withCount('proyectos')->orderBy('numero_decreto')->get()
        );
    }

    // POST /api/decretos-supremos — crear uno nuevo directo en el catálogo
    // (uso independiente; el flujo de "crear proyecto" tiene su propia
    // lógica embebida, ver ProyectoController@store)
    public function store(Request $request)
    {
        $datos = $request->validate([
            'numero_decreto' => 'required|string|max:255|unique:decretos_supremos,numero_decreto',
            'monto' => 'nullable|numeric|min:0',
            'descripcion' => 'nullable|string',
            'fecha_decreto' => 'nullable|date',
        ]);

        $userId = Auth::id();

        $decreto = DecretoSupremo::create([
            ...$datos,
            'monto' => $datos['monto'] ?? 0,
            'id_usuario_creador' => $userId,
            'id_usuario_actualizador' => $userId,
        ]);

        return response()->json($decreto, 201);
    }

    // PUT /api/decretos-supremos/{decreto} — edita el decreto del catálogo.
    // Los proyectos que lo usan guardan una copia del número y del monto
    // (norma_financiador / monto_decreto), así que se actualizan juntos para
    // que nunca queden desincronizados. Los registros por proyecto
    // (decretos_supremos_proyecto) NO se tocan.
    public function update(Request $request, DecretoSupremo $decreto)
    {
        $datos = $request->validate([
            'numero_decreto' => 'required|string|max:255|unique:decretos_supremos,numero_decreto,' . $decreto->id_decreto_supremo . ',id_decreto_supremo',
            'monto' => 'required|numeric|min:0',
            'descripcion' => 'nullable|string',
            'fecha_decreto' => 'nullable|date',
        ], [
            'numero_decreto.unique' => 'Ya existe un Decreto Supremo con ese número.',
        ]);

        $proyectosActualizados = 0;

        DB::transaction(function () use ($decreto, $datos, &$proyectosActualizados) {
            $decreto->update([
                ...$datos,
                'id_usuario_actualizador' => Auth::id(),
            ]);

            Proyecto::where('id_decreto_supremo', $decreto->id_decreto_supremo)
                ->get()
                ->each(function ($proyecto) use ($decreto, &$proyectosActualizados) {
                    $proyecto->update([
                        'norma_financiador' => $decreto->numero_decreto,
                        'monto_decreto' => $decreto->monto,
                        'id_usuario_actualizador' => Auth::id(),
                    ]);
                    $proyectosActualizados++;
                });
        });

        return response()->json([
            'decreto' => $decreto->loadCount('proyectos'),
            'proyectos_actualizados' => $proyectosActualizados,
        ]);
    }

    // DELETE /api/decretos-supremos/{decreto} — eliminación lógica. No se
    // permite si algún proyecto vigente lo tiene asignado.
    public function destroy(DecretoSupremo $decreto)
    {
        $enUso = $decreto->proyectos()->count();

        if ($enUso > 0) {
            return response()->json([
                'message' => "No se puede eliminar: {$enUso} proyecto(s) usan este Decreto Supremo.",
            ], 422);
        }

        $decreto->update(['id_usuario_actualizador' => Auth::id()]);
        $decreto->delete();

        return response()->json(['message' => 'Decreto Supremo eliminado']);
    }
}
