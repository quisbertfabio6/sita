<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Materia;
use App\Models\Curso; 

class MateriaController extends Controller
{
    public function index(Request $request)
    {
        $query = Materia::with('curso');

        // LÓGICA DEL BUSCADOR
        if ($request->has('buscar') && $request->buscar != '') {
            $query->where('nombre', 'LIKE', '%' . $request->buscar . '%');
        }

        $materias = $query->get(); 
        return view('admin.materias.index', compact('materias'));
    }

    public function create()
    {
        $cursos = Curso::all(); 
        return view('admin.materias.create', compact('cursos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'sigla' => 'nullable|string|max:20',
            'curso_id' => 'required|exists:cursos,id', 
        ]);

        Materia::create($request->all());

        return redirect()->route('materias.index')->with('success', 'Materia creada correctamente.');
    }

    public function edit($id)
    {
        $materia = Materia::findOrFail($id);
        $cursos = Curso::all();
        return view('admin.materias.edit', compact('materia', 'cursos'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'sigla' => 'nullable|string|max:20',
            'curso_id' => 'required|exists:cursos,id',
        ]);

        $materia = Materia::findOrFail($id);
        $materia->update($request->all());

        return redirect()->route('materias.index')->with('success', 'Materia actualizada.');
    }

    public function destroy($id)
    {
        $materia = Materia::findOrFail($id);
        $materia->delete();
        return redirect()->route('materias.index')->with('success', 'Materia eliminada.');
    }
}