<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Estudiante;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Curso;
use App\Models\Gestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Imports\EstudiantesImport; 
use Maatwebsite\Excel\Facades\Excel; 

class EstudianteController extends Controller
{
    public function index(Request $request)
    {
        $query = Estudiante::with(['usuario']);

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
            'password' => ['required', Password::min(12)->letters()->numbers()],
            'ci' => 'nullable|string|unique:estudiantes,ci',
            'matricula' => 'nullable|string|unique:estudiantes,matricula',
            'codigo_qr' => 'required|string|unique:estudiantes,codigo_qr',
            'curso_id' => 'required|exists:cursos,id',
        ]);

        $gestionActiva = Gestion::where('es_activa', true)->first();
        if (!$gestionActiva) {
            return back()->withErrors([
                'gestion' => 'Debe iniciar una gestión académica antes de registrar estudiantes.',
            ])->withInput();
        }

        $rolEstudiante = Rol::where('nombre', 'estudiante')->firstOrFail();

        DB::transaction(function () use ($request, $rolEstudiante, $gestionActiva) {
            $usuario = Usuario::create([
                'nombre_completo' => $request->nombre_completo,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'rol_id' => $rolEstudiante->id,
                'activo' => true,
            ]);

            $estudiante = Estudiante::create([
                'user_id' => $usuario->id,
                'ci' => $request->ci,
                'matricula' => $request->matricula,
                'codigo_qr' => $request->codigo_qr,
            ]);

            $estudiante->inscripciones()->create([
                'curso_id' => $request->curso_id,
                'gestion_id' => $gestionActiva->id,
                'estado' => 'Activo',
            ]);
        });

        return redirect()->route('estudiantes.index')->with('success', 'Estudiante registrado correctamente.');
    }

    public function importar(Request $request) 
    {
        $request->validate([
            'archivo_excel' => 'required|mimes:xlsx,csv',
        ]);

        if (!Gestion::where('es_activa', true)->exists()) {
            return back()->withErrors([
                'gestion' => 'Debe iniciar una gestión académica antes de importar estudiantes.',
            ]);
        }

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
        $gestionActiva = Gestion::where('es_activa', true)->first();
        $inscripcionActiva = $gestionActiva
            ? $estudiante->inscripciones()->where('gestion_id', $gestionActiva->id)->first()
            : null;

        return view('admin.estudiantes.edit', compact(
            'estudiante', 'cursos', 'gestionActiva', 'inscripcionActiva'
        ));
    }

    public function update(Request $request, $id)
    {
        $estudiante = Estudiante::findOrFail($id);
        $usuario = $estudiante->usuario;

        $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email,'.$usuario->id,
            'password' => ['nullable', Password::min(12)->letters()->numbers()],
            'curso_id' => 'required|exists:cursos,id',
            'codigo_qr' => 'required|string|unique:estudiantes,codigo_qr,'.$estudiante->id,
        ]);

        $gestionActiva = Gestion::where('es_activa', true)->first();
        if (!$gestionActiva) {
            return back()->withErrors([
                'gestion' => 'Debe iniciar una gestión académica antes de modificar la inscripción.',
            ])->withInput();
        }

        DB::transaction(function () use ($request, $estudiante, $usuario, $gestionActiva) {
            $usuario->update([
                'nombre_completo' => $request->nombre_completo,
                'email' => $request->email,
            ]);

            if ($request->filled('password')) {
                $usuario->update(['password' => Hash::make($request->password)]);
                $usuario->tokens()->delete();
            }

            $estudiante->update([
                'ci' => $request->ci,
                'matricula' => $request->matricula,
                'codigo_qr' => $request->codigo_qr,
            ]);

            // Modificar solo la inscripción de la gestión activa.
            // Las inscripciones de gestiones anteriores se conservan.
            $estudiante->inscripciones()->updateOrCreate(
                ['gestion_id' => $gestionActiva->id],
                ['curso_id' => $request->curso_id, 'estado' => 'Activo']
            );
        });

        return redirect()->route('estudiantes.index')->with('success', 'Estudiante actualizado.');
    }

    public function destroy($id)
    {
        $estudiante = Estudiante::findOrFail($id);
        $estudiante->usuario->delete();
        return redirect()->route('estudiantes.index')->with('success', 'Estudiante eliminado.');
    }
    // --- INICIO NUEVA FUNCIÓN: REVOCAR QR ---
    public function regenerarQR($id)
    {
        $estudiante = \App\Models\Estudiante::findOrFail($id);
        
        // Generamos un nuevo código único (usando un prefijo y un identificador único aleatorio)
        $nuevoCodigo = 'QR-' . strtoupper(uniqid());
        
        // Actualizamos el estudiante
        $estudiante->codigo_qr = $nuevoCodigo;
        $estudiante->save();

        return redirect()->back()->with('success', 'El código QR anterior ha sido revocado. El nuevo código es: ' . $nuevoCodigo . '. Ya puede imprimir la nueva tarjeta.');
    }
}