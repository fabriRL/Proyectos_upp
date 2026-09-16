<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 6px; color: #222; }
        h1 { font-size: 14px; margin-bottom: 2px; }
        .subtitulo { color: #666; font-size: 8px; margin-bottom: 12px; }
        .stats { display: flex; margin-bottom: 12px; }
        .stat-box { border: 1px solid #ccc; padding: 5px 10px; margin-right: 8px; }
        .stat-label { font-size: 7px; color: #888; text-transform: uppercase; }
        .stat-value { font-size: 13px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 3px 4px; text-align: center; }
        th { background: #f0f0f0; font-size: 5.5px; text-transform: uppercase; }
        td.izquierda { text-align: left; }
    </style>
</head>
<body>
    <h1>Reporte General de Proyectos</h1>
    <div class="subtitulo">Generado el {{ $generado_en }} — {{ $total_proyectos }} proyecto(s)</div>

    <div class="stats">
        <div class="stat-box">
            <div class="stat-label">Avance Físico General</div>
            <div class="stat-value">{{ $stats['avance_fisico'] }}%</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Avance Financiero General</div>
            <div class="stat-value">{{ $stats['avance_financiero'] }}%</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>N°</th><th>Código</th><th>Proyecto</th>
                <th>Entidad Ejec. / Supervisión</th><th>Contratistas</th>
                <th>Monto D.S.</th><th>Contrato Original</th><th>Según Modif.</th>
                <th>Orden Proceder</th><th>Concl. Prevista</th>
                <th>Av. Físico</th><th>Av. Financiero</th>
                <th>Av. Infraestr.</th><th>Av. Equipam.</th><th>Av. Insumos/P.M.</th>
                <th>Entrega Prov.</th><th>Entrega Def.</th>
                <th>Últ. Modificaciones</th><th>Últ. Acciones</th>
                <th>Planilla Pend. (Contratista)</th><th>Monto Pend. Contratista</th>
                <th>Planilla Pend. (Superv.)</th><th>Monto Pend. Superv.</th>
                <th>Increm. D.S. 5321</th><th>Anticipo D.S. 5406</th><th>SIGEP</th>
                <th>Problemas</th><th>Acciones</th><th>Líneas/Capacidades</th>
                <th>Días Rest.</th><th>Estado</th><th>Semáforo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($proyectos as $p)
            <tr>
                <td>{{ $p['n'] }}</td>
                <td>{{ $p['codigo'] }}</td>
                <td class="izquierda">{{ $p['nombre'] }}</td>
                <td class="izquierda">{{ $p['empresa_supervision'] ?? '—' }}</td>
                <td class="izquierda">{{ $p['contratistas'] }}</td>
                <td>{{ number_format($p['monto_decreto_vigente'], 2) }}</td>
                <td>{{ number_format($p['monto_original'], 2) }}</td>
                <td>{{ number_format($p['monto_modificaciones'], 2) }}</td>
                <td>{{ $p['fecha_orden_proceder'] ?? '—' }}</td>
                <td>{{ $p['fecha_conclusion_prevista'] ?? '—' }}</td>
                <td>{{ $p['avance_fisico'] }}%</td>
                <td>{{ $p['avance_financiero'] }}%</td>
                <td>{{ $p['avance_fisico_infraestructura'] ?? '—' }}</td>
                <td>{{ $p['avance_fisico_equipamiento'] ?? '—' }}</td>
                <td>{{ $p['avance_insumos_puesta_marcha'] ?? '—' }}</td>
                <td>{{ $p['fecha_entrega_provisional'] ?? 'En proceso' }}</td>
                <td>{{ $p['fecha_entrega_definitiva'] ?? 'En proceso' }}</td>
                <td class="izquierda">{{ $p['ultimas_modificaciones'] ?? '—' }}</td>
                <td class="izquierda">{{ $p['ultimas_acciones'] ?? '—' }}</td>
                <td class="izquierda">{{ $p['descripcion_planilla_pendiente_contratista'] ?? '—' }}</td>
                <td>{{ $p['monto_planilla_pendiente_contratista'] ?? '—' }}</td>
                <td class="izquierda">{{ $p['descripcion_planilla_pendiente_supervision'] ?? '—' }}</td>
                <td>{{ $p['monto_planilla_pendiente_supervision'] ?? '—' }}</td>
                <td>{{ $p['incremento_ds_5321'] ?? '—' }}</td>
                <td>{{ $p['anticipo_adicional_ds_5406'] ?? '—' }}</td>
                <td>{{ $p['tiene_sigep'] ?? '—' }}</td>
                <td class="izquierda">{{ $p['problemas'] ?? '—' }}</td>
                <td class="izquierda">{{ $p['acciones'] ?? '—' }}</td>
                <td class="izquierda">{{ $p['lineas_capacidades'] ?? '—' }}</td>
                <td>{{ $p['dias_restantes'] ?? '—' }}</td>
                <td>{{ $p['estado_general'] }}</td>
                <td style="color: {{ $p['semaforo']['color'] }}; font-weight: bold;">{{ $p['semaforo']['texto'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>