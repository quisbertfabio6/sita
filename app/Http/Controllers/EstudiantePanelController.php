<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Asistencia;
use App\Models\Licencia;
use App\Models\SubcategoriaLicencia;
use App\Models\Estudiante;
use App\Services\CupoLicenciasSemestralService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

        $licencias = Licencia::with(['categoria', 'subcategoria'])->where('estudiante_id', $estudiante->id)
                             ->orderBy('created_at', 'desc')
                             ->get();

        return view('estudiante.licencias', compact('licencias'));
    }

    // =================================================
    //              MÉTODOS PARA LA API (APP MÓVIL)
    // =================================================

    // 4. Guardar Licencia desde la App
    public function guardarLicencia(Request $request, CupoLicenciasSemestralService $cupoService)
    {
        // Compatibilidad: la app anterior sigue enviando solo "motivo".
        // La app nueva enviara categoria_id + subcategoria_id y detalle opcional.
        $datos = $request->validate([
            'categoria_id' => [
                'nullable', 'required_with:subcategoria_id', 'integer',
                Rule::exists('categorias_licencia', 'id')->where('activo', true),
            ],
            'subcategoria_id' => [
                'nullable', 'required_with:categoria_id', 'integer',
                Rule::exists('subcategorias_licencia', 'id')
                    ->where('activo', true)
                    ->where('categoria_id', $request->input('categoria_id')),
            ],
            'motivo' => 'required_without:subcategoria_id|nullable|string|max:500',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'archivo' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // Max 5 MB
        ]);

        // Obtener el estudiante autenticado por Token (Sanctum)
        $user = $request->user(); 
        $estudiante = $user->estudiante;

        if (!$estudiante) {
            return response()->json(['message' => 'No tienes perfil de estudiante asignado'], 403);
        }

        // El campo motivo nunca queda vacio, incluso si el estudiante
        // selecciona una subcategoria sin escribir detalle adicional.
        $subcategoria = isset($datos['subcategoria_id'])
            ? SubcategoriaLicencia::findOrFail($datos['subcategoria_id'])
            : null;
        $motivo = $datos['motivo'] ?? null;
        if ($motivo === null || trim($motivo) === '') {
            $motivo = $subcategoria?->nombre;
        }

        // Bloquear la fila del estudiante evita dos solicitudes simultaneas
        // que puedan superar el limite. Tambien aplica a la app anterior.
        return DB::transaction(function () use ($request, $datos, $estudiante, $motivo, $cupoService) {
            Estudiante::whereKey($estudiante->id)->lockForUpdate()->firstOrFail();

            $cupo = $cupoService->resumen($estudiante->id);
            if (!$cupo['puede_solicitar']) {
                return response()->json([
                    'success' => false,
                    'codigo' => 'LIMITE_LICENCIAS_SEMESTRALES',
                    'message' => 'Has alcanzado el límite de 3 licencias por semestre.',
                    'cupo' => $cupo,
                ], 409);
            }

            // Solo guardar archivos cuando todavia queda cupo.
            $rutaArchivo = null;
            if ($request->hasFile('archivo')) {
                $rutaArchivo = $request->file('archivo')->store('licencias', 'public');
            }

            Licencia::create([
                'estudiante_id' => $estudiante->id,
                'jefe_carrera_id' => null,
                'motivo' => $motivo,
                'categoria_id' => $datos['categoria_id'] ?? null,
                'subcategoria_id' => $datos['subcategoria_id'] ?? null,
                'fecha_inicio' => $datos['fecha_inicio'],
                'fecha_fin' => $datos['fecha_fin'],
                'archivo_adjunto' => $rutaArchivo,
                'estado' => 'pendiente',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud registrada correctamente',
                'cupo' => $cupoService->resumen($estudiante->id),
            ], 201);
        });
    }
}