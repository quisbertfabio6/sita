<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Licencia;
use App\Models\Estudiante;
use App\Models\Curso;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class JefeController extends Controller
{
    // --- MÉTODO AUXILIAR PARA TRADUCIR NOMBRES ---
    // Convierte el nombre formal de la carrera en la palabra clave que está en los cursos
    private function obtenerPalabraClave($nombreCompleto) 
    {
        $mapa = [
            'Sistemas Informáticos' => 'Sistemas',
            'Mecánica Automotriz'   => 'Automotriz',
            'Mecánica Industrial'   => 'Mec. Industrial', // Ojo con el punto
            'Electromecánica'       => 'Electromecánica',
            'Electrónica'           => 'Electrónica',
            'Construcción Civil'    => 'Construcción Civil',
        ];

        $nombreLimpio = trim($nombreCompleto);
        
        // Si existe en el mapa, devuelve la clave corta, si no, devuelve el original
        return $mapa[$nombreLimpio] ?? $nombreLimpio;
    }

    // 1. Dashboard Académico (Resumen FILTRADO)
    public function index()
    {
        $jefe = Auth::user()->jefeCarrera;
        
        $carrerasAsignadas = ($jefe && $jefe->carrera_asignada) ? explode(',', $jefe->carrera_asignada) : [];

        if (empty($carrerasAsignadas)) {
            return view('jefe.dashboard', [
                'pendientes' => 0, 'totalAlumnos' => 0, 'totalCursos' => 0
            ]);
        }

        // 1. Contar Licencias Pendientes
        $pendientes = Licencia::where('estado', 'pendiente')
            ->whereHas('estudiante.curso', function ($query) use ($carrerasAsignadas) {
                $query->where(function ($q) use ($carrerasAsignadas) {
                    foreach ($carrerasAsignadas as $carrera) {
                        $palabraClave = $this->obtenerPalabraClave($carrera);
                        $q->orWhere('nombre', 'LIKE', '%' . $palabraClave . '%');
                    }
                });
            })->count();

        // 2. Contar Alumnos (Usando la palabra clave)
        $totalAlumnos = Estudiante::whereHas('curso', function ($query) use ($carrerasAsignadas) {
            $query->where(function ($q) use ($carrerasAsignadas) {
                foreach ($carrerasAsignadas as $carrera) {
                    $palabraClave = $this->obtenerPalabraClave($carrera);
                    $q->orWhere('nombre', 'LIKE', '%' . $palabraClave . '%');
                }
            });
        })->count();

        // 3. Contar Cursos (Usando la palabra clave)
        $totalCursos = Curso::where(function ($q) use ($carrerasAsignadas) {
            foreach ($carrerasAsignadas as $carrera) {
                $palabraClave = $this->obtenerPalabraClave($carrera);
                $q->orWhere('nombre', 'LIKE', '%' . $palabraClave . '%');
            }
        })->count();

        return view('jefe.dashboard', compact('pendientes', 'totalAlumnos', 'totalCursos'));
    }

    // 2. Ver lista de licencias (FILTRADA)
    public function licencias()
    {
        $jefe = Auth::user()->jefeCarrera;
        
        if (!$jefe || empty($jefe->carrera_asignada)) {
            $licencias = collect();
        } else {
            $carreras = explode(',', $jefe->carrera_asignada);

            $licencias = Licencia::with(['estudiante.usuario', 'estudiante.curso'])
                ->whereHas('estudiante.curso', function ($query) use ($carreras) {
                    $query->where(function ($q) use ($carreras) {
                        foreach ($carreras as $carrera) {
                            $palabraClave = $this->obtenerPalabraClave($carrera);
                            $q->orWhere('nombre', 'LIKE', '%' . $palabraClave . '%');
                        }
                    });
                })
                ->orderBy('created_at', 'desc')
                ->get();
        }
                             
        return view('jefe.licencias.index', compact('licencias'));
    }

    // 3. Acción de Aprobar/Rechazar
    public function cambiarEstadoLicencia(Request $request, $id)
    {
        $licencia = Licencia::findOrFail($id);
        
        $request->validate([
            'estado' => 'required|in:aprobada,rechazada',
            'comentario_admin' => 'nullable|string|max:255'
        ]);

        // 1. Guardamos los cambios de la licencia
        $licencia->estado = $request->estado;
        $licencia->comentario_admin = $request->comentario_admin;
        
        $jefe = Auth::user()->jefeCarrera; 
        if($jefe) {
            $licencia->jefe_carrera_id = $jefe->id;
        }
        $licencia->save();

        // 2. Si se APROBÓ, actualizamos las asistencias automáticamente
        if ($request->estado === 'aprobada') {
            // Buscamos todas las asistencias de ese alumno en el rango de fechas
            $asistenciasAfectadas = \App\Models\Asistencia::where('estudiante_id', $licencia->estudiante_id)
                ->whereBetween('fecha', [$licencia->fecha_inicio, $licencia->fecha_fin])
                ->get();

            foreach ($asistenciasAfectadas as $asistencia) {
                // Solo justificamos si tenía "Falta" o "Atraso" (No tocamos los "Presente")
                if ($asistencia->estado === 'falta' || $asistencia->estado === 'atraso') {
                    $asistencia->update([
                        'estado' => 'licencia',
                        // Aquí llenamos la columna OBSERVACIÓN automáticamente
                        'observacion' => "Justificado por Licencia #{$licencia->id}: " . $licencia->motivo
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Licencia procesada correctamente.');
    }

    // ... (tus funciones index, licencias, cambiarEstadoLicencia y obtenerPalabraClave van aquí) ...

    /**
     * Muestra la página de filtros para los reportes del Jefe.
     */
    public function showReportForm()
    {
        // Solo mostramos la vista, no necesita datos especiales
        return view('jefe.reportes.index');
    }

    /**
     * Procesa los filtros y genera el PDF de Licencias.
     */
    public function generateLicenseReport(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'estado' => 'required|in:todas,pendiente,aprobada,rechazada',
        ]);

        $jefe = Auth::user()->jefeCarrera;
        $carrerasAsignadas = ($jefe && $jefe->carrera_asignada) ? explode(',', $jefe->carrera_asignada) : [];

        // 1. Empezar la consulta
        $query = Licencia::query();

        // 2. Filtrar por rango de fechas
        $query->whereBetween('fecha_inicio', [$request->fecha_inicio, $request->fecha_fin]);

        // 3. Filtrar por estado (si no es "todas")
        if ($request->estado != 'todas') {
            $query->where('estado', $request->estado);
        }

        // 4. Filtrar por las carreras del Jefe (¡MUY IMPORTANTE!)
        $query->whereHas('estudiante.curso', function ($q_curso) use ($carrerasAsignadas) {
            $q_curso->where(function ($q) use ($carrerasAsignadas) {
                foreach ($carrerasAsignadas as $carrera) {
                    $palabraClave = $this->obtenerPalabraClave(trim($carrera));
                    $q->orWhere('nombre', 'LIKE', '%' . $palabraClave . '%');
                }
            });
        });

        // 5. Cargar relaciones y ejecutar (LÍNEA CORREGIDA)
        $licencias = $query->with(['estudiante.usuario', 'estudiante.curso', 'jefeCarrera']) 
                            ->orderBy('fecha_inicio', 'asc')
                            ->get();
        
        $datosReporte = [
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'estado' => $request->estado,
            'licencias' => $licencias
        ];

        // 6. Generar el PDF
        $pdf = Pdf::loadView('reportes.jefe_licencias', $datosReporte);
        return $pdf->stream('Reporte_Licencias.pdf');
    }
}