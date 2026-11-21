<?php

namespace Tests\Feature;

use App\Imports\EstudiantesImport;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InscripcionesEstudiantesTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuario(string $rol, string $email): Usuario
    {
        $rolModelo = Rol::firstOrCreate(['nombre' => $rol]);

        return Usuario::create([
            'nombre_completo' => 'Usuario de prueba',
            'email' => $email,
            'password' => Hash::make('clave-segura-123'),
            'rol_id' => $rolModelo->id,
            'activo' => true,
        ]);
    }

    public function test_registro_manual_crea_inscripcion_en_gestion_activa(): void
    {
        $this->actingAs($this->crearUsuario('administrador', 'admin@sita.test'));
        $gestion = Gestion::create(['anio' => '2026', 'es_activa' => true]);
        $curso = Curso::create(['nombre' => 'Primero A', 'gestion' => '2026']);

        $this->post(route('estudiantes.store'), [
            'nombre_completo' => 'Estudiante de prueba',
            'email' => 'alumno@sita.test',
            'password' => 'clave-segura-123',
            'ci' => 'CI-PRUEBA-1',
            'matricula' => 'MAT-PRUEBA-1',
            'codigo_qr' => 'QR-PRUEBA-1',
            'curso_id' => $curso->id,
        ])->assertRedirect(route('estudiantes.index'));

        $estudiante = Estudiante::where('codigo_qr', 'QR-PRUEBA-1')->firstOrFail();
        $this->assertDatabaseHas('inscripciones', [
            'estudiante_id' => $estudiante->id,
            'curso_id' => $curso->id,
            'gestion_id' => $gestion->id,
            'estado' => 'Activo',
        ]);
        $this->assertSame($curso->id, $estudiante->cursoActual()?->id);
    }

    public function test_registro_manual_sin_gestion_activa_no_crea_registros_incompletos(): void
    {
        $this->actingAs($this->crearUsuario('administrador', 'admin@sita.test'));
        $curso = Curso::create(['nombre' => 'Primero A', 'gestion' => '2026']);

        $this->post(route('estudiantes.store'), [
            'nombre_completo' => 'Estudiante de prueba',
            'email' => 'alumno@sita.test',
            'password' => 'clave-segura-123',
            'codigo_qr' => 'QR-PRUEBA-2',
            'curso_id' => $curso->id,
        ])->assertSessionHasErrors('gestion');

        $this->assertDatabaseCount('estudiantes', 0);
        $this->assertDatabaseMissing('usuarios', ['email' => 'alumno@sita.test']);
    }

    public function test_edicion_actualiza_solo_inscripcion_de_gestion_activa(): void
    {
        $this->actingAs($this->crearUsuario('administrador', 'admin@sita.test'));
        $gestionAnterior = Gestion::create(['anio' => '2025', 'es_activa' => false]);
        $gestionActual = Gestion::create(['anio' => '2026', 'es_activa' => true]);
        $cursoAnterior = Curso::create(['nombre' => 'Primero A', 'gestion' => '2025']);
        $cursoActual = Curso::create(['nombre' => 'Segundo A', 'gestion' => '2026']);
        $cursoNuevo = Curso::create(['nombre' => 'Segundo B', 'gestion' => '2026']);
        $usuario = $this->crearUsuario('estudiante', 'alumno@sita.test');
        $estudiante = Estudiante::create([
            'user_id' => $usuario->id,
            'codigo_qr' => 'QR-PRUEBA-3',
        ]);
        Inscripcion::create([
            'estudiante_id' => $estudiante->id,
            'curso_id' => $cursoAnterior->id,
            'gestion_id' => $gestionAnterior->id,
            'estado' => 'Activo',
        ]);
        Inscripcion::create([
            'estudiante_id' => $estudiante->id,
            'curso_id' => $cursoActual->id,
            'gestion_id' => $gestionActual->id,
            'estado' => 'Activo',
        ]);

        $this->put(route('estudiantes.update', $estudiante->id), [
            'nombre_completo' => 'Estudiante actualizado',
            'email' => 'alumno@sita.test',
            'codigo_qr' => 'QR-PRUEBA-3',
            'curso_id' => $cursoNuevo->id,
        ])->assertRedirect(route('estudiantes.index'));

        $this->assertDatabaseHas('inscripciones', [
            'estudiante_id' => $estudiante->id,
            'curso_id' => $cursoAnterior->id,
            'gestion_id' => $gestionAnterior->id,
        ]);
        $this->assertDatabaseHas('inscripciones', [
            'estudiante_id' => $estudiante->id,
            'curso_id' => $cursoNuevo->id,
            'gestion_id' => $gestionActual->id,
        ]);
        $this->assertSame(2, $estudiante->inscripciones()->count());
    }

    public function test_api_materias_consulta_curso_de_gestion_activa(): void
    {
        $gestion = Gestion::create(['anio' => '2026', 'es_activa' => true]);
        $curso = Curso::create(['nombre' => 'Primero A', 'gestion' => '2026']);
        $materia = Materia::create(['nombre' => 'Matemáticas', 'curso_id' => $curso->id]);
        $usuario = $this->crearUsuario('estudiante', 'alumno@sita.test');
        $estudiante = Estudiante::create([
            'user_id' => $usuario->id,
            'codigo_qr' => 'QR-PRUEBA-4',
        ]);
        Inscripcion::create([
            'estudiante_id' => $estudiante->id,
            'curso_id' => $curso->id,
            'gestion_id' => $gestion->id,
            'estado' => 'Activo',
        ]);

        Sanctum::actingAs($usuario);
        $this->getJson('/api/estudiante/materias')
            ->assertOk()
            ->assertJsonPath('data.0.id', $materia->id)
            ->assertJsonPath('data.0.nombre', 'Matemáticas');
    }

    public function test_api_materias_sin_inscripcion_devuelve_lista_vacia(): void
    {
        Gestion::create(['anio' => '2026', 'es_activa' => true]);
        $usuario = $this->crearUsuario('estudiante', 'alumno@sita.test');
        Estudiante::create([
            'user_id' => $usuario->id,
            'codigo_qr' => 'QR-PRUEBA-5',
        ]);

        Sanctum::actingAs($usuario);
        $this->getJson('/api/estudiante/materias')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }
    public function test_importacion_crea_inscripcion_en_gestion_activa(): void
    {
        Rol::create(['nombre' => 'estudiante']);
        $gestion = Gestion::create(['anio' => '2026', 'es_activa' => true]);
        $curso = Curso::create(['nombre' => 'Primero A', 'gestion' => '2026']);

        (new EstudiantesImport())->model([
            'nombre_completo' => 'Estudiante importado',
            'email' => 'importado@sita.test',
            'ci' => 'CI-IMPORTADO',
            'matricula' => 'MAT-IMPORTADO',
            'codigo_qr' => 'QR-IMPORTADO',
            'id_curso' => $curso->id,
        ]);

        $estudiante = Estudiante::where('codigo_qr', 'QR-IMPORTADO')->firstOrFail();
        $this->assertDatabaseHas('inscripciones', [
            'estudiante_id' => $estudiante->id,
            'curso_id' => $curso->id,
            'gestion_id' => $gestion->id,
        ]);
    }

    public function test_importacion_sin_gestion_activa_es_rechazada(): void
    {
        $curso = Curso::create(['nombre' => 'Primero A', 'gestion' => '2026']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        (new EstudiantesImport())->model([
            'nombre_completo' => 'Estudiante importado',
            'email' => 'importado@sita.test',
            'ci' => 'CI-IMPORTADO',
            'matricula' => 'MAT-IMPORTADO',
            'codigo_qr' => 'QR-IMPORTADO',
            'id_curso' => $curso->id,
        ]);
    }

}
