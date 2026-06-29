<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Curso;

class CursoController extends Controller
{
    public function index(Request $request)
    {
        $query = Curso::query();

        // LÓGICA DEL BUSCADOR
        if ($request->has('buscar') && $request->buscar != '') {
            $query->where('nombre', 'LIKE', '%' . $request->buscar . '%');
        }

        $cursos = $query->get(); 
        return view('admin.cursos.index', compact('cursos'));
    }

    public function create()
    {
        return view('admin.cursos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'gestion' => 'required|string|max:20', 
        ]);

        Curso::create($request->all());

        return redirect()->route('cursos.index')->with('success', 'Curso creado correctamente.');
    }

    public function edit($id)
    {
        $curso = Curso::findOrFail($id);
        return view('admin.cursos.edit', compact('curso'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'gestion' => 'required|string|max:20',
        ]);

        $curso = Curso::findOrFail($id);
        $curso->update($request->all());

        return redirect()->route('cursos.index')->with('success', 'Curso actualizado correctamente.');
    }

    public function destroy($id)
    {
        $curso = Curso::findOrFail($id);
        $curso->delete();

        return redirect()->route('cursos.index')->with('success', 'Curso eliminado correctamente.');
    }
}