<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Materia;
use App\Models\Curso; // <-- Necesitamos esto para el select

class MateriaController extends Controller
{
    public function index()
    {
        // Traemos materias CON su curso (para mostrar "Matemáticas - Primero A")
        $materias = Materia::with('curso')->get(); 
        return view('admin.materias.index', compact('materias'));
    }

    public function create()
    {
        $cursos = Curso::all(); // Para el combo-box
        return view('admin.materias.create', compact('cursos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'sigla' => 'nullable|string|max:20',
            'curso_id' => 'required|exists:cursos,id', // Debe elegir un curso válido
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