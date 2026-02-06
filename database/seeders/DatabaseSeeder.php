<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear los Roles del Sistema
        // Usamos create() para que se guarden en la BD
        $rolAdmin = Rol::create(['nombre' => 'administrador', 'descripcion' => 'Control total del sistema']);
        $rolJefe = Rol::create(['nombre' => 'jefe_carrera', 'descripcion' => 'Valida licencias']);
        $rolDocente = Rol::create(['nombre' => 'docente', 'descripcion' => 'Registra asistencia']);
        $rolEstudiante = Rol::create(['nombre' => 'estudiante', 'descripcion' => 'Solicita licencias']);

        // 2. Crear el Usuario Administrador Principal
        Usuario::create([
            'nombre_completo' => 'Administrador Principal',
            'email' => 'admin@ita.edu.bo', // Este será tu correo para entrar
            'password' => Hash::make('admin123'), // Esta será tu contraseña
            'rol_id' => $rolAdmin->id,
            'activo' => true,
        ]);

        echo "¡Base de datos inicializada con éxito! Usuario Admin creado.\n";
    }
}