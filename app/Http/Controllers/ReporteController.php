<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Materia;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Estudiante; // <- AÑADIDO
use App\Models\Gestion;    // <- AÑADIDO
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteController extends Controller
{
    /**
     * Muestra la página principal de Reportes con los filtros
     */
    public function index()
    {
        $cursos = Curso::orderBy('nombre')->get();
        $materias = Materia::orderBy('nombre')->get();
        
        // --- NUEVO: Mandamos las gestiones a la vista para el Filtro Global ---
        $gestiones = Gestion::orderBy('anio', 'desc')->get();
        $gestionActiva = Gestion::where('es_activa', true)->first();

        return view('admin.reportes.index', compact('cursos', 'materias', 'gestiones', 'gestionActiva'));
    }

    /**
     * Genera el reporte PDF filtrado por Fechas y Curso/Materia
     */
    public function generarReporteFechas(Request $request)
    {
        $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $materia = Materia::with('curso')->findOrFail($request->materia_id);
        $fechaInicio = $request->fecha_inicio;
        $fechaFin = $request->fecha_fin;

        // Determinamos qué gestión consultar (la enviada por el filtro, o la activa por defecto)
        $gestionId = $request->gestion_id ?? Gestion::where('es_activa', true)->value('id');

        $totalClases = Asistencia::where('materia_id', $materia->id)
                        ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                        ->distinct('fecha')
                        ->count();
        if ($totalClases == 0) $totalClases = 1;

        // --- CÓDIGO CORREGIDO PARA SOPORTAR GESTIONES E INSCRIPCIONES ---
        // Buscamos a los estudiantes que estén inscritos en este curso y en esta gestión específica
        $estudiantes = Estudiante::whereHas('inscripciones', function($query) use ($materia, $gestionId) {
                            $query->where('curso_id', $materia->curso_id)
                                  ->where('gestion_id', $gestionId);
                        })
                        ->with('usuario') 
                        ->join('usuarios', 'usuarios.id', '=', 'estudiantes.user_id')
                        ->select('estudiantes.*') 
                        ->orderBy('usuarios.nombre_completo', 'asc') 
                        ->get();
        // --- FIN DE LA CORRECCIÓN ---

        $datos = $estudiantes->map(function($est) use ($materia, $fechaInicio, $fechaFin, $totalClases) {
            
            $asistencias = Asistencia::where('estudiante_id', $est->id)
                            ->where('materia_id', $materia->id)
                            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                            ->get();

            $puntos = 0;
            foreach ($asistencias as $asis) {
                if ($asis->estado == 'presente' || $asis->estado == 'licencia') $puntos += 1;
                if ($asis->estado == 'atraso') $puntos += 0.5;
            }
            $porcentaje = round(($puntos / $totalClases) * 100);

            return [
                'nombre' => $est->usuario->nombre_completo,
                'matricula' => $est->matricula,
                'asistencias_puntos' => $puntos,
                'porcentaje' => $porcentaje,
                'riesgo' => $porcentaje < 80
            ];
        });
        
        $pdf = Pdf::loadView('reportes.reporte_fechas', compact('materia', 'datos', 'totalClases', 'fechaInicio', 'fechaFin'));
        return $pdf->stream('Reporte-'.$materia->nombre.'.pdf');
    }


    /**
     * (Esta es la que usa el Docente)
     * Genera el reporte PDF de la lista oficial (Completo)
     */
    public function listaAsistenciaPorMateria(Request $request, $idMateria)
    {
        $materia = Materia::with(['curso', 'docentes.usuario'])->findOrFail($idMateria);
        
        $totalClases = Asistencia::where('materia_id', $idMateria)->distinct('fecha')->count();
        if ($totalClases == 0) $totalClases = 1;

        $gestionId = $request->gestion_id ?? Gestion::where('es_activa', true)->value('id');

        // --- CÓDIGO CORREGIDO PARA SOPORTAR GESTIONES ---
        $estudiantes = Estudiante::whereHas('inscripciones', function($query) use ($materia, $gestionId) {
                            $query->where('curso_id', $materia->curso_id)
                                  ->where('gestion_id', $gestionId);
                        })
                        ->with('usuario')
                        ->join('usuarios', 'usuarios.id', '=', 'estudiantes.user_id')
                        ->select('estudiantes.*')
                        ->orderBy('usuarios.nombre_completo', 'asc')
                        ->get();
        // --- FIN DE LA CORRECCIÓN ---

        $datos = $estudiantes->map(function($est) use ($idMateria, $totalClases) {
            $asistencias = Asistencia::where('estudiante_id', $est->id)->where('materia_id', $idMateria)->get();
            
            $puntos = 0;
            foreach ($asistencias as $asis) {
                if ($asis->estado == 'presente' || $asis->estado == 'licencia') $puntos += 1;
                if ($asis->estado == 'atraso') $puntos += 0.5;
            }
            $porcentaje = round(($puntos / $totalClases) * 100);

            return [
                'nombre' => $est->usuario->nombre_completo,
                'matricula' => $est->matricula,
                'asistencias_puntos' => $puntos,
                'porcentaje' => $porcentaje,
                'riesgo' => $porcentaje < 80
            ];
        });

        $pdf = Pdf::loadView('reportes.lista_asistencia', compact('materia', 'datos', 'totalClases'));
        return $pdf->stream('Lista-'.$materia->nombre.'.pdf');
    }

    /**
     * Genera el PDF del historial de asistencias personal de un estudiante.
     */
    public function generarHistorialPersonal(Request $request)
    {
        // 1. Obtener el estudiante logueado
        $estudiante = \Illuminate\Support\Facades\Auth::user()->estudiante;
        if (!$estudiante) {
            return redirect()->back()->with('error', 'Perfil de estudiante no encontrado.');
        }

        // 2. Obtener las materias dependiendo de la gestión seleccionada o la activa
        $gestionId = $request->gestion_id ?? Gestion::where('es_activa', true)->value('id');
        
        $inscripcion = $estudiante->inscripciones()->where('gestion_id', $gestionId)->first();
        if (!$inscripcion) {
            return redirect()->back()->with('error', 'No tienes cursos registrados en esta gestión.');
        }

        $materias = $inscripcion->curso->materias;

        // 3. Preparar los datos
        $datos = [];
        
        foreach ($materias as $materia) {
            // Total clases dictadas en esta materia
            $totalClases = Asistencia::where('materia_id', $materia->id)
                            ->distinct('fecha')
                            ->count();
            if ($totalClases == 0) $totalClases = 1;

            // Historial del estudiante en esta materia
            $historial = Asistencia::where('estudiante_id', $estudiante->id)
                            ->where('materia_id', $materia->id)
                            ->orderBy('fecha', 'asc') 
                            ->get();

            // Calcular puntos
            $puntos = 0;
            foreach ($historial as $asis) {
                if ($asis->estado == 'presente' || $asis->estado == 'licencia') $puntos += 1;
                if ($asis->estado == 'atraso') $puntos += 0.5;
            }

            $porcentaje = round(($puntos / $totalClases) * 100);

            $datos[] = [
                'materia_nombre' => $materia->nombre,
                'total_clases' => $totalClases,
                'puntos' => $puntos,
                'porcentaje' => $porcentaje,
                'riesgo' => $porcentaje < 80,
                'detalle' => $historial 
            ];
        }

        // 4. Cargar la vista HTML y generar el PDF
        $pdf = Pdf::loadView('reportes.historial_personal', compact('estudiante', 'datos'));
        
        return $pdf->stream('Historial-'.$estudiante->usuario->nombre_completo.'.pdf');
    }

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
}