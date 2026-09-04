<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ObjetoGastoFinanciero;
use App\Models\PartidaPresupuestaria;
use App\Models\Proyecto;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProgramacionFinancieraController extends Controller
{
    private const CAMPOS_MES = [
        'monto_ene', 'monto_feb', 'monto_mar', 'monto_abr', 'monto_may', 'monto_jun',
        'monto_jul', 'monto_ago', 'monto_sep', 'monto_oct', 'monto_nov', 'monto_dic',
    ];

    public function index(Proyecto $proyecto)
    {
        $mesActual = Carbon::now()->month; // 1-12, se recalcula solo con el tiempo real.
        $camposMesTranscurridos = array_slice(self::CAMPOS_MES, 0, $mesActual);

        $partidas = $proyecto->partidasPresupuestarias()
            ->with(['objetos' => fn ($q) => $q->orderBy('numero')])
            ->orderBy('id_partida')
            ->get();

        $data = $partidas->map(function ($partida) use ($camposMesTranscurridos) {
            $totalEjecutadoPartida = $partida->objetos->sum('monto_ejecutado');
            $saldoPorEjecutar = (float) $partida->presupuesto_aprobado - (float) $totalEjecutadoPartida;

            $objetosFormateados = $partida->objetos->map(function ($o) use ($camposMesTranscurridos, $saldoPorEjecutar) {
                $totalAnual = array_sum(array_map(fn ($campo) => (float) $o->$campo, self::CAMPOS_MES));
                $programacionAcumulada = array_sum(array_map(fn ($campo) => (float) $o->$campo, $camposMesTranscurridos));

                $cumplimientoFinanciero = $programacionAcumulada == 0
                    ? null
                    : min((float) $o->monto_ejecutado / $programacionAcumulada, 1);

                return [
                    'id_objeto' => $o->id_objeto,
                    'numero' => $o->numero,
                    'codigo_objeto' => $o->codigo_objeto,
                    'descripcion' => $o->descripcion,
                    'meses' => collect(self::CAMPOS_MES)->mapWithKeys(
                        fn ($campo) => [$campo => (float) $o->$campo]
                    ),
                    'total_anual' => $totalAnual,
                    'monto_ejecutado' => (float) $o->monto_ejecutado,
                    // Saldo por Ejecutar es a nivel de PARTIDA (ver nota en el
                    // chat) — se repite igual en cada objeto del mismo grupo.
                    'saldo_por_ejecutar' => $saldoPorEjecutar,
                    'programacion_acumulada' => $programacionAcumulada,
                    'cumplimiento_financiero' => $cumplimientoFinanciero,
                ];
            });

            return [
                'id_partida' => $partida->id_partida,
                'presupuesto_aprobado' => (float) $partida->presupuesto_aprobado,
                'monto_ejecutado_total' => (float) $totalEjecutadoPartida,
                'saldo_por_ejecutar' => $saldoPorEjecutar,
                'objetos' => $objetosFormateados,
            ];
        });

        return response()->json($data);
    }

    public function storePartida(Request $request, Proyecto $proyecto)
    {
        $data = $request->validate([
            'presupuesto_aprobado' => 'required|numeric|min:0',
        ]);

        $partida = PartidaPresupuestaria::create([
            ...$data,
            'id_proyecto' => $proyecto->id_proyecto,
            'id_usuario_creador' => $request->user()->id_usuario ?? $request->user()->id,
        ]);

        return response()->json($partida, 201);
    }

    public function updatePartida(Request $request, PartidaPresupuestaria $partida)
    {
        $data = $request->validate([
            'presupuesto_aprobado' => 'required|numeric|min:0',
        ]);

        $data['id_usuario_actualizador'] = $request->user()->id_usuario ?? $request->user()->id;
        $partida->update($data);

        return response()->json($partida);
    }

    public function destroyPartida(PartidaPresupuestaria $partida)
    {
        $partida->delete();
        return response()->json(['message' => 'Partida eliminada']);
    }

    public function storeObjeto(Request $request, PartidaPresupuestaria $partida)
    {
        $data = $request->validate([
            'codigo_objeto' => 'required|string|max:50',
            'descripcion' => 'required|string|max:255',
            'monto_ene' => 'nullable|numeric|min:0',
            'monto_feb' => 'nullable|numeric|min:0',
            'monto_mar' => 'nullable|numeric|min:0',
            'monto_abr' => 'nullable|numeric|min:0',
            'monto_may' => 'nullable|numeric|min:0',
            'monto_jun' => 'nullable|numeric|min:0',
            'monto_jul' => 'nullable|numeric|min:0',
            'monto_ago' => 'nullable|numeric|min:0',
            'monto_sep' => 'nullable|numeric|min:0',
            'monto_oct' => 'nullable|numeric|min:0',
            'monto_nov' => 'nullable|numeric|min:0',
            'monto_dic' => 'nullable|numeric|min:0',
            'monto_ejecutado' => 'nullable|numeric|min:0',
        ]);

        // Numeración global y correlativa dentro de TODO el proyecto (no
        // solo de esta partida) — igual que el N° de tu Excel, que numera
        // de corrido cruzando todos los grupos de la tabla.
        $siguienteNumero = ObjetoGastoFinanciero::whereHas(
            'partida',
            fn ($q) => $q->where('id_proyecto', $partida->id_proyecto)
        )->withTrashed()->max('numero') ?? 0;

        $objeto = ObjetoGastoFinanciero::create([
            ...$data,
            'id_partida' => $partida->id_partida,
            'numero' => $siguienteNumero + 1,
            'id_usuario_creador' => $request->user()->id_usuario ?? $request->user()->id,
        ]);

        return response()->json($objeto, 201);
    }

    public function updateObjeto(Request $request, ObjetoGastoFinanciero $objeto)
    {
        $data = $request->validate([
            'codigo_objeto' => 'sometimes|required|string|max:50',
            'descripcion' => 'sometimes|required|string|max:255',
            'monto_ene' => 'nullable|numeric|min:0',
            'monto_feb' => 'nullable|numeric|min:0',
            'monto_mar' => 'nullable|numeric|min:0',
            'monto_abr' => 'nullable|numeric|min:0',
            'monto_may' => 'nullable|numeric|min:0',
            'monto_jun' => 'nullable|numeric|min:0',
            'monto_jul' => 'nullable|numeric|min:0',
            'monto_ago' => 'nullable|numeric|min:0',
            'monto_sep' => 'nullable|numeric|min:0',
            'monto_oct' => 'nullable|numeric|min:0',
            'monto_nov' => 'nullable|numeric|min:0',
            'monto_dic' => 'nullable|numeric|min:0',
            'monto_ejecutado' => 'nullable|numeric|min:0',
        ]);

        $data['id_usuario_actualizador'] = $request->user()->id_usuario ?? $request->user()->id;
        $objeto->update($data);

        return response()->json($objeto);
    }

    public function destroyObjeto(ObjetoGastoFinanciero $objeto)
    {
        $objeto->delete();
        return response()->json(['message' => 'Objeto de gasto eliminado']);
    }
}