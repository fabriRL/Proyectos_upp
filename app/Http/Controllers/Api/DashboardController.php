<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    /**
     * Dashboard de seguimiento de un proyecto, con datos reales de la BD.
     *
     * NOTA: tu BD todavía está vacía, así que todo esto va a devolver
     * ceros/arrays vacíos hasta que cargues actividades, problemas y
     * componentes de prueba. La lógica ya está lista para cuando haya datos.
     */
    public function show(Proyecto $proyecto)
    {
        $actividades = $proyecto->actividades()->get();
        $problemasTodos = $proyecto->problemas()->get();
        $componentes = $proyecto->componentes()->orderBy('orden')->get();

        // --- Avance físico: promedio del cumplimiento real de las actividades ---
        $avanceFisico = $actividades->count()
            ? round((float) $actividades->avg('porcentaje_cumplimiento_real'), 1)
            : 0;

        // --- Avance financiero ---
        // TODO: en el esquema actual no existe una tabla de ejecución/desembolsos
        // financieros (solo 'monto_decreto', que es el presupuesto planificado).
        // Queda en 0 hasta que definamos de dónde sale este porcentaje.
        $avanceFinanciero = 0;

        // --- Días restantes ---
        $diasRestantes = $proyecto->fecha_conclusion_actual
            ? max(0, (int) Carbon::now()->diffInDays(Carbon::parse($proyecto->fecha_conclusion_actual), false))
            : 0;

        // --- Alertas activas: problemas cuyo estado no es "Resuelto" ---
        $alertasActivas = $problemasTodos->where('estado', '!=', 'Resuelto')->count();

        // --- Avance por contrato (usa componentes_proyecto como "contratos") ---
        // TODO: 'actividades' no tiene id_componente en el esquema actual, así que
        // no hay forma de calcular el avance físico/financiero POR componente todavía.
        // Se listan los componentes reales con 0% hasta resolver esa relación.
        $contratos = $componentes->values()->map(function ($c, $i) {
            return [
                'n' => $i + 1,
                'short' => Str::limit($c->nombre, 10, ''),
                'af' => 0,
                'afin' => 0,
            ];
        });

        // --- Indicadores de desempeño ---
        // Solo calculamos los que sí se pueden derivar del esquema actual.
        $totalProblemas = $problemasTodos->count();
        $problemasResueltos = $problemasTodos->where('estado', 'Resuelto')->count();
        $gestionProblemas = $totalProblemas
            ? round(($problemasResueltos / $totalProblemas) * 100, 1)
            : 0;

        $kpis = [
            ['label' => 'Cumpl. cronograma', 'v' => $avanceFisico],
            ['label' => 'Gestión de problemas', 'v' => $gestionProblemas],
            ['label' => 'Índice del fiscal', 'v' => 0],       // TODO: falta definir origen del dato
            ['label' => 'Riesgo contractual', 'v' => 0],      // TODO: falta definir origen del dato
            ['label' => 'Cumpl. financiero', 'v' => $avanceFinanciero], // TODO: depende de ejecución financiera
            ['label' => 'Utilización D.S.', 'v' => 0],        // TODO: falta definir origen del dato
        ];

        // --- Problemas / alertas ---
        $problemas = $problemasTodos->values()->map(function ($p, $i) {
            $fin = $p->fecha_cierre ? Carbon::parse($p->fecha_cierre) : Carbon::now();
            $dias = Carbon::parse($p->fecha_registro)->diffInDays($fin);

            return [
                'n' => $i + 1,
                'prob' => $p->problema_identificado,
                'impacto' => $p->impacto,
                'resp' => $p->responsable,
                'estado' => $p->estado,
                'dias' => (int) $dias,
            ];
        });

        return response()->json([
            'proyecto' => [
                'id' => $proyecto->id_proyecto,
                'codigo' => $proyecto->codigo,
                'nombre' => $proyecto->nombre,
            ],
            'stats' => [
                'avance_fisico' => $avanceFisico,
                'avance_financiero' => $avanceFinanciero,
                'dias_restantes' => $diasRestantes,
                'alertas_activas' => $alertasActivas,
            ],
            'contratos' => $contratos,
            'kpis' => $kpis,
            'problemas' => $problemas,
        ]);
    }
}