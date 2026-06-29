<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Estudiante;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Curso;
use Illuminate\Support\Facades\Hash;
use App\Imports\EstudiantesImport; 
use Maatwebsite\Excel\Facades\Excel; 

class EstudianteController extends Controller
{
    public function index(Request $request)
    {
        $query = Estudiante::with(['usuario', 'curso']);

        // LÓGICA DEL BUSCADOR POR NOMBRE
        if ($request->has('buscar') && $request->buscar != '') {
            $buscar = $request->buscar;
            // Busca a través de la relación "usuario"
            $query->whereHas('usuario', function($q) use ($buscar) {
                $q->where('nombre_completo', 'LIKE', '%' . $buscar . '%');
            });
        }

        $estudiantes = $query->get();
        return view('admin.estudiantes.index', compact('estudiantes'));
    }

    public function create()
    {
        $cursos = Curso::all();
        return view('admin.estudiantes.create', compact('cursos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email',
            'password' => 'required|min:6',
            'ci' => 'nullable|string|unique:estudiantes,ci',
            'matricula' => 'nullable|string|unique:estudiantes,matricula',
            'codigo_qr' => 'required|string|unique:estudiantes,codigo_qr',
            'curso_id' => 'required|exists:cursos,id',
        ]);

        $rolEstudiante = Rol::where('nombre', 'estudiante')->first();

        $usuario = Usuario::create([
            'nombre_completo' => $request->nombre_completo,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol_id' => $rolEstudiante->id,
            'activo' => true,
        ]);

        Estudiante::create([
            'user_id' => $usuario->id,
            'curso_id' => $request->curso_id,
            'ci' => $request->ci,
            'matricula' => $request->matricula,
            'codigo_qr' => $request->codigo_qr,
        ]);

        return redirect()->route('estudiantes.index')->with('success', 'Estudiante registrado correctamente.');
    }

    public function importar(Request $request) 
    {
        $request->validate([
            'archivo_excel' => 'required|mimes:xlsx,csv',
        ]);

        try {
            Excel::import(new EstudiantesImport, $request->file('archivo_excel'));
            return redirect()->route('estudiantes.index')->with('success', '¡Importación masiva completada!');
        } catch (\Exception $e) {
            return back()->withErrors('Error: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $estudiante = Estudiante::findOrFail($id);
        $cursos = Curso::all();
        return view('admin.estudiantes.edit', compact('estudiante', 'cursos'));
    }

    public function update(Request $request, $id)
    {
        $estudiante = Estudiante::findOrFail($id);
        $usuario = $estudiante->usuario;

        $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email,'.$usuario->id,
            'curso_id' => 'required|exists:cursos,id',
            'codigo_qr' => 'required|string|unique:estudiantes,codigo_qr,'.$estudiante->id,
        ]);

        $usuario->update([
            'nombre_completo' => $request->nombre_completo,
            'email' => $request->email,
        ]);
        
        if ($request->filled('password')) {
            $usuario->update(['password' => Hash::make($request->password)]);
        }

        $estudiante->update([
            'curso_id' => $request->curso_id,
            'ci' => $request->ci,
            'matricula' => $request->matricula,
            'codigo_qr' => $request->codigo_qr,
        ]);

        return redirect()->route('estudiantes.index')->with('success', 'Estudiante actualizado.');
    }

    public function destroy($id)
    {
        $estudiante = Estudiante::findOrFail($id);
        $estudiante->usuario->delete();
        return redirect()->route('estudiantes.index')->with('success', 'Estudiante eliminado.');
    }
}