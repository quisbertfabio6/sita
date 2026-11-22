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
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class SeguridadRolesTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol, string $email, bool $activo = true): Usuario
    {
        $modeloRol = Rol::firstOrCreate(['nombre' => $rol]);

        return Usuario::create([
            'nombre_completo' => 'Usuario de prueba',
            'email' => $email,
            'password' => Hash::make('clave-prueba-123'),
            'rol_id' => $modeloRol->id,
            'activo' => $activo,
        ]);
    }

    private function docente(string $email = 'docente@sita.test'): array
    {
        $usuario = $this->usuario('docente', $email);
        $docente = Docente::create(['user_id' => $usuario->id]);

        return [$usuario, $docente];
    }

    private function materia(): Materia
    {
        $curso = Curso::create(['nombre' => 'Primero A', 'gestion' => '2026']);

        return Materia::create(['nombre' => 'Matematicas', 'curso_id' => $curso->id]);
    }

    private function prepararManual(): array
    {
        $this->travelTo(Carbon::parse('2026-05-02 09:00:00'));
        $gestion = Gestion::create(['anio' => '2026', 'es_activa' => true]);
        [$usuarioDocente, $docente] = $this->docente();
        $materia = $this->materia();
        $docente->materias()->attach($materia->id);

        $usuarioEstudiante = $this->usuario('estudiante', 'estudiante@sita.test');
        $estudiante = Estudiante::create([
            'user_id' => $usuarioEstudiante->id,
            'codigo_qr' => 'QR-SEGURIDAD',
            'matricula' => 'MAT-SEGURIDAD',
        ]);

        Inscripcion::create([
            'estudiante_id' => $estudiante->id,
            'curso_id' => $materia->curso_id,
            'gestion_id' => $gestion->id,
            'estado' => 'Activo',
        ]);

        Sanctum::actingAs($usuarioDocente);

        return [$materia, $estudiante, $docente];
    }

    public function test_estudiante_no_puede_abrir_dashboard_admin(): void
    {
        $this->actingAs($this->usuario('estudiante', 'estudiante@sita.test'))
            ->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_estudiante_no_puede_iniciar_gestion_academica(): void
    {
        $this->actingAs($this->usuario('estudiante', 'estudiante@sita.test'))
            ->post(route('gestiones.iniciar'), ['anio' => '2027'])->assertForbidden();

        $this->assertDatabaseCount('gestiones', 0);
    }

    public function test_docente_no_puede_importar_estudiantes(): void
    {
        $this->actingAs($this->docente()[0])
            ->post(route('estudiantes.importar'))->assertForbidden();
    }

    public function test_jefe_no_puede_administrar_usuarios(): void
    {
        $this->actingAs($this->usuario('jefe_carrera', 'jefe@sita.test'))
            ->get(route('usuarios.index'))->assertForbidden();
    }

    public function test_estudiante_no_puede_ver_pdf_oficial_de_materia(): void
    {
        $this->actingAs($this->usuario('estudiante', 'estudiante@sita.test'))
            ->get(route('reportes.lista', ['id' => 1]))->assertForbidden();
    }

    public function test_administrador_puede_abrir_su_dashboard(): void
    {
        $this->actingAs($this->usuario('administrador', 'admin@sita.test'))
            ->get(route('admin.dashboard'))->assertOk();
    }

    public function test_docente_puede_abrir_su_dashboard(): void
    {
        $this->actingAs($this->docente()[0])
            ->get(route('docente.dashboard'))->assertOk();
    }

    public function test_jefe_puede_abrir_su_formulario_de_reportes(): void
    {
        $this->actingAs($this->usuario('jefe_carrera', 'jefe@sita.test'))
            ->get(route('jefe.reportes.index'))->assertOk();
    }

    public function test_estudiante_no_puede_consultar_api_docente(): void
    {
        Sanctum::actingAs($this->usuario('estudiante', 'estudiante@sita.test'));
        $this->getJson('/api/docente/materias')->assertForbidden();
    }

    public function test_docente_no_puede_consultar_api_estudiante(): void
    {
        Sanctum::actingAs($this->docente()[0]);
        $this->getJson('/api/estudiante/materias')->assertForbidden();
    }

    public function test_docente_autorizado_puede_consultar_sus_materias(): void
    {
        Sanctum::actingAs($this->docente()[0]);
        $this->getJson('/api/docente/materias')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_api_privada_requiere_autenticacion(): void
    {
        $this->getJson('/api/docente/materias')->assertUnauthorized();
    }

    public function test_login_web_rechaza_cuenta_desactivada(): void
    {
        $this->usuario('administrador', 'inactivo@sita.test', false);

        $this->post('/login', [
            'email' => 'inactivo@sita.test',
            'password' => 'clave-prueba-123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_api_rechaza_cuenta_desactivada(): void
    {
        $this->usuario('docente', 'inactivo@sita.test', false);

        $this->postJson('/api/login', [
            'email' => 'inactivo@sita.test',
            'password' => 'clave-prueba-123',
            'device_name' => 'prueba',
        ])->assertUnauthorized();
    }

    public function test_token_preexistente_no_funciona_tras_desactivar_cuenta(): void
    {
        $usuario = $this->usuario('docente', 'inactivo@sita.test');
        Sanctum::actingAs($usuario);
        $usuario->update(['activo' => false]);

        $this->getJson('/api/user')->assertForbidden();
    }

    public function test_sesion_web_inactiva_se_cierra(): void
    {
        $usuario = $this->usuario('administrador', 'inactivo@sita.test');
        $this->actingAs($usuario);
        $usuario->update(['activo' => false]);

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_docente_no_puede_descargar_pdf_de_materia_ajena(): void
    {
        [$usuario] = $this->docente();
        $materia = $this->materia();
        Gestion::create(['anio' => '2026', 'es_activa' => true]);

        $this->actingAs($usuario)
            ->get(route('reportes.lista', ['id' => $materia->id]))
            ->assertForbidden();
    }

    public function test_docente_puede_descargar_pdf_de_materia_asignada(): void
    {
        [$usuario, $docente] = $this->docente();
        $materia = $this->materia();
        $docente->materias()->attach($materia->id);
        Gestion::create(['anio' => '2026', 'es_activa' => true]);

        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('stream')->once()
            ->andReturn(response('PDF de prueba', 200, ['Content-Type' => 'application/pdf']));
        Pdf::shouldReceive('loadView')->once()->andReturn($pdf);

        $this->actingAs($usuario)
            ->get(route('reportes.lista', ['id' => $materia->id]))
            ->assertOk();
    }

    public function test_registro_manual_rechaza_materia_no_asignada(): void
    {
        [$materia, $estudiante] = $this->prepararManual();
        $otraMateria = Materia::create([
            'nombre' => 'Historia',
            'curso_id' => $materia->curso_id,
        ]);

        $this->postJson('/api/docente/registrar-manual', [
            'estudiante_id' => $estudiante->id,
            'materia_id' => $otraMateria->id,
            'estado' => 'presente',
        ])->assertForbidden();

        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_registro_manual_rechaza_estudiante_de_otro_curso(): void
    {
        [$materia, $estudiante] = $this->prepararManual();
        $otroCurso = Curso::create(['nombre' => 'Segundo A', 'gestion' => '2026']);
        Inscripcion::where('estudiante_id', $estudiante->id)->update(['curso_id' => $otroCurso->id]);

        $this->postJson('/api/docente/registrar-manual', [
            'estudiante_id' => $estudiante->id,
            'materia_id' => $materia->id,
            'estado' => 'presente',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_registro_manual_guarda_asistencia_autorizada(): void
    {
        [$materia, $estudiante] = $this->prepararManual();

        $this->postJson('/api/docente/registrar-manual', [
            'estudiante_id' => $estudiante->id,
            'materia_id' => $materia->id,
            'estado' => 'atraso',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('asistencias', [
            'estudiante_id' => $estudiante->id,
            'materia_id' => $materia->id,
            'fecha' => '2026-05-02',
            'estado' => 'atraso',
        ]);
    }

    public function test_registro_manual_respeta_licencia_aprobada(): void
    {
        [$materia, $estudiante] = $this->prepararManual();
        Licencia::create([
            'estudiante_id' => $estudiante->id,
            'motivo' => 'Licencia de prueba',
            'fecha_inicio' => '2026-05-02',
            'fecha_fin' => '2026-05-02',
            'estado' => 'aprobada',
        ]);

        $this->postJson('/api/docente/registrar-manual', [
            'estudiante_id' => $estudiante->id,
            'materia_id' => $materia->id,
            'estado' => 'falta',
        ])->assertStatus(409);

        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_registro_manual_rechaza_estado_invalido(): void
    {
        [$materia, $estudiante] = $this->prepararManual();

        $this->postJson('/api/docente/registrar-manual', [
            'estudiante_id' => $estudiante->id,
            'materia_id' => $materia->id,
            'estado' => 'aprobada',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('asistencias', 0);
    }
}
