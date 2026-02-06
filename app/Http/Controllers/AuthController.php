<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    // Muestra la vista de login
    public function showLogin() {
        return view('auth.login');
    }

    // Procesa el formulario de login
    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Intentar autenticar usando el guard por defecto (web)
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Redirección según ROL
            $user = Auth::user();
            
            // Usamos los nombres de roles que definimos en el Seeder
            // (Asegúrate que en tu modelo Usuario tengas la relación 'rol')
            $rol = $user->rol->nombre; 

            switch ($rol) {
                case 'administrador':
                    return redirect()->route('admin.dashboard');
                case 'jefe_carrera':
                    return redirect()->route('jefe.dashboard');
                case 'docente':
                    // Los docentes suelen usar más la App, pero pueden tener panel web
                    return redirect()->route('docente.dashboard'); 
                case 'estudiante':
                    return redirect()->route('estudiante.dashboard');
                default:
                    Auth::logout();
                    return back()->withErrors(['email' => 'Rol no reconocido.']);
            }
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    // Cerrar sesión
    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}