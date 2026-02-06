<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Materia;
use App\Models\Asistencia;
use App\Models\Curso;
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
        return view('admin.reportes.index', compact('cursos', 'materias'));
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

        $totalClases = Asistencia::where('materia_id', $materia->id)
                        ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                        ->distinct('fecha')
                        ->count();
        if ($totalClases == 0) $totalClases = 1;

        // --- CÓDIGO CORREGIDO ---
        // Unimos 'estudiantes' con 'usuarios' para poder ordenar por nombre
        $estudiantes = $materia->curso->estudiantes()
                            ->with('usuario') // Mantenemos el with() para cargar la relación
                            ->join('usuarios', 'usuarios.id', '=', 'estudiantes.user_id')
                            ->select('estudiantes.*') // Evitamos colisión de 'id'
                            ->orderBy('usuarios.nombre_completo', 'asc') // Ahora sí podemos ordenar
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
    public function listaAsistenciaPorMateria($idMateria)
    {
        $materia = Materia::with(['curso', 'docentes.usuario'])->findOrFail($idMateria);
        
        $totalClases = Asistencia::where('materia_id', $idMateria)->distinct('fecha')->count();
        if ($totalClases == 0) $totalClases = 1;

        // --- CÓDIGO CORREGIDO ---
        $estudiantes = $materia->curso->estudiantes()
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

        // 2. Obtener todas las materias del curso del estudiante
        $materias = $estudiante->curso->materias;

        // 3. Preparar los datos
        // Vamos a agrupar todas las asistencias por materia
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
                            ->orderBy('fecha', 'asc') // Ordenar por fecha
                            ->get();

            // Calcular puntos
            $puntos = 0;
            foreach ($historial as $asis) {
                // Usamos la lógica de ponderación
                if ($asis->estado == 'presente' || $asis->estado == 'licencia') $puntos += 1;
                if ($asis->estado == 'atraso') $puntos += 0.5;
            }

            $porcentaje = round(($puntos / $totalClases) * 100);

            // Guardamos todo
            $datos[] = [
                'materia_nombre' => $materia->nombre,
                'total_clases' => $totalClases,
                'puntos' => $puntos,
                'porcentaje' => $porcentaje,
                'riesgo' => $porcentaje < 80,
                'detalle' => $historial // La lista detallada de asistencias
            ];
        }

        // 4. Cargar la vista HTML y generar el PDF
        $pdf = Pdf::loadView('reportes.historial_personal', compact('estudiante', 'datos'));
        
        return $pdf->stream('Historial-'.$estudiante->usuario->nombre_completo.'.pdf');
    }
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
}