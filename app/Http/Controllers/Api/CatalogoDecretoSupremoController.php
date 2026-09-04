<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DecretoSupremo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CatalogoDecretoSupremoController extends Controller
{
    // GET /api/decretos-supremos — alimenta el combobox
    public function index()
    {
        return response()->json(
            DecretoSupremo::orderBy('numero_decreto')->get()
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
}