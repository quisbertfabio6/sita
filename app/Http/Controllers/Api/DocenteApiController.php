<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Asistencia;
use App\Models\Licencia;
use App\Models\Materia;
use App\Models\Estudiante;
use App\Models\Gestion;
use Carbon\Carbon;

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

    // 2. Registrar asistencia por QR, usando la hora real del escaneo.
    // Flutter envia fecha/hora de la captura, incluso cuando sincroniza sin conexion.
    public function registrarAsistencia(Request $request)
    {
        $datos = $request->validate([
            'codigo_qr' => 'required|string',
            'materia_id' => 'required|exists:materias,id',
            'fecha' => 'required|date_format:Y-m-d',
            'hora' => 'required|date_format:H:i,H:i:s',
        ]);

        $materia = Materia::findOrFail($datos['materia_id']);
        $docente = $request->user()?->docente;

        if (!$docente || !$docente->materias()->where('materias.id', $materia->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Materia no asignada al docente.',
            ], 403);
        }

        $gestionActiva = Gestion::where('es_activa', true)->first();
        if (!$gestionActiva) {
            return response()->json([
                'success' => false,
                'message' => 'No hay una gestion academica activa.',
            ], 422);
        }

        // En esta etapa solo se registran asistencias de la gestion activa.
        if (substr($datos['fecha'], 0, 4) !== (string) $gestionActiva->anio) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha no corresponde a la gestion activa.',
            ], 422);
        }

        $estudiante = Estudiante::where('codigo_qr', $datos['codigo_qr'])->first();
        if (!$estudiante) {
            return response()->json([
                'success' => false,
                'message' => 'QR no valido.',
            ], 404);
        }

        // Evita registrar asistencia en otra materia o de alumnos retirados.
        $inscrito = $estudiante->inscripciones()
            ->where('curso_id', $materia->curso_id)
            ->where('gestion_id', $gestionActiva->id)
            ->where('estado', 'Activo')
            ->exists();

        if (!$inscrito) {
            return response()->json([
                'success' => false,
                'message' => 'El estudiante no esta inscrito en este curso durante la gestion activa.',
            ], 422);
        }

        $tieneLicencia = Licencia::where('estudiante_id', $estudiante->id)
            ->where('estado', 'aprobada')
            ->whereDate('fecha_inicio', '<=', $datos['fecha'])
            ->whereDate('fecha_fin', '>=', $datos['fecha'])
            ->exists();

        if ($tieneLicencia) {
            return response()->json([
                'success' => false,
                'message' => 'El estudiante tiene licencia justificada en esa fecha.',
            ], 409);
        }

        if (!$materia->hora_inicio) {
            return response()->json([
                'success' => false,
                'message' => 'La materia no tiene horario de inicio configurado.',
            ], 422);
        }

        // Comparar horas de la MISMA fecha, no Carbon::now() del servidor.
        // Si el QR se escaneo offline a las 08:05 y se sincroniza a las 11:00,
        // se mantiene la hora original 08:05 para calcular el estado.
        $momentoRegistro = Carbon::parse($datos['fecha'] . ' ' . $datos['hora']);
        $limitePuntualidad = Carbon::parse($datos['fecha'] . ' ' . $materia->hora_inicio)
            ->addMinutes((int) $materia->minutos_tolerancia);

        $estadoCalculado = $momentoRegistro->greaterThan($limitePuntualidad)
            ? 'atraso'
            : 'presente';

        DB::transaction(function () use ($materia, $estudiante, $datos, $momentoRegistro, $estadoCalculado) {
            Materia::whereKey($materia->id)->lockForUpdate()->firstOrFail();
            Asistencia::updateOrCreate(
                [
                    'estudiante_id' => $estudiante->id,
                    'materia_id' => $materia->id,
                    'fecha' => $datos['fecha'],
                ],
                [
                    'hora' => $momentoRegistro->format('H:i:s'),
                    'estado' => $estadoCalculado,
                    'observacion' => null,
                    'es_sincronizado' => true,
                ]
            );
        }, 3);

        return response()->json([
            'success' => true,
            'message' => 'Asistencia registrada',
            'alumno' => $estudiante->usuario->nombre_completo,
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

    // 4. Registro manual: verificar docente, materia y alumno antes de modificar asistencia.
    public function registrarManual(Request $request)
    {
        $datos = $request->validate([
            'estudiante_id' => 'required|integer|exists:estudiantes,id',
            'materia_id' => 'required|integer|exists:materias,id',
            'estado' => 'required|in:presente,atraso,falta',
        ]);

        $materia = Materia::findOrFail($datos['materia_id']);
        $docente = $request->user()?->docente;

        if (!$docente || !$docente->materias()->where('materias.id', $materia->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Materia no asignada al docente.',
            ], 403);
        }

        $gestionActiva = Gestion::where('es_activa', true)->first();
        if (!$gestionActiva) {
            return response()->json([
                'success' => false,
                'message' => 'No hay una gestion academica activa.',
            ], 422);
        }

        $fecha = now()->toDateString();
        if (substr($fecha, 0, 4) !== (string) $gestionActiva->anio) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha no corresponde a la gestion activa.',
            ], 422);
        }

        $inscrito = Estudiante::whereKey($datos['estudiante_id'])
            ->whereHas('inscripciones', function ($consulta) use ($materia, $gestionActiva) {
                $consulta->where('curso_id', $materia->curso_id)
                    ->where('gestion_id', $gestionActiva->id)
                    ->where('estado', 'Activo');
            })
            ->exists();

        if (!$inscrito) {
            return response()->json([
                'success' => false,
                'message' => 'El estudiante no esta inscrito en este curso durante la gestion activa.',
            ], 422);
        }

        $tieneLicencia = Licencia::where('estudiante_id', $datos['estudiante_id'])
            ->where('estado', 'aprobada')
            ->whereDate('fecha_inicio', '<=', $fecha)
            ->whereDate('fecha_fin', '>=', $fecha)
            ->exists();

        if ($tieneLicencia) {
            return response()->json([
                'success' => false,
                'message' => 'El estudiante tiene licencia justificada en esa fecha.',
            ], 409);
        }

        DB::transaction(function () use ($materia, $datos, $fecha) {
            Materia::whereKey($materia->id)->lockForUpdate()->firstOrFail();
            Asistencia::updateOrCreate(
                [
                    'estudiante_id' => $datos['estudiante_id'],
                    'materia_id' => $materia->id,
                    'fecha' => $fecha,
                ],
                [
                    'hora' => now()->format('H:i:s'),
                    'estado' => $datos['estado'],
                    'observacion' => null,
                    'es_sincronizado' => true,
                ]
            );
        }, 3);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado',
        ]);
    }


    // Finalizar la asistencia del dia: solo completar registros inexistentes.
    // No bloquear correcciones posteriores ni escaneos offline sincronizados tarde.
    public function finalizarAsistencia(Request $request)
    {
        $datos = $request->validate([
            'materia_id' => 'required|integer|exists:materias,id',
            'fecha' => 'required|date_format:Y-m-d',
        ]);

        $docente = $request->user()?->docente;
        if (!$docente || !$docente->materias()->where('materias.id', $datos['materia_id'])->exists()) {
            return response()->json(['success' => false, 'message' => 'Materia no asignada al docente.'], 403);
        }

        $fecha = $datos['fecha'];
        $ahora = now('America/La_Paz');
        if ($fecha !== $ahora->toDateString()) {
            return response()->json(['success' => false, 'message' => 'Solo se puede finalizar la asistencia de hoy.'], 422);
        }

        return DB::transaction(function () use ($datos, $fecha, $ahora) {
            // Serializar el cierre con los registros QR y manuales de esta materia.
            $materia = Materia::whereKey($datos['materia_id'])->lockForUpdate()->firstOrFail();
            $gestion = Gestion::where('es_activa', true)->first();
            if (!$gestion || $gestion->anio !== substr($fecha, 0, 4)) {
                return response()->json(['success' => false, 'message' => 'La fecha no corresponde a una gestion activa.'], 422);
            }

            $ids = Estudiante::whereHas('inscripciones', function ($consulta) use ($materia, $gestion) {
                $consulta->where('curso_id', $materia->curso_id)
                    ->where('gestion_id', $gestion->id)
                    ->where('estado', 'Activo');
            })->pluck('id');

            if ($ids->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No hay estudiantes inscritos en esta materia.'], 422);
            }

            $registrados = Asistencia::where('materia_id', $materia->id)
                ->where('fecha', $fecha)->whereIn('estudiante_id', $ids)
                ->pluck('estudiante_id');
            $faltantes = $ids->diff($registrados);

            // Bloquear solicitudes de esa fecha mientras se comprueba su estado.
            $licencias = Licencia::whereIn('estudiante_id', $faltantes)
                ->whereDate('fecha_inicio', '<=', $fecha)
                ->whereDate('fecha_fin', '>=', $fecha)
                ->lockForUpdate()->get()
                ->where('estado', 'aprobada')->keyBy('estudiante_id');

            $faltas = 0;
            $justificadas = 0;
            foreach ($faltantes as $id) {
                $licencia = $licencias->get($id);
                Asistencia::create([
                    'estudiante_id' => $id,
                    'materia_id' => $materia->id,
                    'fecha' => $fecha,
                    'hora' => $ahora->format('H:i:s'),
                    'estado' => $licencia ? 'licencia' : 'falta',
                    'observacion' => $licencia
                        ? 'Justificado por licencia #' . $licencia->id
                        : 'Falta generada al finalizar asistencia',
                    'es_sincronizado' => true,
                ]);
                $licencia ? $justificadas++ : $faltas++;
            }

            return response()->json([
                'success' => true,
                'message' => 'Asistencia finalizada correctamente.',
                'faltas_generadas' => $faltas,
                'licencias_registradas' => $justificadas,
                'registros_conservados' => $ids->count() - $faltantes->count(),
            ]);
        }, 3);
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
