<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DecretoSupremoProyecto;
use App\Models\Proyecto;
use Illuminate\Http\Request;

class DecretoSupremoController extends Controller
{
    public function index(Proyecto $proyecto)
    {
        $decretos = $proyecto->decretosSupremos()->orderBy('numero')->get();

        $montoVigenteContratos = (float) $proyecto->contratos()->sum('monto_vigente');

        // El monto base del decreto viene UNA SOLA VEZ del proyecto (es estático,
        // no varía entre filas). Solo los incrementos de cada fila se suman —
        // cada fila puede representar una ampliación presupuestaria distinta.
        $montoBaseDecreto = (float) ($proyecto->monto_decreto ?? 0);
        $sumaIncrementos = (float) $decretos->sum('incremento');
        $montoVigenteDecretoTotal = $montoBaseDecreto + $sumaIncrementos;

        $montoPuestaMarchaTotal = (float) $decretos->sum('monto_puesta_marcha_insumos');
        $montoAuditoriaTotal = (float) $decretos->sum('monto_auditoria_interna');

        $montoComprometido = $montoVigenteContratos + $montoPuestaMarchaTotal + $montoAuditoriaTotal;

        $utilizacionPct = $montoVigenteDecretoTotal > 0
            ? round(($montoComprometido / $montoVigenteDecretoTotal) * 100, 2)
            : 0;

        $saldoDisponible = $montoVigenteDecretoTotal - $montoComprometido;

        $decretosFormateados = $decretos->map(function ($d) {
            return [
                'id_decreto' => $d->id_decreto,
                'numero' => $d->numero,
                'numero_decreto' => $d->numero_decreto,
                'monto_inicial' => (float) $d->monto_inicial,
                'incremento' => (float) $d->incremento,
                'monto_vigente' => (float) $d->monto_inicial + (float) $d->incremento,
                'monto_puesta_marcha_insumos' => (float) $d->monto_puesta_marcha_insumos,
                'monto_auditoria_interna' => (float) $d->monto_auditoria_interna,
            ];
        });

        return response()->json([
            'decretos' => $decretosFormateados,
            'resumen' => [
                'monto_vigente_decreto_total' => $montoVigenteDecretoTotal,
                'monto_vigente_contratos' => $montoVigenteContratos,
                'monto_puesta_marcha_total' => $montoPuestaMarchaTotal,
                'monto_auditoria_total' => $montoAuditoriaTotal,
                'monto_comprometido' => $montoComprometido,
                'utilizacion_ds_pct' => $utilizacionPct,
                'saldo_disponible' => $saldoDisponible,
            ],
        ]);
    }

    public function store(Request $request, Proyecto $proyecto)
    {
        $data = $request->validate([
            'numero_decreto' => 'required|string|max:255',
            'monto_inicial' => 'required|numeric|min:0',
            'incremento' => 'nullable|numeric|min:0',
            'monto_puesta_marcha_insumos' => 'nullable|numeric|min:0',
            'monto_auditoria_interna' => 'nullable|numeric|min:0',
        ]);

        $siguienteNumero = ($proyecto->decretosSupremos()->withTrashed()->max('numero') ?? 0) + 1;

        $decreto = DecretoSupremoProyecto::create([
            ...$data,
            'id_proyecto' => $proyecto->id_proyecto,
            'numero' => $siguienteNumero,
            'incremento' => $data['incremento'] ?? 0,
            'monto_puesta_marcha_insumos' => $data['monto_puesta_marcha_insumos'] ?? 0,
            'monto_auditoria_interna' => $data['monto_auditoria_interna'] ?? 0,
            'id_usuario_creador' => $request->user()->id_usuario ?? $request->user()->id,
        ]);

        return response()->json($decreto, 201);
    }

    public function update(Request $request, DecretoSupremoProyecto $decreto)
    {
        // "numero_decreto" y "monto_inicial" vienen del proyecto / Decreto
        // Supremo original y NO se editan aquí (validate() los descarta):
        // este registro solo permite ajustar lo propio del proyecto.
        $data = $request->validate([
            'incremento' => 'nullable|numeric|min:0',
            'monto_puesta_marcha_insumos' => 'nullable|numeric|min:0',
            'monto_auditoria_interna' => 'nullable|numeric|min:0',
        ]);

        foreach (['incremento', 'monto_puesta_marcha_insumos', 'monto_auditoria_interna'] as $campo) {
            if (array_key_exists($campo, $data)) {
                $data[$campo] = $data[$campo] ?? 0;
            }
        }

        $data['id_usuario_actualizador'] = $request->user()->id_usuario ?? $request->user()->id;
        $decreto->update($data);

        return response()->json($decreto);
    }

    public function destroy(DecretoSupremoProyecto $decreto)
    {
        $decreto->delete();

        return response()->json(['message' => 'Registro eliminado']);
    }
}