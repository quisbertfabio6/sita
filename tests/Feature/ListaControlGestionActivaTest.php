<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\Inscripcion;
use App\Models\Licencia;
use App\Models\Materia;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ListaControlGestionActivaTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuario(string $rol, string $email): Usuario
    {
        $rolModelo = Rol::firstOrCreate(['nombre' => $rol]);

        return Usuario::create([
            'nombre_completo' => 'Usuario de prueba',
            'email' => $email,
            'password' => Hash::make('clave-prueba-123'),
            'rol_id' => $rolModelo->id,
            'activo' => true,
        ]);
    }

    private function prepararDocente(): array
    {
        $usuario = $this->crearUsuario('docente', 'docente@sita.test');
        $docente = Docente::create(['user_id' => $usuario->id]);
        $curso = Curso::create(['nombre' => 'Primero A', 'gestion' => '2026']);
        $materia = Materia::create(['nombre' => 'Matematicas', 'curso_id' => $curso->id]);
        $docente->materias()->attach($materia->id);
        Sanctum::actingAs($usuario);

        return [$curso, $materia];
    }

    private function inscribir(Curso $curso, Gestion $gestion, string $codigo, string $estado = 'Activo'): Estudiante
    {
        $usuario = $this->crearUsuario('estudiante', 'alumno-' . $codigo . '@sita.test');
        $estudiante = Estudiante::create([
            'user_id' => $usuario->id,
            'codigo_qr' => 'QR-' . $codigo,
            'matricula' => 'MAT-' . $codigo,
        ]);
        Inscripcion::create([
            'estudiante_id' => $estudiante->id,
            'curso_id' => $curso->id,
            'gestion_id' => $gestion->id,
            'estado' => $estado,
        ]);

        return $estudiante;
    }

    private function registrar(Estudiante $estudiante, Materia $materia, string $fecha, string $estado): void
    {
        Asistencia::create([
            'estudiante_id' => $estudiante->id,
            'materia_id' => $materia->id,
            'fecha' => $fecha,
            'hora' => '08:00:00',
            'estado' => $estado,
        ]);
    }

    public function test_lista_muestra_solo_inscripciones_activas_de_la_gestion_actual(): void
    {
        [$curso, $materia] = $this->prepararDocente();
        $anterior = Gestion::create(['anio' => '2025', 'es_activa' => false]);
        $actual = Gestion::create(['anio' => '2026', 'es_activa' => true]);

        $vigente = $this->inscribir($curso, $actual, 'VIGENTE');
        $this->inscribir($curso, $anterior, 'ANTERIOR');
        $this->inscribir($curso, $actual, 'RETIRADO', 'Retirado');

        $this->postJson('/api/docente/lista-control', [
            'materia_id' => $materia->id,
            'fecha' => '2026-05-02',
        ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $vigente->id)
            ->assertJsonPath('data.0.porcentaje', 0)
            ->assertJsonPath('data.0.riesgo', false);
    }

    public function test_porcentaje_no_mezcla_asistencias_de_otros_anios(): void
    {
        [$curso, $materia] = $this->prepararDocente();
        $actual = Gestion::create(['anio' => '2026', 'es_activa' => true]);
        $alumno = $this->inscribir($curso, $actual, 'PORCENTAJE');

        $this->registrar($alumno, $materia, '2025-05-01', 'presente');
        $this->registrar($alumno, $materia, '2026-05-01', 'presente');
        $this->registrar($alumno, $materia, '2026-05-02', 'atraso');

        $this->postJson('/api/docente/lista-control', [
            'materia_id' => $materia->id,
            'fecha' => '2026-05-02',
        ])
            ->assertOk()
            ->assertJsonPath('data.0.estado_hoy', 'atraso')
            ->assertJsonPath('data.0.porcentaje', 75)
            ->assertJsonPath('data.0.riesgo', true);
    }

    public function test_licencia_aprobada_cuenta_en_su_fecha_y_no_en_otras(): void
    {
        [$curso, $materia] = $this->prepararDocente();
        $actual = Gestion::create(['anio' => '2026', 'es_activa' => true]);
        $alumno = $this->inscribir($curso, $actual, 'LICENCIA');

        $this->registrar($alumno, $materia, '2026-05-01', 'presente');
        $this->registrar($alumno, $materia, '2026-05-02', 'licencia');
        Licencia::create([
            'estudiante_id' => $alumno->id,
            'motivo' => 'Justificacion de prueba',
            'fecha_inicio' => '2026-05-02',
            'fecha_fin' => '2026-05-02',
            'estado' => 'aprobada',
        ]);

        $this->postJson('/api/docente/lista-control', [
            'materia_id' => $materia->id,
            'fecha' => '2026-05-02',
        ])
            ->assertOk()
            ->assertJsonPath('data.0.estado_hoy', 'licencia')
            ->assertJsonPath('data.0.porcentaje', 100);
    }

    public function test_fechas_de_clases_pertenecen_solo_a_la_gestion_activa(): void
    {
        [$curso, $materia] = $this->prepararDocente();
        $actual = Gestion::create(['anio' => '2026', 'es_activa' => true]);
        $alumno = $this->inscribir($curso, $actual, 'FECHAS');

        $this->registrar($alumno, $materia, '2025-05-01', 'presente');
        $this->registrar($alumno, $materia, '2026-05-02', 'presente');

        $this->postJson('/api/docente/fechas-clases', ['materia_id' => $materia->id])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0', '2026-05-02');
    }

    public function test_sin_gestion_activa_la_lista_esta_vacia(): void
    {
        [, $materia] = $this->prepararDocente();

        $this->postJson('/api/docente/lista-control', [
            'materia_id' => $materia->id,
            'fecha' => '2026-05-02',
        ])
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_usuario_sin_materia_asignada_no_puede_ver_lista(): void
    {
        [, $materia] = $this->prepararDocente();
        Gestion::create(['anio' => '2026', 'es_activa' => true]);
        $otroUsuario = $this->crearUsuario('docente', 'otro@sita.test');
        Docente::create(['user_id' => $otroUsuario->id]);
        Sanctum::actingAs($otroUsuario);

        $this->postJson('/api/docente/lista-control', [
            'materia_id' => $materia->id,
            'fecha' => '2026-05-02',
        ])->assertForbidden();

        $this->postJson('/api/docente/fechas-clases', [
            'materia_id' => $materia->id,
        ])->assertForbidden();
    }

    public function test_fecha_fuera_de_gestion_activa_es_rechazada(): void
    {
        [, $materia] = $this->prepararDocente();
        Gestion::create(['anio' => '2026', 'es_activa' => true]);

        $this->postJson('/api/docente/lista-control', [
            'materia_id' => $materia->id,
            'fecha' => '2025-05-02',
        ])->assertStatus(422);
    }
}
