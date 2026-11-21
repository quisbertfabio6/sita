<?php

namespace App\Imports;

use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EstudiantesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $gestionActiva = Gestion::where('es_activa', true)->first();
        if (!$gestionActiva) {
            throw ValidationException::withMessages([
                'gestion' => 'Debe iniciar una gestión académica antes de importar estudiantes.',
            ]);
        }

        if (empty($row['id_curso']) || !Curso::whereKey($row['id_curso'])->exists()) {
            throw ValidationException::withMessages([
                'id_curso' => 'El curso indicado en el archivo no existe.',
            ]);
        }

        $rolEstudiante = Rol::where('nombre', 'estudiante')->firstOrFail();

        // Se guardan usuario, estudiante e inscripción juntos, o ninguno.
        DB::transaction(function () use ($row, $rolEstudiante, $gestionActiva) {
            $usuario = Usuario::create([
                'nombre_completo' => $row['nombre_completo'],
                'email' => $row['email'],
                // Contraseña inicial heredada: pendiente de mejora de seguridad.
                'password' => Hash::make($row['ci']),
                'rol_id' => $rolEstudiante->id,
                'activo' => true,
            ]);

            $estudiante = Estudiante::create([
                'user_id' => $usuario->id,
                'ci' => $row['ci'],
                'matricula' => $row['matricula'],
                'codigo_qr' => $row['codigo_qr'],
            ]);

            $estudiante->inscripciones()->create([
                'curso_id' => $row['id_curso'],
                'gestion_id' => $gestionActiva->id,
                'estado' => 'Activo',
            ]);
        });

        // El registro ya fue guardado; evitar que Excel intente guardarlo otra vez.
        return null;
    }
}
