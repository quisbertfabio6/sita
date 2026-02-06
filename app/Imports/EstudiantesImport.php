<?php

namespace App\Imports;

use App\Models\Estudiante;
use App\Models\Usuario;
use App\Models\Rol;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EstudiantesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Buscamos el rol de estudiante
        $rolEstudiante = Rol::where('nombre', 'estudiante')->first();

        // 1. Crear Usuario
        $usuario = Usuario::create([
            'nombre_completo' => $row['nombre_completo'],
            'email'           => $row['email'],
            // La contraseña por defecto será su CI
            'password'        => Hash::make($row['ci']), 
            'rol_id'          => $rolEstudiante->id,
            'activo'          => true,
        ]);

        // 2. Crear Perfil Estudiante
        return new Estudiante([
            'user_id'   => $usuario->id,
            'curso_id'  => $row['id_curso'], // ID del curso en tu BD
            'ci'        => $row['ci'],
            'matricula' => $row['matricula'],
            'codigo_qr' => $row['codigo_qr'],
        ]);
    }
}