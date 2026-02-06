<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Licencias</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; margin: 25px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #D32F2F; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #D32F2F; font-size: 18px; }
        
        .info-box { width: 100%; margin-bottom: 15px; font-size: 11px; }
        .info-box td { padding: 3px; }
        .info-box .label { font-weight: bold; }

        table.lista { width: 100%; border-collapse: collapse; }
        table.lista th, table.lista td { border: 1px solid #999; padding: 5px; text-align: left; }
        table.lista th { background-color: #f2f2f2; text-align: center; }
        
        .estado-aprobada { color: green; font-weight: bold; }
        .estado-rechazada { color: red; font-weight: bold; }
        .estado-pendiente { color: orange; font-weight: bold; }

        .footer { position: fixed; bottom: -30px; width: 100%; text-align: center; font-size: 9px; color: #777; }
    </style>
</head>
<body>
    <div class="header">
        <h1>INSTITUTO TECNOLÓGICO AYACUCHO</h1>
        <p>REPORTE DE LICENCIAS (JEFATURA)</p>
    </div>

    <table class="info-box">
        <tr>
            <td class="label">PERIODO:</td>
            <td>{{ $fecha_inicio }} al {{ $fecha_fin }}</td>
        </tr>
        <tr>
            <td class="label">ESTADO:</td>
            <td>{{ ucfirst($estado) }}</td>
        </tr>
        <tr>
            <td class="label">FECHA REPORTE:</td>
            <td>{{ date('d/m/Y') }}</td>
        </tr>
    </table>

    <table class="lista">
        <thead>
            <tr>
                <th style="width: 25px;">#</th>
                <th style="width: 130px;">Estudiante</th>
                <th style="width: 90px;">Curso</th>
                <th style="width: 60px;">Estado</th>
                <th>Motivo</th>
                <th>Observación (Jefatura)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($licencias as $index => $lic)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $lic->estudiante->usuario->nombre_completo }}</td>
                <td>{{ $lic->estudiante->curso->nombre }}</td>
                <td class="estado-{{$lic->estado}}">{{ ucfirst($lic->estado) }}</td>
                <td style="font-size: 9px;">{{ $lic->motivo }}</td>
                <td style="font-size: 9px;">{{ $lic->comentario_admin }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px;">No se encontraron licencias para este filtro.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generado por Sistema SITA - {{ date('Y') }}
    </div>
</body>
</html>