<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Curso;

class CursoController extends Controller
{
    // 1. Listar Cursos
    public function index()
    {
        $cursos = Curso::all(); // Trae todos los cursos
        return view('admin.cursos.index', compact('cursos'));
    }

    // 2. Mostrar Formulario de Creación
    public function create()
    {
        return view('admin.cursos.create');
    }

    // 3. Guardar Nuevo Curso
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'gestion' => 'required|string|max:20', // Ej: "1-2025"
        ]);

        Curso::create($request->all());

        return redirect()->route('cursos.index')->with('success', 'Curso creado correctamente.');
    }

    // 4. Mostrar Formulario de Edición
    public function edit($id)
    {
        $curso = Curso::findOrFail($id);
        return view('admin.cursos.edit', compact('curso'));
    }

    // 5. Actualizar Curso
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

    // 6. Eliminar Curso
    public function destroy($id)
    {
        $curso = Curso::findOrFail($id);
        $curso->delete();

        return redirect()->route('cursos.index')->with('success', 'Curso eliminado correctamente.');
    }
}