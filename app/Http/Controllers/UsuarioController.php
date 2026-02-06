<?php

namespace App\Http\Controllers;

use App\Models\Rol; 
use Illuminate\Support\Facades\Hash; 
use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Docente;      // Necesario para crear el perfil docente
use App\Models\JefeCarrera;  // Necesario para crear el perfil jefe

class UsuarioController extends Controller
{
    public function index() {
        // Traemos todos los usuarios con su rol
        $usuarios = Usuario::with('rol')->get();
        return view('admin.usuarios.index', compact('usuarios'));
    }
    
    public function create() {
        // Necesitamos los roles para el "select" del formulario
        $roles = Rol::all();
        return view('admin.usuarios.create', compact('roles'));
    }

    // 2. Guarda el usuario en la BD (MODIFICADO CON LÓGICA DE CARRERAS)
    public function store(Request $request) {
        // Validamos los datos
        $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email',
            'password' => 'required|min:6',
            'rol_id' => 'required|exists:roles,id'
        ]);

        // 1. Creamos el usuario principal (Login)
        $usuario = Usuario::create([
            'nombre_completo' => $request->nombre_completo,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Encriptamos la contraseña
            'rol_id' => $request->rol_id,
            'activo' => true
        ]);

        // 2. LÓGICA AUTOMÁTICA: Creamos el perfil según el rol
        $rol = Rol::find($request->rol_id);

        if ($rol->nombre === 'docente') {
            // Si es docente, creamos su registro en la tabla 'docentes'
            Docente::create([
                'user_id' => $usuario->id,
                'codigo_docente' => 'DOC-' . str_pad($usuario->id, 4, '0', STR_PAD_LEFT) // Ej: DOC-0005
            ]);
        } 
        elseif ($rol->nombre === 'jefe_carrera') {
            // Lógica para capturar las carreras seleccionadas en los checkboxes
            $carrerasString = "Sin asignar";
            
            if ($request->has('carreras')) {
                // Une el array ["Sistemas", "Electrónica"] en un string "Sistemas, Electrónica"
                $carrerasString = implode(', ', $request->carreras);
            }

            // Creamos el perfil de Jefe con las carreras asignadas
            JefeCarrera::create([
                'user_id' => $usuario->id,
                'carrera_asignada' => $carrerasString
            ]);
        }
        
        // Nota: Los estudiantes se importan, no se crean manual por aquí usualmente.

        // Redirigimos a la lista con un mensaje de éxito
        return redirect()->route('usuarios.index')->with('success', 'Usuario registrado correctamente.');
    }

    // 3. Muestra el formulario de EDICIÓN
    public function edit($id) {
        $usuario = Usuario::findOrFail($id); // Busca el usuario o falla si no existe
        $roles = Rol::all();
        return view('admin.usuarios.edit', compact('usuario', 'roles'));
    }

// 4. Actualiza los datos en la BD
public function update(Request $request, $id) {
    $usuario = Usuario::findOrFail($id);

    $request->validate([
        'nombre_completo' => 'required|string|max:255',
        'email' => 'required|email|unique:usuarios,email,'.$usuario->id,
        'rol_id' => 'required|exists:roles,id'
    ]);

    // Actualizamos datos básicos del Usuario
    $usuario->nombre_completo = $request->nombre_completo;
    $usuario->email = $request->email;
    $usuario->rol_id = $request->rol_id;
    $usuario->activo = $request->has('activo');

    if ($request->filled('password')) {
        $usuario->password = Hash::make($request->password);
    }

    $usuario->save();

    // --- LÓGICA DE PERFILES (JEFE Y DOCENTE) ---
    $rol = Rol::find($request->rol_id);

    // CASO 1: Si el rol es (o ahora es) Jefe de Carrera
    if ($rol->nombre === 'jefe_carrera') {
        // Capturamos las carreras del formulario de edición
        $carrerasString = "Sin asignar";
        if ($request->has('carreras')) {
            $carrerasString = implode(', ', $request->carreras);
        }

        // 'updateOrCreate' es mágico: Si ya existe el perfil lo actualiza, si no, lo crea.
        JefeCarrera::updateOrCreate(
            ['user_id' => $usuario->id], // Búscalo por ID de usuario
            ['carrera_asignada' => $carrerasString] // Actualiza este campo
        );
    }
    
    // CASO 2: Si el rol es (o ahora es) Docente
    elseif ($rol->nombre === 'docente') {
        // Nos aseguramos de que tenga perfil de docente (firstOrCreate lo busca o lo crea)
        Docente::firstOrCreate(
            ['user_id' => $usuario->id],
            ['codigo_docente' => 'DOC-' . str_pad($usuario->id, 4, '0', STR_PAD_LEFT)]
        );
    }

    return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
}

    // 5. Eliminar usuario
    public function destroy($id) {
        $usuario = Usuario::findOrFail($id);
        $usuario->delete(); 
        // Gracias al "ON DELETE CASCADE" de la base de datos, 
        // al borrar el usuario se borra solo el perfil de docente/jefe.
        
        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado correctamente.');
    }
}