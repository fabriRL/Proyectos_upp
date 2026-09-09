<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    /**
     * Dashboard GLOBAL — resumen de TODOS los proyectos del sistema.
     * Usado por Dashboard.vue (pantalla aparte en el sidebar).
     */
    public function index()
    {
        $proyectos = Proyecto::with([
            'actividades',
            'problemas',
            'contratos' => fn ($q) => $q->where('activo', true),
            'decretoSupremo',
        ])->get();

        $todasActividades = $proyectos->flatMap->actividades;
        $todosProblemas = $proyectos->flatMap->problemas;
        $todosContratos = $proyectos->flatMap->contratos;

        $montoVigenteTotal = (float) $todosContratos->sum('monto_vigente');
        $ejecutadoTotal = (float) $todosContratos->sum('monto_ejecutado_acumulado');
        $amortizacionTotal = (float) $todosContratos->sum('amortizacion_acumulada');
        $anticipoTotal = (float) $todosContratos->sum('anticipo');

        $avanceFisico = $montoVigenteTotal > 0
            ? round(
                $todosContratos->sum(fn ($c) => (float) $c->avance_fisico * (float) $c->monto_vigente) / $montoVigenteTotal,
                1
            )
            : 0;

        $avanceFinanciero = $montoVigenteTotal > 0
            ? round((($ejecutadoTotal - $amortizacionTotal + $anticipoTotal) / $montoVigenteTotal) * 100, 1)
            : 0;

        $alertasActivas = $todosProblemas->where('estado', '!=', 'Resuelto')->count();

        $proyectosVencidos = $proyectos->filter(function ($p) {
            return $p->fecha_conclusion_actual
                && Carbon::parse($p->fecha_conclusion_actual)->isPast();
        })->count();

        $montoEnRiesgo = (float) $todosContratos
            ->whereIn('estado_contractual', ['Vencido', 'Paralizado'])
            ->sum('monto_vigente');
        $riesgoContractual = $montoVigenteTotal > 0
            ? round(($montoEnRiesgo / $montoVigenteTotal) * 100, 1)
            : 0;

        $indicesDesempeno = $todosContratos
            ->filter(fn ($c) => (float) $c->avance_financiero > 0)
            ->map(fn ($c) => min(100, ((float) $c->avance_fisico / (float) $c->avance_financiero) * 100));
        $indiceDesempenoFiscal = $indicesDesempeno->count() > 0
            ? round($indicesDesempeno->avg(), 1)
            : 0;

        $proyectosConDecreto = $proyectos->filter(fn ($p) => $p->id_decreto_supremo !== null);
        $montoDecretosUnicos = (float) $proyectosConDecreto
            ->pluck('decretoSupremo.monto', 'id_decreto_supremo')
            ->filter()
            ->sum();
        $montoVigenteConDecreto = (float) $proyectosConDecreto->flatMap->contratos->sum('monto_vigente');
        $utilizacionDecreto = $montoDecretosUnicos > 0
            ? round(($montoVigenteConDecreto / $montoDecretosUnicos) * 100, 1)
            : 0;

        $totalProblemas = $todosProblemas->count();
        $problemasResueltos = $todosProblemas->where('estado', 'Resuelto')->count();
        $gestionProblemas = $totalProblemas
            ? round(($problemasResueltos / $totalProblemas) * 100, 1)
            : 0;

        $sumaReal = (float) $todasActividades->sum('porcentaje_cumplimiento_real');
        $sumaProgramado = (float) $todasActividades->sum('porcentaje_cumplimiento_programado');
        $cumplimientoCronograma = $sumaProgramado > 0
            ? round(($sumaReal / $sumaProgramado) * 100, 1)
            : 0;

        $kpis = [
            ['label' => 'Cumpl. cronograma', 'v' => $cumplimientoCronograma],
            ['label' => 'Gestión de problemas', 'v' => $gestionProblemas],
            ['label' => 'Índice del fiscal', 'v' => $indiceDesempenoFiscal],
            ['label' => 'Riesgo contractual', 'v' => $riesgoContractual],
            ['label' => 'Cumpl. financiero', 'v' => $avanceFinanciero],
            ['label' => 'Utilización D.S.', 'v' => $utilizacionDecreto],
        ];

        $contratosPorProyecto = $proyectos->values()->map(function ($p, $i) {
            $contratosProyecto = $p->contratos;
            $montoVigenteProyecto = (float) $contratosProyecto->sum('monto_vigente');

            $af = $montoVigenteProyecto > 0
                ? round($contratosProyecto->sum(fn ($c) => (float) $c->avance_fisico * (float) $c->monto_vigente) / $montoVigenteProyecto, 1)
                : 0;

            $ejecutado = (float) $contratosProyecto->sum('monto_ejecutado_acumulado');
            $amortizacion = (float) $contratosProyecto->sum('amortizacion_acumulada');
            $anticipo = (float) $contratosProyecto->sum('anticipo');
            $afin = $montoVigenteProyecto > 0
                ? round((($ejecutado - $amortizacion + $anticipo) / $montoVigenteProyecto) * 100, 1)
                : 0;

            return [
                'n' => $i + 1,
                'short' => Str::limit($p->nombre, 10, ''),
                'af' => $af,
                'afin' => $afin,
            ];
        });

        $problemas = collect();
        $n = 0;
        foreach ($proyectos as $p) {
            foreach ($p->problemas as $prob) {
                $n++;
                $fin = $prob->fecha_cierre ? Carbon::parse($prob->fecha_cierre) : Carbon::now();
                $dias = Carbon::parse($prob->fecha_registro)->diffInDays($fin);

                $problemas->push([
                    'n' => $n,
                    'proyecto' => $p->nombre,
                    'prob' => $prob->problema_identificado,
                    'impacto' => $prob->impacto,
                    'resp' => $prob->responsable,
                    'estado' => $prob->estado,
                    'dias' => (int) $dias,
                ]);
            }
        }

        return response()->json([
            'total_proyectos' => $proyectos->count(),
            'stats' => [
                'avance_fisico' => $avanceFisico,
                'avance_financiero' => $avanceFinanciero,
                'proyectos_vencidos' => $proyectosVencidos,
                'alertas_activas' => $alertasActivas,
            ],
            'contratos' => $contratosPorProyecto,
            'kpis' => $kpis,
            'problemas' => $problemas,
        ]);
    }

    /**
     * Resumen de UN proyecto específico — usado por DatosGenerales.vue
     * para las tarjetas de arriba (Avance físico / financiero / Días
     * restantes / Semáforo). Reemplaza al viejo show(Proyecto $proyecto)
     * que quedó eliminado al convertir el dashboard principal en global.
     */
    public function resumenProyecto(Proyecto $proyecto)
    {
        $contratos = $proyecto->contratos()->where('activo', true)->get();

        $montoVigenteTotal = (float) $contratos->sum('monto_vigente');
        $ejecutadoTotal = (float) $contratos->sum('monto_ejecutado_acumulado');
        $amortizacionTotal = (float) $contratos->sum('amortizacion_acumulada');
        $anticipoTotal = (float) $contratos->sum('anticipo');

        $avanceFisico = $montoVigenteTotal > 0
            ? round(
                $contratos->sum(fn ($c) => (float) $c->avance_fisico * (float) $c->monto_vigente) / $montoVigenteTotal,
                1
            )
            : 0;

        $avanceFinanciero = $montoVigenteTotal > 0
            ? round((($ejecutadoTotal - $amortizacionTotal + $anticipoTotal) / $montoVigenteTotal) * 100, 1)
            : 0;

        $diasRestantes = $proyecto->fecha_conclusion_actual
            ? max(0, (int) Carbon::now()->diffInDays(Carbon::parse($proyecto->fecha_conclusion_actual), false))
            : 0;

        return response()->json([
            'stats' => [
                'avance_fisico' => $avanceFisico,
                'avance_financiero' => $avanceFinanciero,
                'dias_restantes' => $diasRestantes,
            ],
        ]);
    }
}