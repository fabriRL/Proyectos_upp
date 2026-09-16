<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\ReporteGenerado;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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

        $pdf = Pdf::loadView('reportes.general', $reporte)->setPaper('a3', 'landscape');
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

        // Orden reagrupado: Monto D.S. queda junto a Entidad, y TODAS las
        // columnas que vienen de Contratos quedan consecutivas (F a O),
        // para poder resaltarlas como un solo bloque de color.
        $encabezados = [
            'N°', 'Código', 'Proyecto', 'Entidad Ejecutora / Empresa de Supervisión', 'Monto del D.S. (Bs)',
            'Contratistas', 'Monto Contrato Original (Bs)', 'Monto según Modificaciones (Bs)',
            'Orden de Proceder (más antigua)', 'Conclusión Prevista (más reciente)',
            'Avance Físico (%)', 'Avance Financiero (%)',
            'Entrega Provisional', 'Entrega Definitiva', 'Estado General',
            'Avance Físico Infraestructura (%)', 'Avance Físico Equipamiento (%)', 'Avance Insumos / Puesta en Marcha (%)',
            'Últimas Modificaciones Realizadas',
            'Últimas Acciones Realizadas (Resoluciones)',
            'Descripción Planilla Pendiente (Contratista)', 'Monto Pendiente Contratista (Bs)',
            'Descripción Planilla Pendiente (Supervisión)', 'Monto Pendiente Supervisión (Bs)',
            'Increm. D.S. 5321 (Bs)', 'Anticipo Adic. D.S. 5406 (Bs)', 'Asignación SIGEP',
            'Problemas', 'Acciones', 'Líneas y Capacidades de Producción',
            'Días Restantes', 'Semáforo',
        ];

        $totalColumnas = count($encabezados);
        $ultimaColumna = Coordinate::stringFromColumnIndex($totalColumnas);

        // --- Título ---
        $sheet->setCellValue('A1', 'REPORTE GENERAL DE PROYECTOS');
        $sheet->mergeCells("A1:{$ultimaColumna}1");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D1F30']],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        $sheet->setCellValue('A2', 'Generado el ' . $reporte['generado_en'] . ' — ' . $reporte['total_proyectos'] . ' proyecto(s)');
        $sheet->mergeCells("A2:{$ultimaColumna}2");
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '647A8E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // --- Tarjetas de resumen ---
        $sheet->setCellValue('A4', 'Avance Físico General');
        $sheet->setCellValue('B4', $reporte['stats']['avance_fisico'] / 100);
        $sheet->getStyle('B4')->getNumberFormat()->setFormatCode('0.0%');

        $sheet->setCellValue('D4', 'Avance Financiero General');
        $sheet->setCellValue('E4', $reporte['stats']['avance_financiero'] / 100);
        $sheet->getStyle('E4')->getNumberFormat()->setFormatCode('0.0%');

        $sheet->getStyle('A4:E4')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('B4')->getFont()->getColor()->setRGB('00A67D');
        $sheet->getStyle('E4')->getFont()->getColor()->setRGB('00A67D');

        // --- Encabezados de columnas ---
        $filaEncabezados = 6;
        $sheet->fromArray($encabezados, null, "A{$filaEncabezados}");
        $sheet->getStyle("A{$filaEncabezados}:{$ultimaColumna}{$filaEncabezados}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A52']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B9CADB']]],
        ]);
        $sheet->getRowDimension($filaEncabezados)->setRowHeight(34);

        // Resalta el bloque de columnas F–O (Contratistas...Estado
        // General) — mismas 10 columnas que en pantalla vienen de Contratos.
        $sheet->getStyle("F{$filaEncabezados}:O{$filaEncabezados}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2B6E96']],
        ]);

        // --- Datos ---
        $filaInicioDatos = $filaEncabezados + 1;
        $fila = $filaInicioDatos;

        $columnasMonto = [5, 7, 8, 22, 24, 25, 26];
        $columnasPorcentaje = [11, 12, 16, 17, 18];
        $columnasTextoLargo = [3, 4, 6, 19, 20, 21, 23, 28, 29, 30];

        foreach ($reporte['proyectos'] as $p) {
            $filaDatos = [
                $p['n'], $p['codigo'], $p['nombre'], $p['empresa_supervision'] ?? '—', $p['monto_decreto_vigente'],
                $p['contratistas'], $p['monto_original'], $p['monto_modificaciones'],
                $p['fecha_orden_proceder'] ?? '—', $p['fecha_conclusion_prevista'] ?? '—',
                $p['avance_fisico'], $p['avance_financiero'],
                $p['fecha_entrega_provisional'] ?? 'En proceso', $p['fecha_entrega_definitiva'] ?? 'En proceso', $p['estado_general'],
                $p['avance_fisico_infraestructura'], $p['avance_fisico_equipamiento'], $p['avance_insumos_puesta_marcha'],
                $p['ultimas_modificaciones'] ?? '—',
                $p['ultimas_acciones'] ?? '—',
                $p['descripcion_planilla_pendiente_contratista'] ?? '—', $p['monto_planilla_pendiente_contratista'],
                $p['descripcion_planilla_pendiente_supervision'] ?? '—', $p['monto_planilla_pendiente_supervision'],
                $p['incremento_ds_5321'], $p['anticipo_adicional_ds_5406'],
                $p['tiene_sigep'] === true ? 'Sí' : ($p['tiene_sigep'] === false ? 'No' : '—'),
                $p['problemas'] ?? '—', $p['acciones'] ?? '—', $p['lineas_capacidades'] ?? '—',
                $p['dias_restantes'] ?? '—', $p['semaforo']['texto'],
            ];

            $sheet->fromArray($filaDatos, null, "A{$fila}");

            // Franja alterna, para que se lea más fácil por fila.
            if (($fila - $filaInicioDatos) % 2 === 1) {
                $sheet->getStyle("A{$fila}:{$ultimaColumna}{$fila}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F6FA']],
                ]);
            }

            // Franja azul clara en las columnas de Contratos, en cada fila.
            $sheet->getStyle("F{$fila}:O{$fila}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E4F0FB']],
            ]);

            // Color del semáforo, igual criterio que en pantalla.
            $colorSemaforo = match ($p['semaforo']['texto']) {
                'VENCIDO', 'CRÍTICO' => 'F87171',
                'EN RIESGO' => 'F59E0B',
                'SIN DATOS' => '8EA9BF',
                default => '00A67D',
            };
            $colSemaforo = Coordinate::stringFromColumnIndex($totalColumnas);
            $sheet->getStyle("{$colSemaforo}{$fila}")->getFont()->setBold(true);
            $sheet->getStyle("{$colSemaforo}{$fila}")->getFont()->getColor()->setRGB($colorSemaforo);

            $fila++;
        }

        $filaFinDatos = $fila - 1;

        // --- Formato numérico ---
        foreach ($columnasMonto as $indice) {
            $col = Coordinate::stringFromColumnIndex($indice);
            $sheet->getStyle("{$col}{$filaInicioDatos}:{$col}{$filaFinDatos}")
                ->getNumberFormat()->setFormatCode('#,##0.00');
        }
        foreach ($columnasPorcentaje as $indice) {
            $col = Coordinate::stringFromColumnIndex($indice);
            $sheet->getStyle("{$col}{$filaInicioDatos}:{$col}{$filaFinDatos}")
                ->getNumberFormat()->setFormatCode('0.00"%"');
        }

        // --- Bordes en toda la tabla ---
        $sheet->getStyle("A{$filaEncabezados}:{$ultimaColumna}{$filaFinDatos}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D3DEE8']]],
        ]);

        // --- Alineación ---
        $sheet->getStyle("A{$filaInicioDatos}:{$ultimaColumna}{$filaFinDatos}")
            ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        foreach ($columnasTextoLargo as $indice) {
            $col = Coordinate::stringFromColumnIndex($indice);
            $sheet->getStyle("{$col}{$filaInicioDatos}:{$col}{$filaFinDatos}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
        }

        // --- Ancho de columnas ---
        foreach ($columnasTextoLargo as $indice) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($indice))->setWidth(28);
        }
        $columnasAncho12 = array_diff(range(1, $totalColumnas), $columnasTextoLargo);
        foreach ($columnasAncho12 as $indice) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($indice))->setWidth(14);
        }

        // --- Filtro y paneles congelados ---
        $sheet->setAutoFilter("A{$filaEncabezados}:{$ultimaColumna}{$filaEncabezados}");
        $sheet->freezePane("D{$filaInicioDatos}");

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
            'contratos.modificaciones',
            'decretosSupremos',
            'problemas',
            'componentes',
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

            $montoBaseDecreto = (float) ($p->monto_decreto ?? 0);
            $sumaIncrementos = (float) $p->decretosSupremos->sum('incremento');
            $montoDecretoVigente = $montoBaseDecreto + $sumaIncrementos;

            $montoOriginal = (float) $contratos->sum('monto_vigente_original');
            $montoModificaciones = $montoVigenteProyecto - $montoOriginal;

            $contratistas = $contratos->pluck('contratista')->filter()->unique()->implode(', ');

            $fechaProvisional = $this->fechaEntregaProyecto($contratos, 'fecha_entrega_provisional');
            $fechaDefinitiva = $this->fechaEntregaProyecto($contratos, 'fecha_entrega_definitiva');

            $fechaOrdenProceder = $contratos->pluck('fecha_orden_proceder')->filter()->min();
            $fechaConclusionPrevista = $contratos->pluck('fecha_conclusion_prevista')->filter()->max();

            $todasModificaciones = $contratos->flatMap->modificaciones;
            $ultimaModificacion = $todasModificaciones
                ->sortByDesc(fn ($m) => $m->fecha_firma_documento ?? $m->creado_en)
                ->first();
            $ultimasModificaciones = $ultimaModificacion
                ? $ultimaModificacion->tipo_modificacion . ' — ' .
                  Carbon::parse($ultimaModificacion->fecha_firma_documento ?? $ultimaModificacion->creado_en)->format('d/m/Y')
                : null;

            $problemaAbierto = $p->problemas
                ->where('estado', '!=', 'Resuelto')
                ->sortByDesc('fecha_registro')
                ->first();

            $lineasCapacidades = $p->componentes
                ->pluck('descripcion')
                ->filter()
                ->implode(' | ');

            return [
                'n' => $i + 1,
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'empresa_supervision' => $p->entidad_ejecutora,
                'contratistas' => $contratistas ?: '—',
                'monto_decreto_vigente' => round($montoDecretoVigente, 2),
                'monto_original' => round($montoOriginal, 2),
                'monto_modificaciones' => round($montoModificaciones, 2),
                'fecha_orden_proceder' => $fechaOrdenProceder ? Carbon::parse($fechaOrdenProceder)->format('d/m/Y') : null,
                'fecha_conclusion_prevista' => $fechaConclusionPrevista ? Carbon::parse($fechaConclusionPrevista)->format('d/m/Y') : null,
                'avance_fisico' => $avanceFisico,
                'avance_financiero' => $avanceFinanciero,
                'avance_fisico_infraestructura' => null,
                'avance_fisico_equipamiento' => null,
                'avance_insumos_puesta_marcha' => null,
                'fecha_entrega_provisional' => $fechaProvisional,
                'fecha_entrega_definitiva' => $fechaDefinitiva,
                'ultimas_modificaciones' => $ultimasModificaciones,
                'ultimas_acciones' => null,
                'descripcion_planilla_pendiente_contratista' => null,
                'monto_planilla_pendiente_contratista' => null,
                'descripcion_planilla_pendiente_supervision' => null,
                'monto_planilla_pendiente_supervision' => null,
                'incremento_ds_5321' => null,
                'anticipo_adicional_ds_5406' => null,
                'tiene_sigep' => null,
                'problemas' => $problemaAbierto->problema_identificado ?? null,
                'acciones' => $problemaAbierto->solucion_propuesta ?? null,
                'lineas_capacidades' => $lineasCapacidades ?: null,
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

    private function fechaEntregaProyecto($contratos, string $campo): ?string
    {
        if ($contratos->isEmpty()) {
            return null;
        }

        $todosConFecha = $contratos->every(fn ($c) => !empty($c->$campo));
        if (!$todosConFecha) {
            return null;
        }

        $fechaMasReciente = $contratos->max(fn ($c) => Carbon::parse($c->$campo));

        return $fechaMasReciente->format('d/m/Y');
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