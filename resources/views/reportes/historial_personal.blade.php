<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial Personal de Asistencia</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; margin: 25px; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #D32F2F; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #D32F2F; font-size: 20px; }
        
        .info-box { width: 100%; margin-bottom: 20px; font-size: 12px; border: 1px solid #ccc; padding: 10px; border-radius: 5px; }
        .info-box td { padding: 4px; }
        .info-box .label { font-weight: bold; color: #333; }

        .materia-bloque { margin-bottom: 20px; page-break-inside: avoid; }
        .materia-header { background-color: #f2f2f2; padding: 8px; border-bottom: 1px solid #ccc; font-weight: bold; font-size: 14px; }

        table.lista { width: 100%; border-collapse: collapse; margin-top: 5px; }
        table.lista th, table.lista td { border: 1px solid #ddd; padding: 6px; text-align: center; }
        table.lista th { background-color: #fafafa; }
        
        .rojo { color: red; font-weight: bold; }
        .verde { color: green; }
        
        .footer { position: fixed; bottom: -30px; width: 100%; text-align: center; font-size: 9px; color: #777; }
    </style>
</head>
<body>
    <div class="header">
        <h1>INSTITUTO TECNOLÓGICO AYACUCHO</h1>
        <p>HISTORIAL PERSONAL DE ASISTENCIA</p>
    </div>

    <table class="info-box">
        <tr>
            <td class="label" style="width: 100px;">ESTUDIANTE:</td>
            <td>{{ $estudiante->usuario->nombre_completo }}</td>
        </tr>
        <tr>
            <td class="label">CURSO:</td>
            <td>{{ $estudiante->curso->nombre }}</td>
        </tr>
        <tr>
            <td class="label">FECHA REPORTE:</td>
            <td>{{ date('d/m/Y') }}</td>
        </tr>
    </table>

    @foreach($datos as $materia)
        <div class="materia-bloque">
            <div class="materia-header">
                {{ $materia['materia_nombre'] }}
            </div>
            
            <table class="lista" style="margin-bottom: 10px;">
                <tr>
                    <th style="background-color: #f0f0f0;">Total Clases Dictadas</th>
                    <th style="background-color: #f0f0f0;">Puntos Acumulados</th>
                    <th style="background-color: #f0f0f0;">Porcentaje Final</th>
                    <th style="background-color: #f0f0f0;">Estado</th>
                </tr>
                <tr>
                    <td style="text-align: center;">{{ $materia['total_clases'] }}</td>
                    <td style="text-align: center;">{{ $materia['puntos'] }}</td>
                    <td style="text-align: center; font-size: 14px; font-weight: bold;">{{ $materia['porcentaje'] }}%</td>
                    <td style="text-align: center;">
                        @if($materia['riesgo'])
                            <span class="rojo">EN RIESGO</span>
                        @else
                            <span class="verde">REGULAR</span>
                        @endif
                    </td>
                </tr>
            </table>

            @if($materia['detalle']->count() > 0)
                <table class="lista">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Estado</th>
                            <th>Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($materia['detalle'] as $asistencia)
                        <tr>
                            <td>{{ $asistencia->fecha }}</td>
                            <td>{{ $asistencia->hora }}</td>
                            <td>{{ ucfirst($asistencia->estado) }}</td>
                            <td style="font-size: 9px; width: 40%;">{{ $asistencia->observacion }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="text-align: center; color: #777;">(Sin registros de asistencia para esta materia)</p>
            @endif
        </div>
    @endforeach

    <div class="footer">
        Generado por Sistema SITA - {{ date('Y') }}
    </div>
</body>
</html>