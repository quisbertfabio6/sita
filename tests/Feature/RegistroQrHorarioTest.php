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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RegistroQrHorarioTest extends TestCase
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

    private function prepararSistema(?string $horaInicio = '08:00:00'): array
    {
        $usuarioDocente = $this->crearUsuario('docente', 'docente@sita.test');
        $docente = Docente::create(['user_id' => $usuarioDocente->id]);
        $gestion = Gestion::create(['anio' => '2026', 'es_activa' => true]);
        $curso = Curso::create(['nombre' => 'Primero A', 'gestion' => '2026']);
        $materia = Materia::create([
            'nombre' => 'Matematicas',
            'curso_id' => $curso->id,
            'hora_inicio' => $horaInicio,
            'minutos_tolerancia' => 10,
        ]);
        $docente->materias()->attach($materia->id);

        $usuarioAlumno = $this->crearUsuario('estudiante', 'alumno@sita.test');
        $estudiante = Estudiante::create([
            'user_id' => $usuarioAlumno->id,
            'codigo_qr' => 'QR-PRUEBA-HORARIO',
            'matricula' => 'MAT-PRUEBA-HORARIO',
        ]);
        Inscripcion::create([
            'estudiante_id' => $estudiante->id,
            'curso_id' => $curso->id,
            'gestion_id' => $gestion->id,
            'estado' => 'Activo',
        ]);

        Sanctum::actingAs($usuarioDocente);

        return [$materia, $estudiante, $gestion];
    }

    private function enviarQr(Materia $materia, string $hora, string $fecha = '2026-05-02')
    {
        return $this->postJson('/api/docente/registrar-qr', [
            'codigo_qr' => 'QR-PRUEBA-HORARIO',
            'materia_id' => $materia->id,
            'fecha' => $fecha,
            'hora' => $hora,
        ]);
    }

    public function test_registro_antes_de_clase_es_presente(): void
    {
        [$materia, $estudiante] = $this->prepararSistema();

        $this->enviarQr($materia, '07:55')->assertOk();
        $this->assertDatabaseHas('asistencias', [
            'estudiante_id' => $estudiante->id,
            'materia_id' => $materia->id,
            'fecha' => '2026-05-02',
            'hora' => '07:55:00',
            'estado' => 'presente',
        ]);
    }

    public function test_registro_justo_al_inicio_es_presente(): void
    {
        [$materia, $estudiante] = $this->prepararSistema();

        $this->enviarQr($materia, '08:00')->assertOk();
        $this->assertDatabaseHas('asistencias', [
            'estudiante_id' => $estudiante->id,
            'estado' => 'presente',
            'hora' => '08:00:00',
        ]);
    }

    public function test_registro_dentro_de_tolerancia_es_presente(): void
    {
        [$materia, $estudiante] = $this->prepararSistema();

        $this->enviarQr($materia, '08:05')->assertOk();
        $this->assertDatabaseHas('asistencias', [
            'estudiante_id' => $estudiante->id,
            'estado' => 'presente',
            'hora' => '08:05:00',
        ]);
    }

    public function test_registro_exactamente_en_limite_es_presente(): void
    {
        [$materia, $estudiante] = $this->prepararSistema();

        $this->enviarQr($materia, '08:10')->assertOk();
        $this->assertDatabaseHas('asistencias', [
            'estudiante_id' => $estudiante->id,
            'estado' => 'presente',
            'hora' => '08:10:00',
        ]);
    }

    public function test_registro_despues_de_tolerancia_es_atraso(): void
    {
        [$materia, $estudiante] = $this->prepararSistema();

        $this->enviarQr($materia, '08:11')->assertOk();
        $this->assertDatabaseHas('asistencias', [
            'estudiante_id' => $estudiante->id,
            'estado' => 'atraso',
            'hora' => '08:11:00',
        ]);
    }

    public function test_sincronizacion_tardia_usa_hora_original_del_escaneo(): void
    {
        [$materia, $estudiante] = $this->prepararSistema();

        Carbon::setTestNow('2026-11-21 18:00:00');
        try {
            $this->enviarQr($materia, '08:05', '2026-05-02')->assertOk();
        } finally {
            Carbon::setTestNow();
        }

        $this->assertDatabaseHas('asistencias', [
            'estudiante_id' => $estudiante->id,
            'fecha' => '2026-05-02',
            'hora' => '08:05:00',
            'estado' => 'presente',
        ]);
    }

    public function test_rechaza_hora_invalida(): void
    {
        [$materia] = $this->prepararSistema();

        $this->enviarQr($materia, '25:99')->assertUnprocessable();
        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_rechaza_alumno_sin_inscripcion_activa(): void
    {
        [$materia] = $this->prepararSistema();
        Inscripcion::query()->update(['estado' => 'Retirado']);

        $this->enviarQr($materia, '08:05')->assertUnprocessable();
        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_rechaza_docente_sin_materia_asignada(): void
    {
        [$materia] = $this->prepararSistema();
        $otroUsuario = $this->crearUsuario('docente', 'otro@sita.test');
        Docente::create(['user_id' => $otroUsuario->id]);
        Sanctum::actingAs($otroUsuario);

        $this->enviarQr($materia, '08:05')->assertForbidden();
        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_rechaza_licencia_aprobada(): void
    {
        [$materia, $estudiante] = $this->prepararSistema();
        Licencia::create([
            'estudiante_id' => $estudiante->id,
            'motivo' => 'Licencia de prueba',
            'fecha_inicio' => '2026-05-02',
            'fecha_fin' => '2026-05-02',
            'estado' => 'aprobada',
        ]);

        $this->enviarQr($materia, '08:05')->assertStatus(409);
        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_rechaza_materia_sin_horario(): void
    {
        [$materia] = $this->prepararSistema(null);

        $this->enviarQr($materia, '08:05')->assertUnprocessable();
        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_rechaza_fecha_fuera_de_gestion_activa(): void
    {
        [$materia] = $this->prepararSistema();

        $this->enviarQr($materia, '08:05', '2025-05-02')->assertUnprocessable();
        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_rechaza_si_no_hay_gestion_activa(): void
    {
        [$materia, , $gestion] = $this->prepararSistema();
        $gestion->update(['es_activa' => false]);

        $this->enviarQr($materia, '08:05')->assertUnprocessable();
        $this->assertDatabaseCount('asistencias', 0);
    }
}
