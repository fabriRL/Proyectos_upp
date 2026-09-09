<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\ReporteGenerado;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReporteGeneralController extends Controller
{
    public function index()
    {
        return response()->json($this->construirReporte());
    }

    public function exportarPdf()
    {
        $reporte = $this->construirReporte();

        $pdf = Pdf::loadView('reportes.general', $reporte)->setPaper('a4', 'landscape');
        $contenido = $pdf->output();

        $nombreArchivo = 'reporte-general-' . now()->format('Y-m-d_His') . '.pdf';
        $rutaRelativa = 'reportes/' . $nombreArchivo;
        Storage::disk('public')->put($rutaRelativa, $contenido);

        $this->registrarHistorial('pdf', $nombreArchivo, $rutaRelativa, $reporte['total_proyectos']);

        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$nombreArchivo}\"",
        ]);
    }

    public function exportarExcel()
    {
        $reporte = $this->construirReporte();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte General');

        $sheet->setCellValue('A1', 'REPORTE GENERAL DE PROYECTOS — ' . now()->format('d/m/Y'));
        $sheet->mergeCells('A1:F1');

        $sheet->setCellValue('A3', 'Avance Físico General');
        $sheet->setCellValue('B3', $reporte['stats']['avance_fisico'] . '%');
        $sheet->setCellValue('A4', 'Avance Financiero General');
        $sheet->setCellValue('B4', $reporte['stats']['avance_financiero'] . '%');
        $sheet->setCellValue('A5', 'Total de proyectos');
        $sheet->setCellValue('B5', $reporte['total_proyectos']);

        $encabezados = ['N°', 'Código', 'Proyecto', 'Avance Físico (%)', 'Avance Financiero (%)', 'Días Restantes', 'Estado General', 'Semáforo'];
        $sheet->fromArray($encabezados, null, 'A7');

        $fila = 8;
        foreach ($reporte['proyectos'] as $p) {
            $sheet->fromArray([
                $p['n'], $p['codigo'], $p['nombre'],
                $p['avance_fisico'], $p['avance_financiero'],
                $p['dias_restantes'], $p['estado_general'], $p['semaforo']['texto'],
            ], null, "A{$fila}");
            $fila++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $nombreArchivo = 'reporte-general-' . now()->format('Y-m-d_His') . '.xlsx';
        $rutaRelativa = 'reportes/' . $nombreArchivo;
        $rutaCompleta = storage_path('app/public/' . $rutaRelativa);

        if (!file_exists(dirname($rutaCompleta))) {
            mkdir(dirname($rutaCompleta), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($rutaCompleta);

        $this->registrarHistorial('excel', $nombreArchivo, $rutaRelativa, $reporte['total_proyectos']);

        return response()->download($rutaCompleta, $nombreArchivo)->deleteFileAfterSend(false);
    }

    // Lista el histórico de reportes generados — usado por la pantalla
    // nueva del sidebar "Reportes".
    public function historial()
    {
        return response()->json(
            ReporteGenerado::orderByDesc('creado_en')->get()
        );
    }

    public function destroyHistorial(ReporteGenerado $reporte)
    {
        Storage::disk('public')->delete($reporte->ruta_archivo);
        $reporte->delete();

        return response()->json(['message' => 'Reporte eliminado del historial']);
    }

    private function registrarHistorial(string $tipo, string $nombreArchivo, string $rutaRelativa, int $totalProyectos): void
    {
        ReporteGenerado::create([
            'id_usuario' => request()->user()->id_usuario ?? request()->user()->id,
            'tipo' => $tipo,
            'nombre_archivo' => $nombreArchivo,
            'ruta_archivo' => $rutaRelativa,
            'total_proyectos' => $totalProyectos,
        ]);
    }

    private function construirReporte(): array
    {
        $proyectos = Proyecto::with([
            'contratos' => fn ($q) => $q->where('activo', true),
        ])->get();

        $todosContratos = $proyectos->flatMap->contratos;

        $montoVigenteTotal = (float) $todosContratos->sum('monto_vigente');
        $ejecutadoTotal = (float) $todosContratos->sum('monto_ejecutado_acumulado');
        $amortizacionTotal = (float) $todosContratos->sum('amortizacion_acumulada');
        $anticipoTotal = (float) $todosContratos->sum('anticipo');

        $avanceFisicoGeneral = $montoVigenteTotal > 0
            ? round($todosContratos->sum(fn ($c) => (float) $c->avance_fisico * (float) $c->monto_vigente) / $montoVigenteTotal, 1)
            : 0;

        $avanceFinancieroGeneral = $montoVigenteTotal > 0
            ? round((($ejecutadoTotal - $amortizacionTotal + $anticipoTotal) / $montoVigenteTotal) * 100, 1)
            : 0;

        $filas = $proyectos->values()->map(function ($p, $i) {
            $contratos = $p->contratos;
            $montoVigenteProyecto = (float) $contratos->sum('monto_vigente');

            $avanceFisico = $montoVigenteProyecto > 0
                ? round($contratos->sum(fn ($c) => (float) $c->avance_fisico * (float) $c->monto_vigente) / $montoVigenteProyecto, 1)
                : 0;

            $ejecutado = (float) $contratos->sum('monto_ejecutado_acumulado');
            $amortizacion = (float) $contratos->sum('amortizacion_acumulada');
            $anticipo = (float) $contratos->sum('anticipo');
            $avanceFinanciero = $montoVigenteProyecto > 0
                ? round((($ejecutado - $amortizacion + $anticipo) / $montoVigenteProyecto) * 100, 1)
                : 0;

            $diasRestantes = $p->fecha_conclusion_actual
                ? (int) Carbon::now()->diffInDays(Carbon::parse($p->fecha_conclusion_actual), false)
                : null;

            return [
                'n' => $i + 1,
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'avance_fisico' => $avanceFisico,
                'avance_financiero' => $avanceFinanciero,
                'dias_restantes' => $diasRestantes,
                'estado_general' => $this->estadoGeneral($contratos),
                'semaforo' => $this->semaforo($p, $avanceFisico, $diasRestantes),
            ];
        });

        return [
            'total_proyectos' => $proyectos->count(),
            'stats' => [
                'avance_fisico' => $avanceFisicoGeneral,
                'avance_financiero' => $avanceFinancieroGeneral,
            ],
            'proyectos' => $filas,
            'generado_en' => now()->format('d/m/Y H:i'),
        ];
    }

    private function estadoGeneral($contratos): string
    {
        if ($contratos->isEmpty()) return 'Sin contratos';
        if ($contratos->contains('estado_contractual', 'Vencido')) return 'Vencido';
        if ($contratos->contains('estado_contractual', 'Paralizado')) return 'Paralizado';
        if ($contratos->every(fn ($c) => $c->estado_contractual === 'Cerrado')) return 'Concluido';
        return 'Vigente';
    }

    private function semaforo(Proyecto $p, float $avanceFisico, ?int $diasRestantes): array
    {
        if ($p->fecha_conclusion_actual && $diasRestantes !== null && $diasRestantes <= 0) {
            return ['texto' => 'VENCIDO', 'color' => '#f87171'];
        }

        $plazoVigente = $p->plazo_contractual_actual_dias;

        if (!$plazoVigente || !$p->fecha_inicio_contractual) {
            return ['texto' => 'SIN DATOS', 'color' => '#8ea9bf'];
        }

        $diasTranscurridos = max(0, Carbon::parse($p->fecha_inicio_contractual)->diffInDays(Carbon::now()));
        $avanceEsperado = min(100, ($diasTranscurridos / $plazoVigente) * 100);
        $brecha = $avanceFisico - $avanceEsperado;

        if ($brecha <= -15) return ['texto' => 'CRÍTICO', 'color' => '#f87171'];
        if ($brecha <= -5) return ['texto' => 'EN RIESGO', 'color' => '#f59e0b'];
        return ['texto' => 'EN TIEMPO', 'color' => '#00c9a7'];
    }
}