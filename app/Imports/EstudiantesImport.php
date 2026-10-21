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

        $estudiante = new Estudiante([
            'user_id' => $usuario->id,
            'ci' => $row['ci'],
            'matricula' => $row['matricula'],
            'codigo_qr' => $row['codigo_qr'],
        ]);
        $estudiante->save(); // Lo guardamos manualmente para obtener su ID
    
        // 3. Crear la Inscripción en la Gestión Activa
        $gestionActiva = \App\Models\Gestion::where('es_activa', true)->first();
        
        if ($gestionActiva) {
            \App\Models\Inscripcion::create([
                'estudiante_id' => $estudiante->id,
                'curso_id' => $row['id_curso'], // Asegúrate que el nombre coincide con tu Excel
                'gestion_id' => $gestionActiva->id,
                'estado' => 'Activo'
            ]);
        }
        
        // Retornamos null para que la librería no intente guardar el estudiante por segunda vez
        return null;
    } // <- Esta es la llave que faltaba para cerrar la función model()
} // <- Y esta cierra la clase