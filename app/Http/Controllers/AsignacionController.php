<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asignacion; 
use App\Models\Docente;
use App\Models\Materia;

class AsignacionController extends Controller
{
    public function index(Request $request)
    {
        $query = Asignacion::with(['docente.usuario', 'materia.curso']);

        // LÓGICA DEL BUSCADOR (Busca por nombre del docente o de la materia)
        if ($request->has('buscar') && $request->buscar != '') {
            $buscar = $request->buscar;
            $query->whereHas('docente.usuario', function($q) use ($buscar) {
                $q->where('nombre_completo', 'LIKE', '%' . $buscar . '%');
            })->orWhereHas('materia', function($q) use ($buscar) {
                $q->where('nombre', 'LIKE', '%' . $buscar . '%');
            });
        }

        $asignaciones = $query->get();
        return view('admin.asignaciones.index', compact('asignaciones'));
    }

    public function create()
    {
        $docentes = Docente::with('usuario')->get();
        $materias = Materia::with('curso')->orderBy('nombre')->get();
        
        return view('admin.asignaciones.create', compact('docentes', 'materias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'docente_id' => 'required|exists:docentes,id',
            'materia_id' => 'required|exists:materias,id',
        ]);

        $existe = Asignacion::where('docente_id', $request->docente_id)
                            ->where('materia_id', $request->materia_id)
                            ->exists();

        if ($existe) {
            return back()->withErrors(['error' => 'Este docente ya tiene asignada esta materia.']);
        }

        $asignacion = new Asignacion();
        $asignacion->docente_id = $request->docente_id;
        $asignacion->materia_id = $request->materia_id;
        $asignacion->save();

        return redirect()->route('asignaciones.index')->with('success', 'Docente asignado correctamente.');
    }

    public function destroy($id)
    {
        $asignacion = Asignacion::findOrFail($id);
        $asignacion->delete();
        return redirect()->route('asignaciones.index')->with('success', 'Asignación eliminada.');
    }
}