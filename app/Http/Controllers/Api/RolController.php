<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Http\Request;

class RolController extends Controller
{
    public function index()
    {
        return response()->json(
            Rol::with('permisos:id_permiso,nombre,descripcion')->orderBy('nombre')->get()
        );
    }

    public function permisosDisponibles()
    {
        return response()->json(
            Permiso::orderBy('nombre')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100|unique:roles,nombre',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $rol = Rol::create($data);

        return response()->json($rol->load('permisos'), 201);
    }

    public function update(Request $request, Rol $rol)
    {
        $data = $request->validate([
            'nombre' => 'sometimes|required|string|max:100|unique:roles,nombre,' . $rol->id_rol . ',id_rol',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $rol->update($data);

        return response()->json($rol->load('permisos'));
    }

    public function destroy(Rol $rol)
    {
        if ($rol->usuarios()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar este rol porque hay usuarios asignados a él.',
            ], 422);
        }

        $rol->delete();

        return response()->json(['message' => 'Rol eliminado']);
    }

    // Reemplaza COMPLETO el conjunto de permisos del rol por el que
    // venga en el request — más simple para la pantalla de checkboxes
    // (marca/desmarca y guarda todo de una vez), en vez de ir
    // agregando/quitando uno por uno.
    public function actualizarPermisos(Request $request, Rol $rol)
    {
        $data = $request->validate([
            'permisos' => 'array',
            'permisos.*' => 'integer|exists:permisos,id_permiso',
        ]);

        $idsPermisos = $data['permisos'] ?? [];

        // La tabla pivote roles_permisos no tiene "actualizado_en" — solo
        // "creado_en" — así que se pasa manualmente en el sync().
        $datosSincronizar = collect($idsPermisos)->mapWithKeys(fn ($id) => [
            $id => ['creado_en' => now()],
        ])->toArray();

        $rol->permisos()->sync($datosSincronizar);

        return response()->json($rol->load('permisos'));
    }
}