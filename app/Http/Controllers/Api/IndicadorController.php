<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use Carbon\Carbon;

class IndicadorController extends Controller
{
    private const PESO_CERRADO = 0;
    private const PESO_CUMPLIDO = 0;
    private const PESO_VIGENTE = 1;
    private const PESO_PROXIMO_A_VENCER = 2;
    private const PESO_VENCIDO = 4;

    public function show(Proyecto $proyecto)
    {
        $proyecto->load(['actividades', 'problemas', 'contratos']);
        $actividades = $proyecto->actividades;
        $problemas   = $proyecto->problemas;
        $contratos   = $proyecto->contratos;

        $avgProgramado = $actividades->avg('porcentaje_cumplimiento_programado');
        $avgReal       = $actividades->avg('porcentaje_cumplimiento_real');
        $cumplimientoCronograma = ($avgProgramado > 0)
            ? min(100, round(($avgReal / $avgProgramado) * 100, 2))
            : 0;

        $totalProblemas = $problemas->count();
        $problemasResueltos = $problemas->where('estado', 'Resuelto')->count();
        $gestionProblemas = ($totalProblemas > 0)
            ? round(($problemasResueltos / $totalProblemas) * 100, 2)
            : 0;

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
        // 4. Riesgo Contractual — normalizado a escala 0-100.
        // Cada componente (contratos y problemas) se lleva a una
        // fracción 0-1 ANTES de aplicar la ponderación 70/30, para que
        // el resultado final sea comparable con el resto de los KPIs.
        // ---------------------------------------------------------
        $hoy = Carbon::now()->startOfDay();

        $pesosRiesgoContratos = $contratos->map(function ($c) use ($hoy) {
            $tieneEntregaDefinitiva = !empty($c->fecha_entrega_definitiva);
            $avanceFisico = (float) $c->avance_fisico;
            $fechaConclusionPrevista = $c->fecha_conclusion_prevista
                ? Carbon::parse($c->fecha_conclusion_prevista)->startOfDay()
                : null;

            if ($tieneEntregaDefinitiva) {
                return self::PESO_CERRADO;
            }
            if ($avanceFisico >= 100) {
                return self::PESO_CUMPLIDO;
            }
            if ($fechaConclusionPrevista && $fechaConclusionPrevista->lt($hoy) && $avanceFisico < 100) {
                return self::PESO_VENCIDO;
            }
            if ($fechaConclusionPrevista && $hoy->diffInDays($fechaConclusionPrevista, false) <= 30) {
                return self::PESO_PROXIMO_A_VENCER;
            }
            return self::PESO_VIGENTE;
        });

        $promedioPesoContratos = $pesosRiesgoContratos->count() > 0
            ? $pesosRiesgoContratos->avg()
            : 0;

        $maxPesoTeorico = self::PESO_VENCIDO; // 4 — el peso más alto posible
        $componenteContratos = $maxPesoTeorico > 0 ? ($promedioPesoContratos / $maxPesoTeorico) : 0;
        $componenteProblemas = 1 - ($gestionProblemas / 100);

        $riesgoContractual = round((($componenteContratos * 0.7) + ($componenteProblemas * 0.3)) * 100, 2);

        $montoVigenteTotal = (float) $contratos->sum('monto_vigente');
        $montoEjecutadoTotal = (float) $contratos->sum('monto_ejecutado_acumulado');
        $cumplimientoFinanciero = ($montoVigenteTotal > 0)
            ? round(($montoEjecutadoTotal / $montoVigenteTotal) * 100, 2)
            : 0;

        $montoDecreto = (float) ($proyecto->monto_decreto ?? 0);
        $ejecucionPresupuestaria = ($montoDecreto > 0)
            ? round(($montoEjecutadoTotal / $montoDecreto) * 100, 2)
            : 0;

        $utilizacionDecreto = ($montoDecreto > 0)
            ? round(($montoVigenteTotal / $montoDecreto) * 100, 2)
            : 0;

        // ---------------------------------------------------------
        // 8. Nivel de Riesgo — riesgo_contractual YA está en 0-100,
        // así que aquí ya no se vuelve a multiplicar por 100 (ese
        // paso era necesario antes, cuando la escala era 0-3.1).
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