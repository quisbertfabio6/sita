<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\Licencia;
use App\Models\Materia;
use App\Services\EstadisticasAsistenciaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReporteController extends Controller
{
    public function __construct(private EstadisticasAsistenciaService $estadisticas)
    {
    }

    /**
     * Pantalla de reportes con filtro de gestion academica.
     */
    public function index()
    {
        $cursos = Curso::orderBy('nombre')->get();
        $materias = Materia::with('curso')->orderBy('nombre')->get();
        $gestiones = Gestion::orderBy('anio', 'desc')->get();
        $gestionActiva = Gestion::where('es_activa', true)->first();

        return view('admin.reportes.index', compact('cursos', 'materias', 'gestiones', 'gestionActiva'));
    }

    /**
     * PDF de asistencias de una materia dentro de un rango de la gestion elegida.
     */
    public function generarReporteFechas(Request $request)
    {
        $datosValidados = $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'gestion_id' => 'nullable|integer|exists:gestiones,id',
            'fecha_inicio' => 'required|date_format:Y-m-d',
            'fecha_fin' => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio',
        ]);

        $gestion = $this->resolverGestion($datosValidados['gestion_id'] ?? null);
        $fechaInicio = $datosValidados['fecha_inicio'];
        $fechaFin = $datosValidados['fecha_fin'];

        if (substr($fechaInicio, 0, 4) !== $gestion->anio
            || substr($fechaFin, 0, 4) !== $gestion->anio) {
            throw ValidationException::withMessages([
                'fecha_inicio' => 'El rango de fechas debe pertenecer a la gestion seleccionada (' . $gestion->anio . ').',
            ]);
        }

        $materia = Materia::with('curso')->findOrFail($datosValidados['materia_id']);
        [$datos, $totalClases] = $this->datosPorMateria($materia, $gestion, $fechaInicio, $fechaFin);

        $pdf = Pdf::loadView('reportes.reporte_fechas', compact(
            'materia', 'datos', 'totalClases', 'fechaInicio', 'fechaFin', 'gestion'
        ));

        return $pdf->stream('Reporte-' . $materia->nombre . '.pdf');
    }

    /**
     * PDF oficial del docente por materia y gestion, sin mezclar anios.
     */
    public function listaAsistenciaPorMateria(Request $request, $idMateria)
    {
        $request->validate(['gestion_id' => 'nullable|integer|exists:gestiones,id']);
        $gestion = $this->resolverGestion($request->input('gestion_id'));
        $materia = Materia::with(['curso', 'docentes.usuario'])->findOrFail($idMateria);

        [$datos, $totalClases] = $this->datosPorMateria(
            $materia,
            $gestion,
            $gestion->anio . '-01-01',
            $gestion->anio . '-12-31'
        );

        $pdf = Pdf::loadView('reportes.lista_asistencia', compact(
            'materia', 'datos', 'totalClases', 'gestion'
        ));

        return $pdf->stream('Lista-' . $materia->nombre . '.pdf');
    }

    /**
     * PDF del historial del estudiante en la gestion seleccionada.
     */
    public function generarHistorialPersonal(Request $request)
    {
        $request->validate(['gestion_id' => 'nullable|integer|exists:gestiones,id']);

        $estudiante = Auth::user()?->estudiante;
        if (!$estudiante) {
            return redirect()->back()->with('error', 'Perfil de estudiante no encontrado.');
        }

        $gestion = $this->resolverGestion($request->input('gestion_id'));
        $inscripcion = $estudiante->inscripciones()
            ->where('gestion_id', $gestion->id)
            ->with('curso.materias')
            ->first();

        if (!$inscripcion) {
            return redirect()->back()->with('error', 'No tienes cursos registrados en esta gestion.');
        }

        $curso = $inscripcion->curso;
        $fechaInicio = $gestion->anio . '-01-01';
        $fechaFin = $gestion->anio . '-12-31';
        $licencias = Licencia::where('estudiante_id', $estudiante->id)
            ->where('estado', 'aprobada')
            ->whereDate('fecha_inicio', '<=', $fechaFin)
            ->whereDate('fecha_fin', '>=', $fechaInicio)
            ->get();

        $datos = [];
        foreach ($curso->materias as $materia) {
            $totalClases = $this->estadisticas->totalClases($materia->id, $gestion->anio);

            $historial = Asistencia::where('estudiante_id', $estudiante->id)
                ->where('materia_id', $materia->id)
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->orderBy('fecha')
                ->get();

            $resumen = $this->estadisticas->calcular($historial, $licencias, $totalClases);

            $datos[] = array_merge($resumen, [
                'materia_nombre' => $materia->nombre,
                'total_clases' => $totalClases,
                'detalle' => $historial,
            ]);
        }

        $pdf = Pdf::loadView('reportes.historial_personal', compact(
            'estudiante', 'curso', 'gestion', 'datos'
        ));

        return $pdf->stream('Historial-' . $estudiante->usuario->nombre_completo . '.pdf');
    }

    /**
     * Datos del PDF de una materia. Consultas agrupadas para evitar N+1.
     */
    private function datosPorMateria(Materia $materia, Gestion $gestion, string $desde, string $hasta): array
    {
        $totalClases = $this->estadisticas->totalClases($materia->id, $gestion->anio, $desde, $hasta);

        // Se incluyen los estudiantes inscritos en la gestion consultada,
        // incluso si posteriormente se retiraron, para conservar el historial.
        $estudiantes = Estudiante::whereHas('inscripciones', function ($consulta) use ($materia, $gestion) {
                $consulta->where('curso_id', $materia->curso_id)
                    ->where('gestion_id', $gestion->id);
            })
            ->with('usuario')
            ->join('usuarios', 'usuarios.id', '=', 'estudiantes.user_id')
            ->select('estudiantes.*')
            ->orderBy('usuarios.nombre_completo')
            ->get();

        $ids = $estudiantes->pluck('id');

        $asistencias = Asistencia::where('materia_id', $materia->id)
            ->whereIn('estudiante_id', $ids)
            ->whereBetween('fecha', [$desde, $hasta])
            ->get()
            ->groupBy('estudiante_id');

        $licencias = Licencia::where('estado', 'aprobada')
            ->whereIn('estudiante_id', $ids)
            ->whereDate('fecha_inicio', '<=', $hasta)
            ->whereDate('fecha_fin', '>=', $desde)
            ->get()
            ->groupBy('estudiante_id');

        $datos = $estudiantes->map(function ($estudiante) use ($asistencias, $licencias, $totalClases) {
            $resumen = $this->estadisticas->calcular(
                $asistencias->get($estudiante->id, collect()),
                $licencias->get($estudiante->id, collect()),
                $totalClases
            );

            return array_merge($resumen, [
                'nombre' => $estudiante->usuario->nombre_completo,
                'matricula' => $estudiante->matricula,
            ]);
        });

        return [$datos, $totalClases];
    }

    private function resolverGestion(?string $gestionId): Gestion
    {
        $gestion = $gestionId
            ? Gestion::find($gestionId)
            : Gestion::where('es_activa', true)->first();

        if (!$gestion) {
            throw ValidationException::withMessages([
                'gestion_id' => 'No hay una gestion academica activa. Seleccione una gestion.',
            ]);
        }

        return $gestion;
    }
}
