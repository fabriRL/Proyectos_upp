<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ComponenteProyecto;
use App\Models\Proyecto;
use Illuminate\Http\Request;

class ComponenteController extends Controller
{
    public function index(Proyecto $proyecto)
    {
        $componentes = $proyecto->componentes()
            ->with(['productos' => fn ($q) => $q->orderBy('orden')])
            ->orderBy('orden')
            ->get();

        return response()->json($componentes);
    }

    public function store(Request $request, Proyecto $proyecto)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
        ]);

        $siguienteOrden = ($proyecto->componentes()->max('orden') ?? 0) + 1;

        $componente = ComponenteProyecto::create([
            ...$data,
            'id_proyecto' => $proyecto->id_proyecto,
            'orden' => $siguienteOrden,
            'id_usuario_creador' => $request->user()->id_usuario ?? $request->user()->id,
        ]);

        return response()->json($componente->load('productos'), 201);
    }

    public function update(Request $request, ComponenteProyecto $componente)
    {
        $data = $request->validate([
            'nombre' => 'sometimes|required|string|max:150',
            'descripcion' => 'nullable|string',
        ]);

        $data['id_usuario_actualizador'] = $request->user()->id_usuario ?? $request->user()->id;
        $componente->update($data);

        return response()->json($componente);
    }

    public function destroy(ComponenteProyecto $componente)
    {
        $componente->delete();

        return response()->json(['message' => 'Componente eliminado']);
    }
}