<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        .subtitulo { color: #666; font-size: 10px; margin-bottom: 16px; }
        .stats { display: flex; margin-bottom: 16px; }
        .stat-box { border: 1px solid #ccc; padding: 8px 14px; margin-right: 10px; }
        .stat-label { font-size: 9px; color: #888; text-transform: uppercase; }
        .stat-value { font-size: 18px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: center; }
        th { background: #f0f0f0; font-size: 10px; text-transform: uppercase; }
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
                <th>Avance Físico</th><th>Avance Financiero</th>
                <th>Días Restantes</th><th>Estado General</th><th>Semáforo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($proyectos as $p)
            <tr>
                <td>{{ $p['n'] }}</td>
                <td>{{ $p['codigo'] }}</td>
                <td class="izquierda">{{ $p['nombre'] }}</td>
                <td>{{ $p['avance_fisico'] }}%</td>
                <td>{{ $p['avance_financiero'] }}%</td>
                <td>{{ $p['dias_restantes'] ?? '—' }}</td>
                <td>{{ $p['estado_general'] }}</td>
                <td style="color: {{ $p['semaforo']['color'] }}; font-weight: bold;">{{ $p['semaforo']['texto'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>