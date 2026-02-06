<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asignacion; // Recuerda que creamos este modelo apuntando a 'materia_docente'
use App\Models\Docente;
use App\Models\Materia;

class AsignacionController extends Controller
{
    public function index()
    {
        // Traemos la asignación con los datos del docente (usuario) y la materia (curso)
        $asignaciones = Asignacion::with(['docente.usuario', 'materia.curso'])->get();
        return view('admin.asignaciones.index', compact('asignaciones'));
    }

    public function create()
    {
        // Listamos docentes y materias para los selectores
        $docentes = Docente::with('usuario')->get();
        // Ordenamos las materias por nombre para que sea más fácil buscar
        $materias = Materia::with('curso')->orderBy('nombre')->get();
        
        return view('admin.asignaciones.create', compact('docentes', 'materias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'docente_id' => 'required|exists:docentes,id',
            'materia_id' => 'required|exists:materias,id',
        ]);

        // Validación extra: Evitar duplicados
        $existe = Asignacion::where('docente_id', $request->docente_id)
                            ->where('materia_id', $request->materia_id)
                            ->exists();

        if ($existe) {
            return back()->withErrors(['error' => 'Este docente ya tiene asignada esta materia.']);
        }

        // Guardamos en la tabla pivote
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