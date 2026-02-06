<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Asistencia;
use App\Models\Licencia;
use App\Models\Materia;
use App\Models\Estudiante;

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

        // Guardar asistencia
        Asistencia::updateOrCreate(
            [
                'estudiante_id' => $estudiante->id,
                'materia_id' => $request->materia_id,
                'fecha' => $request->fecha,
            ],
            [
                'hora' => $request->hora,
                'estado' => 'presente',
                'es_sincronizado' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Asistencia registrada',
            'alumno' => $estudiante->usuario->nombre_completo
        ]);
    }

// 3. Obtener lista INTELIGENTE (Versión Blindada)
public function listaEstudiantesConEstadistica(Request $request)
{
    $request->validate([
        'materia_id' => 'required|exists:materias,id',
    ]);

    $materiaId = $request->materia_id;
    $fecha = $request->fecha ?? date('Y-m-d'); // Hoy

    $materia = Materia::find($materiaId);
    
    // Estadísticas
    $totalClases = Asistencia::where('materia_id', $materiaId)->distinct('fecha')->count();
    if ($totalClases == 0) $totalClases = 1;

    $estudiantes = $materia->curso->estudiantes()->with('usuario')->get();

    $lista = $estudiantes->map(function($estudiante) use ($materiaId, $fecha, $totalClases) {
        
        // 1. Verificar si tiene LICENCIA APROBADA REAL (La autoridad máxima)
        $tieneLicenciaAprobada = Licencia::where('estudiante_id', $estudiante->id)
            ->where('estado', 'aprobada') // <--- Solo si el Jefe dijo SI
            ->whereDate('fecha_inicio', '<=', $fecha)
            ->whereDate('fecha_fin', '>=', $fecha)
            ->exists();

        // 2. Buscar qué hay registrado en la asistencia
        $asistenciaHoy = Asistencia::where('estudiante_id', $estudiante->id)
                            ->where('materia_id', $materiaId)
                            ->where('fecha', $fecha)
                            ->first();

        $estadoFinal = null;

        if ($tieneLicenciaAprobada) {
            // Si hay licencia aprobada, esto le gana a todo.
            $estadoFinal = 'licencia';
        } 
        elseif ($asistenciaHoy) {
            // Si NO tiene licencia aprobada, pero hay un registro...
            if ($asistenciaHoy->estado == 'licencia') {
                // ¡AQUÍ ESTÁ EL TRUCO! 
                // Si la asistencia dice 'licencia' pero arriba vimos que NO hay aprobación...
                // Significa que es un dato viejo o erróneo. Lo ignoramos.
                $estadoFinal = null; 
            } else {
                // Si es presente, falta o atraso, lo respetamos.
                $estadoFinal = $asistenciaHoy->estado;
            }
        }

        // C. Calcular Porcentaje
        $asistenciasTotales = Asistencia::where('estudiante_id', $estudiante->id)
                            ->where('materia_id', $materiaId)
                            ->get();

        $puntos = 0;
        foreach ($asistenciasTotales as $asis) {
            if ($asis->estado == 'presente' || ($asis->estado == 'licencia' && $tieneLicenciaAprobada)) { 
                // Solo sumamos puntos por licencia si sigue aprobada
                $puntos += 1;
            }
            elseif ($asis->estado == 'atraso') $puntos += 0.5;
        }
        
        $porcentaje = round(($puntos / $totalClases) * 100);
        if ($porcentaje > 100) $porcentaje = 100;

        return [
            'id' => $estudiante->id,
            'nombre' => $estudiante->usuario->nombre_completo,
            'matricula' => $estudiante->matricula,
            'estado_hoy' => $estadoFinal,
            'porcentaje' => $porcentaje,
            'riesgo' => $porcentaje < 80
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $lista
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

    // 5. OBTENER FECHAS DE CLASES
    public function obtenerFechasClases(Request $request) {
        $request->validate(['materia_id' => 'required|exists:materias,id']);

        $fechas = Asistencia::where('materia_id', $request->materia_id)
                    ->select('fecha')
                    ->distinct()
                    ->orderBy('fecha', 'desc')
                    ->pluck('fecha');

        return response()->json(['success' => true, 'data' => $fechas]);
    }
}