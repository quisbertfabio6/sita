<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Asistencia;
use App\Models\Licencia;
use App\Models\Materia;
use App\Models\Estudiante;
use App\Models\Gestion;

class DocenteApiController extends Controller
{
    // 1. Obtener materias del docente
    public function misMaterias(Request $request)
    {
        $user = $request->user();
        $docente = $user->docente;

        if (!$docente) {
            return response()->json(['message' => 'No tienes perfil de docente'], 403);
        }

        $materias = $docente->materias()->with('curso')->get();

        return response()->json([
            'success' => true,
            'data' => $materias
        ]);
    }

    // 2. Registrar Asistencia por QR
    public function registrarAsistencia(Request $request)
    {
        $request->validate([
            'codigo_qr' => 'required|string',
            'materia_id' => 'required|exists:materias,id',
            'fecha' => 'required|date',
            'hora' => 'required',
        ]);

        $estudiante = Estudiante::where('codigo_qr', $request->codigo_qr)->first();

        if (!$estudiante) {
            return response()->json(['success' => false, 'message' => 'QR no válido.'], 404);
        }

        // Validar si tiene licencia aprobada para hoy
        $tieneLicencia = Licencia::where('estudiante_id', $estudiante->id)
            ->where('estado', 'aprobada')
            ->whereDate('fecha_inicio', '<=', $request->fecha)
            ->whereDate('fecha_fin', '>=', $request->fecha)
            ->exists();

        if ($tieneLicencia) {
            return response()->json(['success' => false, 'message' => 'El estudiante tiene licencia justificada hoy.'], 409);
        }

        // MANTENER TU CÓDIGO ANTERIOR AQUÍ (Validaciones, buscar al estudiante, etc.)

$materia = \App\Models\Materia::find($request->materia_id);
$estadoCalculado = 'presente'; // Estado por defecto

if ($materia && $materia->hora_inicio) {
    // Convertimos las horas usando la librería Carbon de Laravel
    $horaInicio = \Carbon\Carbon::parse($materia->hora_inicio);
    $horaTolerancia = $horaInicio->copy()->addMinutes($materia->minutos_tolerancia);
    $horaActual = \Carbon\Carbon::now();

    // Si la hora actual es mayor a la hora de tolerancia, es un atraso
    if ($horaActual->greaterThan($horaTolerancia)) {
        $estadoCalculado = 'atraso';
    }
}

        // Guardar asistencia
        Asistencia::updateOrCreate(
            [
                'estudiante_id' => $estudiante->id,
                'materia_id' => $request->materia_id,
                'fecha' => $request->fecha,
            ],
            [
                'hora' => $request->hora,
                'estado' => $estadoCalculado,
                'es_sincronizado' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Asistencia registrada',
            'alumno' => $estudiante->usuario->nombre_completo
        ]);
    }

// 3. Lista de control del docente: solo estudiantes de la gestion activa.
    public function listaEstudiantesConEstadistica(Request $request)
    {
        $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'fecha' => 'nullable|date_format:Y-m-d',
        ]);

        $materia = Materia::findOrFail($request->materia_id);
        $docente = $request->user()?->docente;

        if (!$docente || !$docente->materias()->where('materias.id', $materia->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Materia no asignada al docente.',
            ], 403);
        }

        $gestionActiva = Gestion::where('es_activa', true)->first();
        if (!$gestionActiva) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $fecha = $request->input('fecha') ?: now()->toDateString();
        $anio = $gestionActiva->anio;

        // La pantalla movil consulta la gestion actual, no el historial de otros anios.
        if (substr($fecha, 0, 4) !== $anio) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha no pertenece a la gestion academica activa.',
            ], 422);
        }

        $estudiantes = $materia->curso->estudiantes()
            ->wherePivot('gestion_id', $gestionActiva->id)
            ->wherePivot('estado', 'Activo')
            ->with('usuario')
            ->get();

        // Contar fechas distintas de la gestion activa, no registros individuales.
        $totalClases = Asistencia::where('materia_id', $materia->id)
            ->whereYear('fecha', $anio)
            ->distinct()
            ->count('fecha');

        // Consultas agrupadas para evitar una consulta por cada alumno.
        $ids = $estudiantes->pluck('id');
        $asistenciasPorEstudiante = Asistencia::where('materia_id', $materia->id)
            ->whereYear('fecha', $anio)
            ->whereIn('estudiante_id', $ids)
            ->get()
            ->groupBy('estudiante_id');

        $licenciasPorEstudiante = Licencia::where('estado', 'aprobada')
            ->whereIn('estudiante_id', $ids)
            ->whereDate('fecha_inicio', '<=', $anio . '-12-31')
            ->whereDate('fecha_fin', '>=', $anio . '-01-01')
            ->get()
            ->groupBy('estudiante_id');

        $lista = $estudiantes->map(function ($estudiante) use (
            $fecha,
            $totalClases,
            $asistenciasPorEstudiante,
            $licenciasPorEstudiante
        ) {
            $asistencias = $asistenciasPorEstudiante->get($estudiante->id, collect());
            $licencias = $licenciasPorEstudiante->get($estudiante->id, collect());

            $tieneLicenciaEnFecha = function ($dia) use ($licencias) {
                return $licencias->contains(function ($licencia) use ($dia) {
                    return $licencia->fecha_inicio <= $dia && $licencia->fecha_fin >= $dia;
                });
            };

            $asistenciaHoy = $asistencias->firstWhere('fecha', $fecha);
            if ($tieneLicenciaEnFecha($fecha)) {
                $estadoFinal = 'licencia';
            } elseif ($asistenciaHoy && $asistenciaHoy->estado !== 'licencia') {
                $estadoFinal = $asistenciaHoy->estado;
            } else {
                $estadoFinal = null;
            }

            $puntos = $asistencias->sum(function ($asistencia) use ($tieneLicenciaEnFecha) {
                if ($asistencia->estado === 'presente') {
                    return 1;
                }
                if ($asistencia->estado === 'atraso') {
                    return 0.5;
                }
                if ($asistencia->estado === 'licencia' && $tieneLicenciaEnFecha($asistencia->fecha)) {
                    return 1;
                }
                return 0;
            });

            $porcentaje = $totalClases > 0
                ? min(100, (int) round(($puntos / $totalClases) * 100))
                : 0;

            return [
                'id' => $estudiante->id,
                'nombre' => $estudiante->usuario->nombre_completo,
                'matricula' => $estudiante->matricula,
                'estado_hoy' => $estadoFinal,
                'porcentaje' => $porcentaje,
                'riesgo' => $totalClases > 0 && $porcentaje < 80,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $lista,
        ]);
    }

    // 4. Registrar cambio manual
    public function registrarManual(Request $request) {
        
        $fecha = date('Y-m-d');

        // Verificar licencia APROBADA antes de guardar falta
        if ($request->estado != 'licencia') {
             $tieneLicencia = Licencia::where('estudiante_id', $request->estudiante_id)
                ->where('estado', 'aprobada')
                ->whereDate('fecha_inicio', '<=', $fecha)
                ->whereDate('fecha_fin', '>=', $fecha)
                ->exists();

             if ($tieneLicencia) {
                 return response()->json(['success' => false, 'message' => 'Tiene licencia aprobada, no se puede cambiar.']);
             }
        }

        Asistencia::updateOrCreate(
            [
                'estudiante_id' => $request->estudiante_id,
                'materia_id' => $request->materia_id,
                'fecha' => $fecha,
            ],
            [
                'hora' => date('H:i:s'),
                'estado' => $request->estado,
                'es_sincronizado' => true
            ]
        );
        
        return response()->json(['success' => true, 'message' => 'Estado actualizado']);
    }

    // 5. Fechas registradas solo durante la gestion academica activa.
    public function obtenerFechasClases(Request $request)
    {
        $request->validate(['materia_id' => 'required|exists:materias,id']);

        $materia = Materia::findOrFail($request->materia_id);
        $docente = $request->user()?->docente;

        if (!$docente || !$docente->materias()->where('materias.id', $materia->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Materia no asignada al docente.',
            ], 403);
        }

        $gestionActiva = Gestion::where('es_activa', true)->first();
        if (!$gestionActiva) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $fechas = Asistencia::where('materia_id', $materia->id)
            ->whereYear('fecha', $gestionActiva->anio)
            ->select('fecha')
            ->distinct()
            ->orderBy('fecha', 'desc')
            ->pluck('fecha');

        return response()->json(['success' => true, 'data' => $fechas]);
    }
}
