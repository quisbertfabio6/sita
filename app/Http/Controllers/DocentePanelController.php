<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Materia;
use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Models\Gestion;

class DocentePanelController extends Controller
{
    // 1. Dashboard: Muestra las materias asignadas
    public function index()
    {
        $user = Auth::user();
        $docente = $user->docente;

        if (!$docente) {
            return abort(403, 'No tienes un perfil de docente asignado.');
        }

        // Traemos las materias que dicta este docente
        $materiasAsignadas = $docente->materias()->with('curso')->get();

        return view('docente.dashboard', compact('materiasAsignadas'));
    }

    // 2. Ver la lista de un curso específico
    public function verCurso($idMateria)
    {
        $user = Auth::user();
        $docente = $user->docente;

        // Verificamos que esa materia realmente le pertenezca (Seguridad)
        $materia = $docente->materias()->where('materias.id', $idMateria)->with('curso')->firstOrFail();

        // Obtenemos la gestión activa actual
        $gestionActivaId = Gestion::where('es_activa', true)->value('id');

        // Buscamos a los estudiantes inscritos en este curso durante la gestión activa
        // Si no hay inscritos, devolverá una colección vacía sin arrojar error 500
        $estudiantes = Estudiante::whereHas('inscripciones', function($query) use ($materia, $gestionActivaId) {
                $query->where('curso_id', $materia->curso_id)
                      ->where('gestion_id', $gestionActivaId);
            })
            ->with([
                'usuario', 
                'asistencias' => function($q) use ($idMateria) {
                    $q->where('materia_id', $idMateria)->latest()->take(3);
                }, 
                'licencias' => function($q) {
                    $q->where('estado', 'aprobada');
                }
            ])
            ->get()
            ->sortBy(fn($est) => $est->usuario->nombre_completo ?? '')
            ->values();

        return view('docente.curso', compact('materia', 'estudiantes'));
    }
}