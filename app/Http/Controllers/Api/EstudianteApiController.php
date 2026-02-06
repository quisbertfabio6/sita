<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; // Necesario para borrar archivos
use App\Models\Asistencia;
use App\Models\Licencia;
use App\Models\Estudiante;

class EstudianteApiController extends Controller
{
    // 1. Dashboard Principal (Resumen para la App)
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $estudiante = $user->estudiante;

        if (!$estudiante) {
            return response()->json(['message' => 'Perfil no encontrado'], 403);
        }

        // Contadores globales
        $asistencias = Asistencia::where('estudiante_id', $estudiante->id)->where('estado', 'presente')->count();
        $faltas = Asistencia::where('estudiante_id', $estudiante->id)->where('estado', 'falta')->count();
        $licencias = Licencia::where('estudiante_id', $estudiante->id)->count();

        // Calcular Porcentaje Global
        $totalRegistros = $asistencias + $faltas;
        $porcentaje = 100; 
        
        if ($totalRegistros > 0) {
            $porcentaje = round(($asistencias / $totalRegistros) * 100);
        }

        // Últimas 3 licencias para vista rápida
        $ultimasLicencias = Licencia::where('estudiante_id', $estudiante->id)
                                    ->orderBy('created_at', 'desc')
                                    ->take(3)
                                    ->get();

        return response()->json([
            'success' => true,
            'resumen' => [
                'asistencias' => $asistencias,
                'faltas' => $faltas,
                'licencias' => $licencias,
                'porcentaje' => $porcentaje
            ],
            'mis_licencias' => $ultimasLicencias
        ]);
    }

    // 2. Historial completo de Licencias
    public function misLicencias(Request $request)
    {
        $estudiante = $request->user()->estudiante;

        if (!$estudiante) {
            return response()->json(['success' => false, 'message' => 'Perfil no encontrado']);
        }

        $licencias = Licencia::where('estudiante_id', $estudiante->id)
                            ->orderBy('created_at', 'desc')
                            ->get();

        return response()->json([
            'success' => true,
            'data' => $licencias
        ]);
    }

    // 3. Anular (Eliminar) licencia pendiente
    public function anularLicencia(Request $request, $id)
    {
        $estudiante = $request->user()->estudiante;

        // Buscar la licencia y verificar que sea de este estudiante
        $licencia = Licencia::where('id', $id)
                        ->where('estudiante_id', $estudiante->id)
                        ->first();

        if (!$licencia) {
            return response()->json(['success' => false, 'message' => 'Licencia no encontrada'], 404);
        }

        // REGLA DE ORO: Solo se puede borrar si está PENDIENTE
        if ($licencia->estado != 'pendiente') {
            return response()->json(['success' => false, 'message' => 'No se puede anular: Ya fue procesada.'], 403);
        }

        // Borrar archivo adjunto del servidor si existe
        if ($licencia->archivo_adjunto && Storage::disk('public')->exists($licencia->archivo_adjunto)) {
            Storage::disk('public')->delete($licencia->archivo_adjunto);
        }

        // Borrar registro de la BD
        $licencia->delete();

        return response()->json(['success' => true, 'message' => 'Solicitud anulada correctamente']);
    }

    // 4. Detalle de Materias con Porcentajes Ponderados
    public function misMaterias(Request $request)
    {
        $estudiante = $request->user()->estudiante;

        // Obtenemos las materias del curso del estudiante
        $materias = $estudiante->curso->materias;

        $data = $materias->map(function($materia) use ($estudiante) {
            
            // A. Total de clases dictadas en esa materia
            $totalClases = \App\Models\Asistencia::where('materia_id', $materia->id)
                            ->distinct('fecha')
                            ->count();
            
            if ($totalClases == 0) $totalClases = 1;

            // B. Puntos del estudiante (Nueva Lógica)
            $asistencias = \App\Models\Asistencia::where('estudiante_id', $estudiante->id)
                            ->where('materia_id', $materia->id)
                            ->get();

            $puntos = 0;
            foreach ($asistencias as $asis) {
                if ($asis->estado == 'presente' || $asis->estado == 'licencia') {
                    $puntos += 1;
                } elseif ($asis->estado == 'atraso') {
                    $puntos += 0.5;
                }
                // Falta suma 0
            }

            $porcentaje = round(($puntos / $totalClases) * 100);
            if ($porcentaje > 100) $porcentaje = 100;

            return [
                'id' => $materia->id,
                'nombre' => $materia->nombre,
                'sigla' => $materia->sigla,
                'asistencias_puntos' => $puntos,
                'total_clases' => $totalClases,
                'porcentaje' => $porcentaje,
                'riesgo' => $porcentaje < 80 // Alerta si baja del 80%
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}