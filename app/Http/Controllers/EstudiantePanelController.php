<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Asistencia;
use App\Models\Licencia;

class EstudiantePanelController extends Controller
{
    // =================================================
    //              MÉTODOS PARA LA WEB
    // =================================================

    // 1. Dashboard Principal (Web)
    public function index()
    {
        $estudiante = Auth::user()->estudiante;

        if (!$estudiante) {
            return view('estudiante.sin_perfil');
        }

        // Estadísticas
        $asistencias = Asistencia::where('estudiante_id', $estudiante->id)->where('estado', 'presente')->count();
        $faltas = Asistencia::where('estudiante_id', $estudiante->id)->where('estado', 'falta')->count();
        $atrasos = Asistencia::where('estudiante_id', $estudiante->id)->where('estado', 'atraso')->count();
        $licencias = Licencia::where('estudiante_id', $estudiante->id)->count();

        // Últimas 5 asistencias
        $ultimasAsistencias = Asistencia::with('materia')
                                ->where('estudiante_id', $estudiante->id)
                                ->orderBy('fecha', 'desc')
                                ->take(5)
                                ->get();

        return view('estudiante.dashboard', compact('estudiante', 'asistencias', 'faltas', 'atrasos', 'licencias', 'ultimasAsistencias'));
    }

    // 2. Historial Completo (Web)
    public function misAsistencias()
    {
        $estudiante = Auth::user()->estudiante;
        
        $historial = Asistencia::with('materia')
                        ->where('estudiante_id', $estudiante->id)
                        ->orderBy('fecha', 'desc')
                        ->get();

        return view('estudiante.asistencias', compact('historial'));
    }

    // 3. Estado de Licencias (Web)
    public function misLicencias()
    {
        $estudiante = Auth::user()->estudiante;

        $licencias = Licencia::where('estudiante_id', $estudiante->id)
                             ->orderBy('created_at', 'desc')
                             ->get();

        return view('estudiante.licencias', compact('licencias'));
    }

    // =================================================
    //              MÉTODOS PARA LA API (APP MÓVIL)
    // =================================================

    // 4. Guardar Licencia desde la App
    public function guardarLicencia(Request $request)
    {
        // Validar datos que vienen del celular
        $request->validate([
            'motivo' => 'required|string|max:500',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'archivo' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // Máx 5MB
        ]);

        // Obtener el estudiante autenticado por Token (Sanctum)
        $user = $request->user(); 
        $estudiante = $user->estudiante;

        if (!$estudiante) {
            return response()->json(['message' => 'No tienes perfil de estudiante asignado'], 403);
        }

        // Manejo del archivo adjunto
        $rutaArchivo = null;
        if ($request->hasFile('archivo')) {
            // Guarda en 'storage/app/public/licencias'
            $rutaArchivo = $request->file('archivo')->store('licencias', 'public');
        }

        // Crear el registro en la BD
        Licencia::create([
            'estudiante_id' => $estudiante->id,
            'jefe_carrera_id' => null, // Pendiente de revisión
            'motivo' => $request->motivo,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'archivo_adjunto' => $rutaArchivo,
            'estado' => 'pendiente',
        ]);

        // Responder JSON a la App
        return response()->json([
            'success' => true,
            'message' => 'Solicitud registrada correctamente'
        ], 201);
    }
}