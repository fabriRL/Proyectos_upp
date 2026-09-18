<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\ReporteGenerado;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Reporte de UN proyecto, armado con los mismos módulos que sus pestañas
 * (ProyectoLayout.vue) — el usuario elige cuáles módulos entran.
 * A diferencia de ReporteGeneralController (comparativo de TODOS los
 * proyectos), este reutiliza directamente los controllers de cada pestaña
 * para no duplicar su lógica de negocio.
 */
class ReporteProyectoController extends Controller
{
    public const SECCIONES = [
        'datos' => 'Datos Generales',
        'cronograma' => 'Cronograma',
        'problemas' => 'Problemas',
        'indicadores' => 'Resumen e Indicadores',
        'contratos' => 'Contratos',
        'modificaciones' => 'Modificaciones Contractuales',
        'planillas' => 'Planillas de Pago',
        'decretos' => 'Decreto Supremo',
        'financiero' => 'Programación Financiera',
    ];

    public function secciones()
    {
        return response()->json(
            collect(self::SECCIONES)
                ->map(fn ($label, $clave) => ['clave' => $clave, 'label' => $label])
                ->values()
        );
    }

    public function generar(Request $request, Proyecto $proyecto)
    {
        return response()->json(
            $this->construirReporte($proyecto, $this->seccionesSolicitadas($request))
        );
    }

