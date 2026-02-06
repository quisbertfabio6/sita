<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Asistencia por Rango</title>
    <style>
        /* Estilos básicos para que DOMPDF los entienda */
        body { font-family: sans-serif; font-size: 11px; margin: 25px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #D32F2F; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #D32F2F; font-size: 20px; }
        .header p { margin: 0; font-size: 14px; }
        
        .info-box { width: 100%; margin-bottom: 20px; font-size: 12px; }
        .info-box td { padding: 4px; font-weight: bold; }
        
        table.lista { width: 100%; border-collapse: collapse; }
        table.lista th, table.lista td { border: 1px solid #999; padding: 6px; text-align: left; }
        table.lista th { background-color: #f2f2f2; text-align: center; }
        
        .rojo { color: red; font-weight: bold; }
        .verde { color: green; }
        
        .footer { position: fixed; bottom: -30px; width: 100%; text-align: center; font-size: 9px; color: #777; }
        .firmas { margin-top: 60px; width: 100%; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>INSTITUTO TECNOLÓGICO AYACUCHO</h1>
        <p>REPORTE DE ASISTENCIA POR PERIODO</p>
    </div>

    <table class="info-box">
        <tr>
            <td>MATERIA: {{ $materia->nombre }}</td>
            <td>CURSO: {{ $materia->curso->nombre }}</td>
        </tr>
        <tr>
            <td>PERIODO: {{ $fechaInicio }} AL {{ $fechaFin }}</td>
            <td>TOTAL CLASES DICTADAS: {{ $totalClases }}</td>
        </tr>
    </table>

    <table class="lista">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th>Estudiante</th>
                <th>Matrícula</th>
                <th style="width: 60px;">Asist. (Pts)</th>
                <th style="width: 60px;">% Periodo</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos as $index => $alumno)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $alumno['nombre'] }}</td>
                <td>{{ $alumno['matricula'] }}</td>
                <td style="text-align: center;">{{ $alumno['asistencias_puntos'] }}</td>
                <td style="text-align: center;">{{ $alumno['porcentaje'] }}%</td>
                <td style="text-align: center;">
                    @if($alumno['riesgo'])
                        <span class="rojo">EN RIESGO</span>
                    @else
                        <span class="verde">REGULAR</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        Generado por Sistema SITA - {{ date('Y') }}
    </div>
</body>
</html>