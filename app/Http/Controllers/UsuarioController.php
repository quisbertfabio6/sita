<?php

namespace App\Http\Controllers;

use App\Models\Rol; 
use Illuminate\Support\Facades\Hash; 
use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Docente;      
use App\Models\JefeCarrera;  

class UsuarioController extends Controller
{
    public function index(Request $request) {
        $query = Usuario::with('rol');

        // 1. FILTRO POR TIPO DE KPI (Plantel o Todos)
        // Si no mandan nada, por defecto muestra solo el plantel para no saturar
        $filtro = $request->get('filtro', 'plantel'); 
        
        if ($filtro === 'plantel') {
            $query->whereHas('rol', function($q) {
                $q->where('nombre', '!=', 'estudiante');
            });
        }

        // 2. LÓGICA DEL BUSCADOR POR NOMBRE
        if ($request->has('buscar') && $request->buscar != '') {
            $query->where('nombre_completo', 'LIKE', '%' . $request->buscar . '%');
        }

        $usuarios = $query->get();
        
        return view('admin.usuarios.index', compact('usuarios', 'filtro'));
    }
    
    public function create() {
        $roles = Rol::all();
        return view('admin.usuarios.create', compact('roles'));
    }

    public function store(Request $request) {
        $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email',
            'password' => 'required|min:6',
            'rol_id' => 'required|exists:roles,id'
        ]);

        $usuario = Usuario::create([
            'nombre_completo' => $request->nombre_completo,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol_id' => $request->rol_id,
            'activo' => true
        ]);

        $rol = Rol::find($request->rol_id);

        if ($rol->nombre === 'docente') {
            Docente::create([
                'user_id' => $usuario->id,
                'codigo_docente' => 'DOC-' . str_pad($usuario->id, 4, '0', STR_PAD_LEFT)
            ]);
        } 
        elseif ($rol->nombre === 'jefe_carrera') {
            $carrerasString = "Sin asignar";
            if ($request->has('carreras')) {
                $carrerasString = implode(', ', $request->carreras);
            }
            JefeCarrera::create([
                'user_id' => $usuario->id,
                'carrera_asignada' => $carrerasString
            ]);
        }
        
        return redirect()->route('usuarios.index')->with('success', 'Usuario registrado correctamente.');
    }

    public function edit($id) {
        $usuario = Usuario::findOrFail($id);
        $roles = Rol::all();
        return view('admin.usuarios.edit', compact('usuario', 'roles'));
    }

    public function update(Request $request, $id) {
        $usuario = Usuario::findOrFail($id);

        $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email,'.$usuario->id,
            'rol_id' => 'required|exists:roles,id'
        ]);

        $usuario->nombre_completo = $request->nombre_completo;
        $usuario->email = $request->email;
        $usuario->rol_id = $request->rol_id;
        $usuario->activo = $request->has('activo');

        if ($request->filled('password')) {
            $usuario->password = Hash::make($request->password);
        }

        $usuario->save();

        $rol = Rol::find($request->rol_id);

        if ($rol->nombre === 'jefe_carrera') {
            $carrerasString = "Sin asignar";
            if ($request->has('carreras')) {
                $carrerasString = implode(', ', $request->carreras);
            }
            JefeCarrera::updateOrCreate(
                ['user_id' => $usuario->id], 
                ['carrera_asignada' => $carrerasString] 
            );
        }
        elseif ($rol->nombre === 'docente') {
            Docente::firstOrCreate(
                ['user_id' => $usuario->id],
                ['codigo_docente' => 'DOC-' . str_pad($usuario->id, 4, '0', STR_PAD_LEFT)]
            );
        }

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy($id) {
        $usuario = Usuario::findOrFail($id);
        $usuario->delete(); 
        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado correctamente.');
    }
}