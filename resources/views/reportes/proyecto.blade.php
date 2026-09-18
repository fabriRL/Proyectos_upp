<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 8px; color: #222; }
        h1 { font-size: 15px; margin-bottom: 2px; }
        .subtitulo { color: #666; font-size: 9px; margin-bottom: 14px; }
        .seccion { margin-bottom: 16px; page-break-inside: avoid; }
        .seccion-titulo {
            background: #0d1f30; color: #fff; font-size: 10px; font-weight: bold;
            padding: 5px 8px; text-transform: uppercase; letter-spacing: .03em;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 3px 5px; text-align: center; }
        th { background: #e4ecf3; font-size: 7px; text-transform: uppercase; }
        td.izquierda, th.izquierda { text-align: left; }
        .sin-registros { padding: 8px; color: #888; font-style: italic; text-align: center; border: 1px solid #ccc; border-top: none; }
        .kv td:first-child { text-align: left; font-weight: bold; background: #f5f8fb; width: 28%; }
        .kv td:last-child { text-align: left; }
    </style>
</head>
<body>
    <h1>Reporte del Proyecto</h1>
    <div class="subtitulo">
        {{ $proyecto['codigo'] }} — {{ $proyecto['nombre'] }}
        &nbsp;·&nbsp; Generado el {{ $generado_en }}
    </div>

    @foreach ($secciones as $seccion)
        @php
            $esKv = $seccion['encabezados'] === ['Campo', 'Valor'] || $seccion['encabezados'] === ['Indicador', 'Valor'];
        @endphp
        <div class="seccion">
            <div class="seccion-titulo">{{ $seccion['titulo'] }}</div>

            @if (empty($seccion['filas']))
                <div class="sin-registros">Sin registros para este módulo.</div>
            @else
                <table class="{{ $esKv ? 'kv' : '' }}">
                    @unless ($esKv)
                        <thead>
                            <tr>
                                @foreach ($seccion['encabezados'] as $i => $encabezado)
                                    <th class="{{ $seccion['tipos'][$i] === 'texto' ? 'izquierda' : '' }}">{{ $encabezado }}</th>
                                @endforeach
                            </tr>
                        </thead>
                    @endunless
                    <tbody>
                        @foreach ($seccion['filas'] as $fila)
                            <tr>
                                @foreach ($fila as $i => $valor)
                                    <td class="{{ $seccion['tipos'][$i] === 'texto' ? 'izquierda' : '' }}">
                                        {{ \App\Http\Controllers\Api\ReporteProyectoController::celda($valor, $seccion['tipos'][$i]) }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach
</body>
</html>
