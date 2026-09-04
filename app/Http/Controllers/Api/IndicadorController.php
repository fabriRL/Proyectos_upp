<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;

class IndicadorController extends Controller
{
    /**
     * Resumen de indicadores del proyecto (pestaña "4. Resumen").
     * Endpoint independiente del DashboardController (que alimenta la
     * pantalla general "Dashboard de seguimiento" y tiene otra forma).
     */
    public function show(Proyecto $proyecto)
    {
        $proyecto->load(['actividades', 'problemas', 'contratos']);

        $actividades = $proyecto->actividades;
        $problemas   = $proyecto->problemas;
        $contratos   = $proyecto->contratos;

        // ---------------------------------------------------------
        // 1. Cumplimiento del Cronograma
        // ---------------------------------------------------------
        $avgProgramado = $actividades->avg('porcentaje_cumplimiento_programado');
        $avgReal       = $actividades->avg('porcentaje_cumplimiento_real');

        $cumplimientoCronograma = ($avgProgramado > 0)
            ? min(100, round(($avgReal / $avgProgramado) * 100, 2))
            : 0;

        // ---------------------------------------------------------
        // 2. Gestión de Problemas
        // Usa el estado "Resuelto", tal como lo maneja el resto de la app.
        // ---------------------------------------------------------
        $totalProblemas = $problemas->count();
        $problemasResueltos = $problemas->where('estado', 'Resuelto')->count();

        $gestionProblemas = ($totalProblemas > 0)
            ? round(($problemasResueltos / $totalProblemas) * 100, 2)
            : 0;

        // ---------------------------------------------------------
        // 3. Índice de Desempeño Fiscal
        // Avance físico vs financiero por contrato, promediado.
        // ---------------------------------------------------------
        $indicesDesempeno = $contratos
            ->filter(fn ($c) => (float) $c->avance_financiero > 0)
            ->map(function ($c) {
                $ratio = ((float) $c->avance_fisico / (float) $c->avance_financiero) * 100;
                return min(100, $ratio);
            });

        $indiceDesempenoFiscal = $indicesDesempeno->count() > 0
            ? round($indicesDesempeno->avg(), 2)
            : 0;

        // ---------------------------------------------------------
        // 4. Riesgo Contractual
        // ---------------------------------------------------------
        $montoVigenteTotal = (float) $contratos->sum('monto_vigente');
        $montoEnRiesgo = (float) $contratos
            ->whereIn('estado_contractual', ['Vencido', 'Paralizado'])
            ->sum('monto_vigente');

        $riesgoContractual = ($montoVigenteTotal > 0)
            ? round(($montoEnRiesgo / $montoVigenteTotal) * 100, 2)
            : 0;

        // ---------------------------------------------------------
        // 5. Cumplimiento Financiero
        // ---------------------------------------------------------
        $montoEjecutadoTotal = (float) $contratos->sum('monto_ejecutado_acumulado');

        $cumplimientoFinanciero = ($montoVigenteTotal > 0)
            ? round(($montoEjecutadoTotal / $montoVigenteTotal) * 100, 2)
            : 0;

        // ---------------------------------------------------------
        // 6. Ejecución Presupuestaria
        // ---------------------------------------------------------
        $montoDecreto = (float) ($proyecto->monto_decreto ?? 0);

        $ejecucionPresupuestaria = ($montoDecreto > 0)
            ? round(($montoEjecutadoTotal / $montoDecreto) * 100, 2)
            : 0;

        // ---------------------------------------------------------
        // 7. Utilización del Decreto Supremo
        // ---------------------------------------------------------
        $utilizacionDecreto = ($montoDecreto > 0)
            ? round(($montoVigenteTotal / $montoDecreto) * 100, 2)
            : 0;

        // ---------------------------------------------------------
        // 8. Nivel de Riesgo (compuesto)
        // ---------------------------------------------------------
        $problemasAbiertosPct = 100 - $gestionProblemas;
        $atrasoCronogramaPct = max(0, 100 - $cumplimientoCronograma);

        $nivelRiesgo = round(
            ($riesgoContractual + $problemasAbiertosPct + $atrasoCronogramaPct) / 3,
            2
        );

        return response()->json([
            'cumplimiento_cronograma'     => $cumplimientoCronograma,
            'gestion_problemas'           => $gestionProblemas,
            'indice_desempeno_fiscal'     => $indiceDesempenoFiscal,
            'riesgo_contractual'          => $riesgoContractual,
            'cumplimiento_financiero'     => $cumplimientoFinanciero,
            'ejecucion_presupuestaria'    => $ejecucionPresupuestaria,
            'utilizacion_decreto_supremo' => $utilizacionDecreto,
            'nivel_riesgo'                => $nivelRiesgo,
        ]);
    }
}