<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ComponenteProyecto;
use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function store(Request $request, ComponenteProyecto $componente)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'cantidad' => 'nullable|numeric|min:0',
            'unidad' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string',
        ]);

        $siguienteOrden = ($componente->productos()->max('orden') ?? 0) + 1;

        $producto = Producto::create([
            ...$data,
            'id_componente' => $componente->id_componente,
            'orden' => $siguienteOrden,
            'id_usuario_creador' => $request->user()->id_usuario ?? $request->user()->id,
        ]);

        return response()->json($producto, 201);
    }

    public function update(Request $request, Producto $producto)
    {
        $data = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'cantidad' => 'nullable|numeric|min:0',
            'unidad' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string',
        ]);

        $data['id_usuario_actualizador'] = $request->user()->id_usuario ?? $request->user()->id;
        $producto->update($data);

        return response()->json($producto);
    }

    public function destroy(Producto $producto)
    {
        $producto->delete();

        return response()->json(['message' => 'Producto eliminado']);
    }
}