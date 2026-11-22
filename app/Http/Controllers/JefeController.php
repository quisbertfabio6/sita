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
use App\Models\Asistencia;
use App\Services\AccesoLicenciasJefaturaService;

class JefeController extends Controller
{
    // 1. Dashboard académico con el mismo filtro de carreras que las licencias.
    public function index(AccesoLicenciasJefaturaService $acceso)
    {
        $jefe = Auth::user()->jefeCarrera;

        $kpis = [
            'licencias_pendientes' => $acceso->filtrarLicencias(Licencia::query(), $jefe)
                ->where('estado', 'pendiente')->count(),
            'total_cursos' => 0,
            'total_materias' => 0,
            'promedio_general' => 0,
        ];

        $queryCursos = $acceso->filtrarCursos(Curso::query(), $jefe);
        $kpis['total_cursos'] = (clone $queryCursos)->count();
        $idsCursos = (clone $queryCursos)->pluck('id');
        $kpis['total_materias'] = Materia::whereIn('curso_id', $idsCursos)->count();

        $conteoLicencias = $acceso->filtrarLicencias(Licencia::query(), $jefe)
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        $pieData = [
            $conteoLicencias['aprobada'] ?? 0,
            $conteoLicencias['rechazada'] ?? 0,
            $conteoLicencias['pendiente'] ?? 0,
        ];

        $barLabels = [];
        $barData = [];
        $sumaPromedios = 0;
        $cursos = $queryCursos->with(['materias.asistencias'])->get();

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

        if ($cursos->count() > 0) {
            $kpis['promedio_general'] = round($sumaPromedios / $cursos->count());
        }

        return view('jefe.dashboard', compact('kpis', 'pieData', 'barLabels', 'barData'));
    }

    // 2. Solo mostrar solicitudes de las carreras y gestiones autorizadas.
    public function licencias(AccesoLicenciasJefaturaService $acceso)
    {
        $jefe = Auth::user()->jefeCarrera;
        $licencias = $acceso->filtrarLicencias(Licencia::query(), $jefe)
            ->with([
                'estudiante.usuario',
                'estudiante.inscripciones.gestion',
                'estudiante.inscripciones.curso',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('jefe.licencias.index', compact('licencias'));
    }

    // 3. Aprobar/rechazar: comprobar el acceso en servidor, no solo en la tabla.
    public function cambiarEstadoLicencia(Request $request, $id, AccesoLicenciasJefaturaService $acceso)
    {
        $datos = $request->validate([
            'estado' => 'required|in:aprobada,rechazada',
            'comentario_admin' => 'nullable|string|max:255',
        ]);

        $jefe = $request->user()->jefeCarrera;
        abort_unless($jefe, 403, 'No tienes un perfil de jefatura asignado.');

        DB::transaction(function () use ($acceso, $jefe, $id, $datos) {
            // findOrFail devuelve 404 si el ID existe pero pertenece a otra carrera.
            // lockForUpdate evita que dos jefes procesen simultaneamente la solicitud.
            $licencia = $acceso->filtrarLicencias(Licencia::query(), $jefe)
                ->lockForUpdate()
                ->findOrFail($id);

            abort_if($licencia->estado !== 'pendiente', 409, 'La licencia ya fue procesada.');

            $licencia->update([
                'estado' => $datos['estado'],
                'comentario_admin' => $datos['comentario_admin'] ?? null,
                'jefe_carrera_id' => $jefe->id,
            ]);

            if ($datos['estado'] === 'aprobada') {
                Asistencia::where('estudiante_id', $licencia->estudiante_id)
                    ->whereBetween('fecha', [$licencia->fecha_inicio, $licencia->fecha_fin])
                    ->whereIn('estado', ['falta', 'atraso'])
                    ->update([
                        'estado' => 'licencia',
                        'observacion' => 'Justificado por Licencia #' . $licencia->id . ': ' . $licencia->motivo,
                    ]);
            }
        });

        return redirect()->back()->with('success', 'Licencia procesada correctamente.');
    }

    // 4. Formulario Reportes
    public function showReportForm()
    {
        return view('jefe.reportes.index');
    }

    // 5. Reporte PDF limitado a las mismas licencias que puede gestionar el jefe.
    public function generateLicenseReport(Request $request, AccesoLicenciasJefaturaService $acceso)
    {
        $datos = $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'estado' => 'required|in:todas,pendiente,aprobada,rechazada',
        ]);

        $jefe = $request->user()->jefeCarrera;
        $query = $acceso->filtrarLicencias(Licencia::query(), $jefe)
            ->whereBetween('fecha_inicio', [$datos['fecha_inicio'], $datos['fecha_fin']]);

        if ($datos['estado'] !== 'todas') {
            $query->where('estado', $datos['estado']);
        }

        $licencias = $query
            ->with([
                'estudiante.usuario',
                'estudiante.inscripciones.gestion',
                'estudiante.inscripciones.curso',
                'jefeCarrera',
            ])
            ->orderBy('fecha_inicio', 'asc')
            ->get();

        $pdf = Pdf::loadView('reportes.jefe_licencias', [
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_fin' => $datos['fecha_fin'],
            'estado' => $datos['estado'],
            'licencias' => $licencias,
        ]);

        return $pdf->stream('Reporte_Licencias.pdf');
    }
}