    public function exportarPdf(Request $request, Proyecto $proyecto)
    {
        $reporte = $this->construirReporte($proyecto, $this->seccionesSolicitadas($request));

        $pdf = Pdf::loadView('reportes.proyecto', $reporte)->setPaper('a4', 'landscape');
        $contenido = $pdf->output();

        $nombreArchivo = 'reporte-' . Str::slug($proyecto->codigo) . '-' . now()->format('Y-m-d_His') . '.pdf';
        $rutaRelativa = 'reportes/' . $nombreArchivo;
        Storage::disk('public')->put($rutaRelativa, $contenido);

        $this->registrarHistorial('pdf', $nombreArchivo, $rutaRelativa);

        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$nombreArchivo}\"",
        ]);
    }

    public function exportarExcel(Request $request, Proyecto $proyecto)
    {
        $reporte = $this->construirReporte($proyecto, $this->seccionesSolicitadas($request));

        $spreadsheet = new Spreadsheet();
        $this->hojaResumen($spreadsheet->getActiveSheet(), $reporte);

        foreach ($reporte['secciones'] as $seccion) {
            $sheet = $spreadsheet->createSheet();
            $this->hojaTabla($sheet, $seccion);
        }

        $nombreArchivo = 'reporte-' . Str::slug($proyecto->codigo) . '-' . now()->format('Y-m-d_His') . '.xlsx';
        $rutaRelativa = 'reportes/' . $nombreArchivo;
        $rutaCompleta = storage_path('app/public/' . $rutaRelativa);

        if (!file_exists(dirname($rutaCompleta))) {
            mkdir(dirname($rutaCompleta), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($rutaCompleta);

        $this->registrarHistorial('excel', $nombreArchivo, $rutaRelativa);

        return response()->download($rutaCompleta, $nombreArchivo)->deleteFileAfterSend(false);
    }

    private function registrarHistorial(string $tipo, string $nombreArchivo, string $rutaRelativa): void
    {
        ReporteGenerado::create([
            'id_usuario' => request()->user()->id_usuario ?? request()->user()->id,
            'tipo' => $tipo,
            'nombre_archivo' => $nombreArchivo,
            'ruta_archivo' => $rutaRelativa,
            'total_proyectos' => 1,
        ]);
    }

    private function seccionesSolicitadas(Request $request): array
    {
        $solicitadas = $request->query('secciones', []);
        if (is_string($solicitadas)) {
            $solicitadas = array_filter(explode(',', $solicitadas));
        }

        $validas = array_keys(self::SECCIONES);
        $filtradas = array_values(array_intersect($validas, (array) $solicitadas));

        return $filtradas ?: $validas;
    }

    private function construirReporte(Proyecto $proyecto, array $secciones): array
    {
        $metodos = [
            'datos' => 'seccionDatos',
            'cronograma' => 'seccionCronograma',
            'problemas' => 'seccionProblemas',
            'indicadores' => 'seccionIndicadores',
            'contratos' => 'seccionContratos',
            'modificaciones' => 'seccionModificaciones',
            'planillas' => 'seccionPlanillas',
            'decretos' => 'seccionDecretos',
            'financiero' => 'seccionFinanciero',
        ];

        $bloques = [];
        foreach ($secciones as $clave) {
            if (!isset($metodos[$clave])) {
                continue;
            }
            $bloques[] = array_merge(
                ['clave' => $clave, 'titulo' => self::SECCIONES[$clave]],
                $this->{$metodos[$clave]}($proyecto)
            );
        }

        return [
            'proyecto' => [
                'codigo' => $proyecto->codigo,
                'numero_sisin_web' => $proyecto->numero_sisin_web,
                'nombre' => $proyecto->nombre,
                'estado' => $proyecto->estado,
            ],
            'secciones_incluidas' => $secciones,
            'generado_en' => now()->format('d/m/Y H:i'),
            'secciones' => $bloques,
        ];
    }

    /* ============================================================
       CELDA — formatea un valor crudo a texto legible, según tipo.
       Se usa tanto para la vista en pantalla/PDF como referencia de
       qué se guarda en Excel (ahí además se aplica formato numérico
       nativo en las columnas de tipo 'monto'/'porcentaje').
    ============================================================ */
    public static function celda($valor, string $tipo = 'texto'): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }
        if ($tipo === 'monto') {
            return number_format((float) $valor, 2);
        }
        if ($tipo === 'porcentaje') {
            return number_format((float) $valor, 2) . '%';
        }
        if ($tipo === 'booleano') {
            return $valor ? 'Sí' : 'No';
        }
        return (string) $valor;
    }

    private function fecha($valor): ?string
    {
        if (!$valor) {
            return null;
        }
        try {
            return Carbon::parse($valor)->format('d/m/Y');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /* ============================================================
       SECCIONES
    ============================================================ */

    private function seccionDatos(Proyecto $proyecto): array
    {
        $proyecto->load(['ubicaciones', 'beneficiarios', 'componentes.productos', 'decretoSupremo']);

        $filas = [
            ['Código', $proyecto->codigo],
            ['Código SISINWEB', $proyecto->numero_sisin_web],
            ['Nombre', $proyecto->nombre],
            ['Estado', $proyecto->estado],
            ['Entidad Ejecutora', $proyecto->entidad_ejecutora],
            ['Fiscal General', $proyecto->fiscal_general],
            ['Fuente de Financiamiento', $proyecto->fuente_financiamiento],
            ['Norma Financiador', $proyecto->norma_financiador],
            ['Monto Decreto (Bs)', $this->celda($proyecto->monto_decreto, 'monto')],
            ['Decreto Supremo Marco', $proyecto->decretoSupremo?->numero_decreto],
            ['Fecha Inicio Contractual', $this->fecha($proyecto->fecha_inicio_contractual)],
            ['Fecha Conclusión Inicial', $this->fecha($proyecto->fecha_conclusion_inicial_contractual)],
            ['Plazo Inicial (días)', $proyecto->plazo_contractual_inicial_dias],
            ['Fecha Conclusión Actual', $this->fecha($proyecto->fecha_conclusion_actual)],
            ['Plazo Actual (días)', $proyecto->plazo_contractual_actual_dias],
            ['Estado Derecho Propietario', $proyecto->estado_actual_derecho_propietario],
            ['Líneas / Capacidades de Producción', $proyecto->componentes_lineas_descripcion],
            ['Resultado / Impacto Socioeconómico', $proyecto->resultado_impacto_socioeconomico],
            ['Observaciones', $proyecto->observaciones],
        ];

        foreach ($proyecto->ubicaciones as $i => $u) {
            $filas[] = [
                'Ubicación ' . ($i + 1),
                collect([$u->departamento, $u->provincia, $u->municipio, $u->comunidad_localidad])
                    ->filter()->implode(' — '),
            ];
        }

        foreach ($proyecto->beneficiarios as $i => $b) {
            $filas[] = [
                'Beneficiario ' . ($i + 1),
                collect([$b->categoria, $b->tipo, $b->cantidad ? $b->cantidad . ' unid.' : null, $b->descripcion])
                    ->filter()->implode(' — '),
            ];
        }

        foreach ($proyecto->componentes as $i => $c) {
            $productos = $c->productos->pluck('nombre')->filter()->implode(', ');
            $filas[] = [
                'Componente ' . ($i + 1),
                trim($c->nombre . ($productos ? ' — Productos: ' . $productos : '')),
            ];
        }

        return [
            'encabezados' => ['Campo', 'Valor'],
            'tipos' => ['texto', 'texto'],
            'filas' => array_map(fn ($f) => [$f[0], $f[1] ?? '—'], $filas),
        ];
    }

    private function seccionCronograma(Proyecto $proyecto): array
    {
        $actividades = json_decode((new ActividadController())->index($proyecto)->getContent(), true);

        $filas = collect($actividades)->map(fn ($a) => [
            $a['numero'],
            $a['actividad'],
            $a['componente']['nombre'] ?? '—',
            $this->fecha($a['fecha_inicio']),
            $this->fecha($a['fecha_fin']),
            $a['duracion_dias'],
            $a['porcentaje_cumplimiento_programado'],
            $a['porcentaje_cumplimiento_real'],
            $a['estado'],
        ])->all();

        return [
            'encabezados' => ['N°', 'Actividad', 'Componente', 'Inicio', 'Fin', 'Duración (días)', '% Programado', '% Real', 'Estado'],
            'tipos' => ['entero', 'texto', 'texto', 'texto', 'texto', 'entero', 'porcentaje', 'porcentaje', 'texto'],
            'filas' => $filas,
        ];
    }

    private function seccionProblemas(Proyecto $proyecto): array
    {
        $problemas = json_decode((new ProblemaController())->index($proyecto)->getContent(), true);

        $filas = collect($problemas)->map(fn ($p) => [
            $this->fecha($p['fecha_registro']),
            $p['problema_identificado'],
            $p['impacto'],
            $p['solucion_propuesta'],
            $p['responsable'],
            $p['estado'],
            $this->fecha($p['fecha_cierre']),
            $p['actividad']['actividad'] ?? '—',
        ])->all();

        return [
            'encabezados' => ['Fecha Registro', 'Problema Identificado', 'Impacto', 'Solución Propuesta', 'Responsable', 'Estado', 'Fecha Cierre', 'Actividad Relacionada'],
            'tipos' => ['texto', 'texto', 'texto', 'texto', 'texto', 'texto', 'texto', 'texto'],
            'filas' => $filas,
        ];
    }

    private function seccionIndicadores(Proyecto $proyecto): array
    {
        $ind = json_decode((new IndicadorController())->show($proyecto)->getContent(), true);

        $filas = [
            ['Cumplimiento de Cronograma (%)', $ind['cumplimiento_cronograma']],
            ['Gestión de Problemas (%)', $ind['gestion_problemas']],
            ['Índice de Desempeño Fiscal (%)', $ind['indice_desempeno_fiscal']],
            ['Riesgo Contractual (%)', $ind['riesgo_contractual']],
            ['Cumplimiento Financiero (%)', $ind['cumplimiento_financiero']],
            ['Ejecución Presupuestaria (%)', $ind['ejecucion_presupuestaria']],
            ['Utilización Decreto Supremo (%)', $ind['utilizacion_decreto_supremo']],
            ['Nivel de Riesgo (%)', $ind['nivel_riesgo']],
        ];

        return [
            'encabezados' => ['Indicador', 'Valor'],
            'tipos' => ['texto', 'texto'],
            'filas' => array_map(fn ($f) => [$f[0], $this->celda($f[1], 'porcentaje')], $filas),
        ];
    }

    private function seccionContratos(Proyecto $proyecto): array
    {
        $data = json_decode((new ContratoController())->index($proyecto)->getContent(), true);

        $filas = collect($data['contratos'])->map(fn ($c) => [
            $c['numero'],
            $c['tipo_contrato'],
            $c['contratista'],
            $c['numero_minuta'],
            $this->fecha($c['fecha_firma_contrato']),
            $this->fecha($c['fecha_orden_proceder']),
            (float) $c['monto_vigente'],
            (float) $c['anticipo'],
            (float) $c['monto_ejecutado_acumulado'],
            (float) $c['saldo_por_pagar'],
            $c['estado_contractual'],
            $this->fecha($c['fecha_conclusion_prevista']),
            $this->fecha($c['fecha_entrega_provisional']),
            $this->fecha($c['fecha_entrega_definitiva']),
            (float) $c['avance_fisico'],
            (float) $c['avance_financiero'],
            $c['activo'] ? 'Sí' : 'No',
        ])->all();

        return [
            'encabezados' => [
                'N°', 'Tipo', 'Contratista', 'N° Minuta', 'Fecha Firma', 'Orden Proceder',
                'Monto Vigente (Bs)', 'Anticipo (Bs)', 'Ejecutado Acum. (Bs)', 'Saldo por Pagar (Bs)',
                'Estado Contractual', 'Concl. Prevista', 'Entrega Provisional', 'Entrega Definitiva',
                'Av. Físico (%)', 'Av. Financiero (%)', 'Activo',
            ],
            'tipos' => [
                'entero', 'texto', 'texto', 'texto', 'texto', 'texto',
                'monto', 'monto', 'monto', 'monto',
                'texto', 'texto', 'texto', 'texto',
                'porcentaje', 'porcentaje', 'texto',
            ],
            'filas' => $filas,
        ];
    }

    private function seccionModificaciones(Proyecto $proyecto): array
    {
        $contratos = $proyecto->contratos()->orderBy('numero')->get();
        $controller = new ModificacionContractualController();

        $filas = $contratos->flatMap(function ($contrato) use ($controller) {
            $modificaciones = json_decode($controller->index($contrato)->getContent(), true);

            return collect($modificaciones)->map(fn ($m) => [
                'N° ' . $contrato->numero . ' — ' . $contrato->contratista,
                $m['numero'],
                $m['tipo_modificacion'],
                $m['numero_documento_modificatorio'],
                $this->fecha($m['fecha_firma_documento']),
                $this->fecha($m['nueva_fecha_conclusion']),
                $m['plazo_modificado_dias'],
                $m['monto_modificacion'] !== null ? (float) $m['monto_modificacion'] : null,
                $m['estado_documento'],
                $m['descripcion'],
            ]);
        })->all();

        return [
            'encabezados' => ['Contrato', 'N°', 'Tipo', 'N° Documento', 'Fecha Firma Doc.', 'Nueva Fecha Conclusión', 'Plazo Modificado (días)', 'Monto Modificación (Bs)', 'Estado', 'Descripción'],
            'tipos' => ['texto', 'entero', 'texto', 'texto', 'texto', 'texto', 'entero', 'monto', 'texto', 'texto'],
            'filas' => $filas,
        ];
    }

    private function seccionPlanillas(Proyecto $proyecto): array
    {
        $contratos = $proyecto->contratos()->orderBy('numero')->get();
        $controller = new PlanillaController();

        $filas = $contratos->flatMap(function ($contrato) use ($controller) {
            $planillas = json_decode($controller->index($contrato)->getContent(), true);

            return collect($planillas)->map(fn ($p) => [
                'N° ' . $contrato->numero . ' — ' . $contrato->contratista,
                $p['numero'],
                $this->fecha($p['periodo_desde']),
                $this->fecha($p['periodo_hasta']),
                (float) $p['monto_certificado'],
                (float) $p['liquido_pagable'],
                (float) $p['importe_pagado_sigep'],
                (float) $p['diferencia_lp_f'],
                $p['avance_fisico'] !== null ? (float) $p['avance_fisico'] : null,
                $p['numero_c31'],
                $this->fecha($p['fecha_desembolso']),
            ]);
        })->all();

        return [
            'encabezados' => ['Contrato', 'N°', 'Periodo Desde', 'Periodo Hasta', 'Monto Certificado (Bs)', 'Líquido Pagable (Bs)', 'Pagado SIGEP (Bs)', 'Diferencia (Bs)', 'Av. Físico (%)', 'N° C-31', 'Fecha Desembolso'],
            'tipos' => ['texto', 'entero', 'texto', 'texto', 'monto', 'monto', 'monto', 'monto', 'porcentaje', 'texto', 'texto'],
            'filas' => $filas,
        ];
    }

    private function seccionDecretos(Proyecto $proyecto): array
    {
        $data = json_decode((new DecretoSupremoController())->index($proyecto)->getContent(), true);

        $filas = collect($data['decretos'])->map(fn ($d) => [
            $d['numero'],
            $d['numero_decreto'],
            (float) $d['monto_inicial'],
            (float) $d['incremento'],
            (float) $d['monto_vigente'],
            (float) $d['monto_puesta_marcha_insumos'],
            (float) $d['monto_auditoria_interna'],
        ])->all();

        return [
            'encabezados' => ['N°', 'N° Decreto', 'Monto Inicial (Bs)', 'Incremento (Bs)', 'Monto Vigente (Bs)', 'Puesta en Marcha / Insumos (Bs)', 'Auditoría Interna (Bs)'],
            'tipos' => ['entero', 'texto', 'monto', 'monto', 'monto', 'monto', 'monto'],
            'filas' => $filas,
        ];
    }

    private function seccionFinanciero(Proyecto $proyecto): array
    {
        $partidas = json_decode((new ProgramacionFinancieraController())->index($proyecto)->getContent(), true);

        $filas = collect($partidas)->flatMap(function ($partida, $indice) {
            $objetos = $partida['objetos'] ?: [['codigo_objeto' => null, 'descripcion' => null, 'total_anual' => null, 'monto_ejecutado' => null, 'saldo_por_ejecutar' => null, 'cumplimiento_financiero' => null]];

            return collect($objetos)->map(fn ($o) => [
                'Partida ' . ($indice + 1),
                (float) $partida['presupuesto_aprobado'],
                $o['codigo_objeto'],
                $o['descripcion'],
                $o['total_anual'] !== null ? (float) $o['total_anual'] : null,
                $o['monto_ejecutado'] !== null ? (float) $o['monto_ejecutado'] : null,
                $o['saldo_por_ejecutar'] !== null ? (float) $o['saldo_por_ejecutar'] : null,
                $o['cumplimiento_financiero'] !== null ? round($o['cumplimiento_financiero'] * 100, 2) : null,
            ]);
        })->all();

        return [
            'encabezados' => ['Partida', 'Presupuesto Partida (Bs)', 'Código Objeto', 'Descripción', 'Total Anual (Bs)', 'Ejecutado (Bs)', 'Saldo por Ejecutar (Bs)', 'Cumpl. Financiero (%)'],
            'tipos' => ['texto', 'monto', 'texto', 'texto', 'monto', 'monto', 'monto', 'porcentaje'],
            'filas' => $filas,
        ];
    }

    /* ============================================================
       EXCEL — hojas
    ============================================================ */

    private function hojaResumen($sheet, array $reporte): void
    {
        $sheet->setTitle('Resumen');

        $sheet->setCellValue('A1', 'REPORTE DEL PROYECTO');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D1F30']],
        ]);
        $sheet->mergeCells('A1:C1');
        $sheet->getRowDimension(1)->setRowHeight(26);

        $filas = [
            ['Código', $reporte['proyecto']['codigo']],
            ['Código SISINWEB', $reporte['proyecto']['numero_sisin_web'] ?? '—'],
            ['Nombre', $reporte['proyecto']['nombre']],
            ['Estado', $reporte['proyecto']['estado'] ?? '—'],
            ['Generado el', $reporte['generado_en']],
            ['Módulos incluidos', collect($reporte['secciones'])->pluck('titulo')->implode(', ')],
        ];

        $fila = 3;
        foreach ($filas as [$campo, $valor]) {
            $sheet->setCellValue("A{$fila}", $campo);
            $sheet->setCellValue("B{$fila}", $valor);
            $sheet->getStyle("A{$fila}")->getFont()->setBold(true);
            $fila++;
        }

        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(70);
    }

    private function hojaTabla($sheet, array $seccion): void
    {
        $sheet->setTitle(mb_substr($seccion['titulo'], 0, 31));

        $encabezados = $seccion['encabezados'];
        $tipos = $seccion['tipos'];
        $filas = $seccion['filas'];

        $totalColumnas = count($encabezados);
        $ultimaColumna = Coordinate::stringFromColumnIndex($totalColumnas);

        $sheet->setCellValue('A1', mb_strtoupper($seccion['titulo']));
        $sheet->mergeCells("A1:{$ultimaColumna}1");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D1F30']],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $filaEncabezados = 3;
        $sheet->fromArray($encabezados, null, "A{$filaEncabezados}");
        $sheet->getStyle("A{$filaEncabezados}:{$ultimaColumna}{$filaEncabezados}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A52']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B9CADB']]],
        ]);
        $sheet->getRowDimension($filaEncabezados)->setRowHeight(30);

        if (empty($filas)) {
            $filaVacia = $filaEncabezados + 1;
            $sheet->setCellValue("A{$filaVacia}", 'Sin registros.');
            $sheet->mergeCells("A{$filaVacia}:{$ultimaColumna}{$filaVacia}");
            $sheet->getColumnDimension('A')->setWidth(30);
            return;
        }

        $fila = $filaEncabezados + 1;
        foreach ($filas as $datosFila) {
            $sheet->fromArray($datosFila, null, "A{$fila}");
            if (($fila - $filaEncabezados) % 2 === 0) {
                $sheet->getStyle("A{$fila}:{$ultimaColumna}{$fila}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F6FA']],
                ]);
            }
            $fila++;
        }
        $filaFinDatos = $fila - 1;

        $sheet->getStyle("A{$filaEncabezados}:{$ultimaColumna}{$filaFinDatos}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D3DEE8']]],
        ]);
        $sheet->getStyle("A{$filaEncabezados}:{$ultimaColumna}{$filaFinDatos}")
            ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        foreach ($tipos as $indice => $tipo) {
            $col = Coordinate::stringFromColumnIndex($indice + 1);
            $rango = "{$col}" . ($filaEncabezados + 1) . ":{$col}{$filaFinDatos}";

            if ($tipo === 'monto') {
                $sheet->getStyle($rango)->getNumberFormat()->setFormatCode('#,##0.00');
            } elseif ($tipo === 'porcentaje') {
                $sheet->getStyle($rango)->getNumberFormat()->setFormatCode('0.00"%"');
            } elseif ($tipo === 'texto') {
                $sheet->getStyle($rango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
            }

            $sheet->getColumnDimension($col)->setWidth($tipo === 'texto' ? 26 : 15);
        }

        $sheet->setAutoFilter("A{$filaEncabezados}:{$ultimaColumna}{$filaEncabezados}");
        $sheet->freezePane("A" . ($filaEncabezados + 1));
    }
}
