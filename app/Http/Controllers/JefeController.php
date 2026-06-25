<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

// Modelos
use App\Models\Licencia;
use App\Models\Estudiante;
use App\Models\Curso;
use App\Models\Materia;

class JefeController extends Controller
{
    // --- MÉTODO AUXILIAR PARA TRADUCIR NOMBRES ---
    private function obtenerPalabraClave($nombreCompleto) 
    {
        $mapa = [
            'Sistemas Informáticos' => 'Sistemas',
            'Mecánica Automotriz'   => 'Automotriz',
            'Mecánica Industrial'   => 'Mec. Industrial',
            'Electromecánica'       => 'Electromecánica',
            'Electrónica'           => 'Electrónica',
            'Construcción Civil'    => 'Construcción Civil',
        ];

        $nombreLimpio = trim($nombreCompleto);
        return $mapa[$nombreLimpio] ?? $nombreLimpio;
    }

    // 1. Dashboard Académico (CORREGIDO)
    public function index()
    {
        $jefe = Auth::user()->jefeCarrera;
        
        // Obtenemos las carreras asignadas
        $carrerasAsignadas = ($jefe && $jefe->carrera_asignada) ? explode(',', $jefe->carrera_asignada) : [];

        // Inicializamos variables con las CLAVES EXACTAS que pide la vista
        $kpis = [
            'licencias_pendientes' => 0, 
            'total_cursos' => 0,
            'total_materias' => 0, // Agregamos esta clave que faltaba
            'promedio_general' => 0
        ];
        $pieData = [0, 0, 0];
        $barLabels = [];
        $barData = [];

        if (!empty($carrerasAsignadas)) {
            
            // --- A. KPIS (CONTADORES) ---
            
            // 1. Licencias Pendientes (Filtrado)
            $kpis['licencias_pendientes'] = Licencia::where('estado', 'pendiente')
                ->whereHas('estudiante.curso', function ($query) use ($carrerasAsignadas) {
                    $query->where(function ($q) use ($carrerasAsignadas) {
                        foreach ($carrerasAsignadas as $carrera) {
                            $palabraClave = $this->obtenerPalabraClave($carrera);
                            $q->orWhere('nombre', 'LIKE', '%' . $palabraClave . '%');
                        }
                    });
                })->count();

            // 2. Query Base de Cursos (Lo usamos para contar cursos y materias)
            $queryCursos = Curso::where(function ($q) use ($carrerasAsignadas) {
                foreach ($carrerasAsignadas as $carrera) {
                    $palabraClave = $this->obtenerPalabraClave($carrera);
                    $q->orWhere('nombre', 'LIKE', '%' . $palabraClave . '%');
                }
            });

            $kpis['total_cursos'] = $queryCursos->count();

            // 3. Total Materias (Contamos las materias de esos cursos filtrados)
            // Obtenemos los IDs de los cursos filtrados para contar sus materias
            $idsCursos = $queryCursos->pluck('id');
            $kpis['total_materias'] = Materia::whereIn('curso_id', $idsCursos)->count();


            // --- B. DATOS PARA GRÁFICOS ---

            // 4. Gráfico Dona: Licencias por Estado
            $conteoLicencias = Licencia::whereHas('estudiante.curso', function ($query) use ($carrerasAsignadas) {
                    $query->where(function ($q) use ($carrerasAsignadas) {
                        foreach ($carrerasAsignadas as $carrera) {
                            $palabraClave = $this->obtenerPalabraClave($carrera);
                            $q->orWhere('nombre', 'LIKE', '%' . $palabraClave . '%');
                        }
                    });
                })
                ->select('estado', DB::raw('count(*) as total'))
                ->groupBy('estado')
                ->pluck('total', 'estado')
                ->toArray();

            // Orden: [Aprobadas, Rechazadas, Pendientes]
            $pieData = [
                $conteoLicencias['aprobada'] ?? 0,
                $conteoLicencias['rechazada'] ?? 0,
                $conteoLicencias['pendiente'] ?? 0,
            ];

            // 5. Gráfico Barras: Asistencia por Curso
            $cursos = $queryCursos->with(['materias.asistencias'])->get();
            
            $sumaPromedios = 0;
            
            foreach ($cursos as $curso) {
                $totalRegistros = 0;
                $totalPresentes = 0;

                foreach ($curso->materias as $materia) {
                    $totalRegistros += $materia->asistencias->count();
                    $totalPresentes += $materia->asistencias->where('estado', 'presente')->count();
                }

                $porcentaje = $totalRegistros > 0 
                    ? round(($totalPresentes / $totalRegistros) * 100) 
                    : 0;

                $barLabels[] = $curso->nombre;
                $barData[] = $porcentaje;
                $sumaPromedios += $porcentaje;
            }

            if (count($cursos) > 0) {
                $kpis['promedio_general'] = round($sumaPromedios / count($cursos));
            }
        }

        return view('jefe.dashboard', compact('kpis', 'pieData', 'barLabels', 'barData'));
    }

    // 2. Ver lista de licencias (MANTENEMOS TU LÓGICA)
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

        $licencia->estado = $request->estado;
        $licencia->comentario_admin = $request->comentario_admin;
        
        $jefe = Auth::user()->jefeCarrera; 
        if($jefe) {
            $licencia->jefe_carrera_id = $jefe->id;
        }
        $licencia->save();

        if ($request->estado === 'aprobada') {
            $asistenciasAfectadas = \App\Models\Asistencia::where('estudiante_id', $licencia->estudiante_id)
                ->whereBetween('fecha', [$licencia->fecha_inicio, $licencia->fecha_fin])
                ->get();

            foreach ($asistenciasAfectadas as $asistencia) {
                if ($asistencia->estado === 'falta' || $asistencia->estado === 'atraso') {
                    $asistencia->update([
                        'estado' => 'licencia',
                        'observacion' => "Justificado por Licencia #{$licencia->id}: " . $licencia->motivo
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Licencia procesada correctamente.');
    }

    // 4. Formulario Reportes
    public function showReportForm()
    {
        return view('jefe.reportes.index');
    }

    // 5. Generar Reporte PDF
    public function generateLicenseReport(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'estado' => 'required|in:todas,pendiente,aprobada,rechazada',
        ]);

        $jefe = Auth::user()->jefeCarrera;
        $carrerasAsignadas = ($jefe && $jefe->carrera_asignada) ? explode(',', $jefe->carrera_asignada) : [];

        $query = Licencia::query();
        $query->whereBetween('fecha_inicio', [$request->fecha_inicio, $request->fecha_fin]);

        if ($request->estado != 'todas') {
            $query->where('estado', $request->estado);
        }

        $query->whereHas('estudiante.curso', function ($q_curso) use ($carrerasAsignadas) {
            $q_curso->where(function ($q) use ($carrerasAsignadas) {
                foreach ($carrerasAsignadas as $carrera) {
                    $palabraClave = $this->obtenerPalabraClave(trim($carrera));
                    $q->orWhere('nombre', 'LIKE', '%' . $palabraClave . '%');
                }
            });
        });

        $licencias = $query->with(['estudiante.usuario', 'estudiante.curso', 'jefeCarrera']) 
                            ->orderBy('fecha_inicio', 'asc')
                            ->get();
        
        $datosReporte = [
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'estado' => $request->estado,
            'licencias' => $licencias
        ];

        $pdf = Pdf::loadView('reportes.jefe_licencias', $datosReporte);
        return $pdf->stream('Reporte_Licencias.pdf');
    }
}