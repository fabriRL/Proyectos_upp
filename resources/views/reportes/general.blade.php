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
        th.grupo-contratos { background: #d7e8f7; }
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
                <th>N°</th><th>Código</th><th>Código SISINWEB</th><th>Proyecto</th>
                <th>Norma de Financiamiento</th><th>Monto D.S.</th>
                <th>Av. Físico (SISIN)</th><th>Av. Financiero (SISIN)</th>
                <th>Estado de Situación</th>
                <th>Inicio Contractual</th>
                <th>Entrega Prov.</th><th>Entrega Def.</th>
                <th>Plazo (Días)</th>
                <th class="grupo-contratos">Empresa Contratista</th>
                <th class="grupo-contratos">Monto Original Contratista</th>
                <th class="grupo-contratos">Monto Modif. Contratista</th>
                <th class="grupo-contratos">Empresa Supervisión</th>
                <th class="grupo-contratos">Monto Original Supervisión</th>
                <th class="grupo-contratos">Monto Modif. Supervisión</th>
                <th class="grupo-contratos">Orden Proceder</th>
                <th class="grupo-contratos">Concl. Prevista</th>
                <th class="grupo-contratos">Av. Infraestr.</th>
                <th class="grupo-contratos">Av. Equipam.</th>
                <th class="grupo-contratos">Av. Insumos/P.M.</th>
                <th class="grupo-contratos">Últ. Modificaciones</th>
                <th class="grupo-contratos">Últ. Acciones</th>
                <th class="grupo-contratos">Planilla Pend. (Contratista)</th>
                <th class="grupo-contratos">Monto Pend. Contratista</th>
                <th class="grupo-contratos">Planilla Pend. (Superv.)</th>
                <th class="grupo-contratos">Monto Pend. Superv.</th>
                <th class="grupo-contratos">Monto Req. hasta Conclusión</th>
                <th class="grupo-contratos">Increm. D.S. 5321</th>
                <th class="grupo-contratos">Anticipo D.S. 5406</th>
                <th class="grupo-contratos">Presup. Gestión</th>
                <th class="grupo-contratos">SIGEP</th>
                <th>Problemas</th><th>Acciones</th><th>Líneas/Capacidades</th>
                <th>Result. Impacto Socioecon.</th><th>Observaciones</th>
                <th>Días Rest.</th><th>Semáforo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($proyectos as $p)
            <tr>
                <td>{{ $p['n'] }}</td>
                <td>{{ $p['codigo'] }}</td>
                <td>{{ $p['numero_sisin_web'] ?? '—' }}</td>
                <td class="izquierda">{{ $p['nombre'] }}</td>
                <td class="izquierda">{{ $p['norma_financiamiento'] ?? '—' }}</td>
                <td>{{ number_format($p['monto_decreto_vigente'], 2) }}</td>
                <td>{{ $p['avance_fisico'] }}%</td>
                <td>{{ $p['avance_financiero'] }}%</td>
                <td>{{ $p['estado_general'] }}</td>
                <td>{{ $p['fecha_inicio_contractual'] ?? '—' }}</td>
                <td>{{ $p['fecha_entrega_provisional'] ?? '—' }}</td>
                <td>{{ $p['fecha_entrega_definitiva'] ?? '—' }}</td>
                <td>{{ $p['plazo_dias'] ?? '—' }}</td>
                <td class="izquierda">{{ $p['contratistas'] }}</td>
                <td>{{ number_format($p['monto_original'], 2) }}</td>
                <td>{{ number_format($p['monto_modificaciones'], 2) }}</td>
                <td class="izquierda">{{ $p['empresa_supervision'] ?? '—' }}</td>
                <td>{{ number_format($p['monto_original_supervision'], 2) }}</td>
                <td>{{ number_format($p['monto_modificaciones_supervision'], 2) }}</td>
                <td>{{ $p['fecha_orden_proceder'] ?? '—' }}</td>
                <td>{{ $p['fecha_conclusion_prevista'] ?? '—' }}</td>
                <td>{{ $p['avance_fisico_infraestructura'] ?? '—' }}</td>
                <td>{{ $p['avance_fisico_equipamiento'] ?? '—' }}</td>
                <td>{{ $p['avance_insumos_puesta_marcha'] ?? '—' }}</td>
                <td class="izquierda">{{ $p['ultimas_modificaciones'] ?? '—' }}</td>
                <td class="izquierda">{{ $p['ultimas_acciones'] ?? '—' }}</td>
                <td class="izquierda">{{ $p['descripcion_planilla_pendiente_contratista'] ?? '—' }}</td>
                <td>{{ number_format($p['monto_planilla_pendiente_contratista'] ?? 0, 2) }}</td>
                <td class="izquierda">{{ $p['descripcion_planilla_pendiente_supervision'] ?? '—' }}</td>
                <td>{{ number_format($p['monto_planilla_pendiente_supervision'] ?? 0, 2) }}</td>
                <td>{{ number_format($p['monto_requerido_hasta_conclusion'] ?? 0, 2) }}</td>
                <td>{{ $p['incremento_ds_5321'] !== null ? number_format($p['incremento_ds_5321'], 2) : '—' }}</td>
                <td>{{ $p['anticipo_adicional_ds_5406'] ?? '—' }}</td>
                <td>{{ number_format($p['presupuesto_gestion_actual'] ?? 0, 2) }}</td>
                <td>{{ $p['tiene_sigep'] === true ? 'Sí' : ($p['tiene_sigep'] === false ? 'No' : '—') }}</td>
                <td class="izquierda">{{ $p['problemas'] ?? '—' }}</td>
                <td class="izquierda">{{ $p['acciones'] ?? '—' }}</td>
                <td class="izquierda">{{ $p['lineas_capacidades'] ?? '—' }}</td>
                <td class="izquierda">{{ $p['resultado_impacto_socioeconomico'] ?? '—' }}</td>
                <td class="izquierda">{{ $p['observaciones'] ?? '—' }}</td>
                <td>{{ $p['dias_restantes'] ?? '—' }}</td>
                <td style="color: {{ $p['semaforo']['color'] }}; font-weight: bold;">{{ $p['semaforo']['texto'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
