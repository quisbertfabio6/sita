<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\Inscripcion;
use App\Models\JefeCarrera;
use App\Models\Licencia;
use App\Models\Materia;
use App\Models\Rol;
use App\Models\Usuario;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class SeguridadLicenciasJefaturaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-05-02 09:00:00'));
    }

    private function usuario(string $rol, string $email): Usuario
    {
        $modeloRol = Rol::firstOrCreate(['nombre' => $rol]);

        return Usuario::create([
            'nombre_completo' => 'Usuario de prueba',
            'email' => $email,
            'password' => Hash::make('clave-prueba-123'),
            'rol_id' => $modeloRol->id,
            'activo' => true,
        ]);
    }

    private function jefe(string $carreras, string $email = 'jefe@sita.test'): array
    {
        $usuario = $this->usuario('jefe_carrera', $email);
        $jefe = JefeCarrera::create([
            'user_id' => $usuario->id,
            'carrera_asignada' => $carreras,
        ]);

        return [$usuario, $jefe];
    }

    private function estudiante(string $nombreCurso, string $anio = '2026'): array
    {
        $indice = Estudiante::count() + 1;
        $usuario = $this->usuario('estudiante', "alumno{$indice}@sita.test");
        $estudiante = Estudiante::create([
            'user_id' => $usuario->id,
            'codigo_qr' => "QR-JEFE-{$indice}",
        ]);

        $gestion = Gestion::firstOrCreate(
            ['anio' => $anio],
            ['es_activa' => $anio === '2026']
        );
        $curso = Curso::create([
            'nombre' => $nombreCurso,
            'gestion' => $anio,
        ]);

        Inscripcion::create([
            'estudiante_id' => $estudiante->id,
            'curso_id' => $curso->id,
            'gestion_id' => $gestion->id,
            'estado' => 'Activo',
        ]);

        return [$estudiante, $curso];
    }

    private function licencia(Estudiante $estudiante, string $motivo, string $estado = 'pendiente', string $inicio = '2026-05-01'): Licencia
    {
        return Licencia::create([
            'estudiante_id' => $estudiante->id,
            'motivo' => $motivo,
            'fecha_inicio' => $inicio,
            'fecha_fin' => $inicio,
            'estado' => $estado,
        ]);
    }

    public function test_listado_muestra_solo_licencias_de_carrera_asignada(): void
    {
        [$usuario] = $this->jefe('Sistemas Informáticos');
        [$sistemas] = $this->estudiante('Primero Sistemas');
        [$automotriz] = $this->estudiante('Primero Automotriz');
        $this->licencia($sistemas, 'Solicitud sistemas');
        $this->licencia($automotriz, 'Solicitud automotriz');

        $this->actingAs($usuario)->get(route('jefe.licencias'))
            ->assertOk()
            ->assertSee('Solicitud sistemas')
            ->assertDontSee('Solicitud automotriz')
            ->assertSee('Primero Sistemas');
    }

    public function test_jefe_no_puede_aprobar_licencia_de_otra_carrera_por_id(): void
    {
        [$usuario] = $this->jefe('Sistemas Informáticos');
        [$automotriz, $curso] = $this->estudiante('Primero Automotriz');
        $licencia = $this->licencia($automotriz, 'Solicitud ajena');
        $materia = Materia::create(['nombre' => 'Mecanica', 'curso_id' => $curso->id]);
        $asistencia = Asistencia::create([
            'estudiante_id' => $automotriz->id,
            'materia_id' => $materia->id,
            'fecha' => '2026-05-01',
            'hora' => '08:00:00',
            'estado' => 'falta',
        ]);

        $this->actingAs($usuario)
            ->put(route('jefe.licencias.update', $licencia->id), ['estado' => 'aprobada'])
            ->assertNotFound();

        $this->assertDatabaseHas('licencias', [
            'id' => $licencia->id,
            'estado' => 'pendiente',
            'jefe_carrera_id' => null,
        ]);
        $this->assertDatabaseHas('asistencias', ['id' => $asistencia->id, 'estado' => 'falta']);
    }

    public function test_jefe_autorizado_aprueba_y_justifica_falta(): void
    {
        [$usuario, $jefe] = $this->jefe('Sistemas Informáticos');
        [$estudiante, $curso] = $this->estudiante('Primero Sistemas');
        $licencia = $this->licencia($estudiante, 'Consulta medica');
        $materia = Materia::create(['nombre' => 'Matematicas', 'curso_id' => $curso->id]);
        $asistencia = Asistencia::create([
            'estudiante_id' => $estudiante->id,
            'materia_id' => $materia->id,
            'fecha' => '2026-05-01',
            'hora' => '08:00:00',
            'estado' => 'falta',
        ]);

        $this->actingAs($usuario)
            ->put(route('jefe.licencias.update', $licencia->id), [
                'estado' => 'aprobada',
                'comentario_admin' => 'Justificado',
            ])->assertRedirect();

        $this->assertDatabaseHas('licencias', [
            'id' => $licencia->id,
            'estado' => 'aprobada',
            'jefe_carrera_id' => $jefe->id,
            'comentario_admin' => 'Justificado',
        ]);
        $this->assertDatabaseHas('asistencias', [
            'id' => $asistencia->id,
            'estado' => 'licencia',
        ]);
    }

    public function test_jefe_autorizado_rechaza_sin_cambiar_asistencia(): void
    {
        [$usuario, $jefe] = $this->jefe('Sistemas Informáticos');
        [$estudiante, $curso] = $this->estudiante('Primero Sistemas');
        $licencia = $this->licencia($estudiante, 'Solicitud a rechazar');
        $materia = Materia::create(['nombre' => 'Matematicas', 'curso_id' => $curso->id]);
        $asistencia = Asistencia::create([
            'estudiante_id' => $estudiante->id,
            'materia_id' => $materia->id,
            'fecha' => '2026-05-01',
            'hora' => '08:00:00',
            'estado' => 'atraso',
        ]);

        $this->actingAs($usuario)
            ->put(route('jefe.licencias.update', $licencia->id), ['estado' => 'rechazada'])
            ->assertRedirect();

        $this->assertDatabaseHas('licencias', [
            'id' => $licencia->id,
            'estado' => 'rechazada',
            'jefe_carrera_id' => $jefe->id,
        ]);
        $this->assertDatabaseHas('asistencias', ['id' => $asistencia->id, 'estado' => 'atraso']);
    }

    public function test_no_permite_reprocesar_una_licencia_ya_aprobada(): void
    {
        [$usuario] = $this->jefe('Sistemas Informáticos');
        [$estudiante] = $this->estudiante('Primero Sistemas');
        $licencia = $this->licencia($estudiante, 'Ya procesada', 'aprobada');

        $this->actingAs($usuario)
            ->put(route('jefe.licencias.update', $licencia->id), ['estado' => 'rechazada'])
            ->assertStatus(409);

        $this->assertDatabaseHas('licencias', ['id' => $licencia->id, 'estado' => 'aprobada']);
    }

    public function test_jefe_sin_perfil_no_puede_procesar_licencia(): void
    {
        $usuario = $this->usuario('jefe_carrera', 'sin-perfil@sita.test');
        [$estudiante] = $this->estudiante('Primero Sistemas');
        $licencia = $this->licencia($estudiante, 'Sin jefe');

        $this->actingAs($usuario)->get(route('jefe.licencias'))->assertOk();
        $this->actingAs($usuario)
            ->put(route('jefe.licencias.update', $licencia->id), ['estado' => 'aprobada'])
            ->assertForbidden();

        $this->assertDatabaseHas('licencias', ['id' => $licencia->id, 'estado' => 'pendiente']);
    }

    public function test_jefe_sin_carreras_asignadas_no_puede_procesar(): void
    {
        [$usuario] = $this->jefe('Sin asignar');
        [$estudiante] = $this->estudiante('Primero Sistemas');
        $licencia = $this->licencia($estudiante, 'Sin carrera');

        $this->actingAs($usuario)->get(route('jefe.licencias'))
            ->assertOk()->assertDontSee('Sin carrera');
        $this->actingAs($usuario)
            ->put(route('jefe.licencias.update', $licencia->id), ['estado' => 'aprobada'])
            ->assertNotFound();
    }

    public function test_inscripcion_de_otro_anio_no_autoriza_licencia_actual(): void
    {
        [$jefeSistemas] = $this->jefe('Sistemas Informáticos', 'sistemas@sita.test');
        [$jefeAutomotriz] = $this->jefe('Mecánica Automotriz', 'auto@sita.test');
        [$estudiante] = $this->estudiante('Primero Sistemas', '2025');

        $gestionActual = Gestion::create(['anio' => '2026', 'es_activa' => true]);
        $cursoActual = Curso::create(['nombre' => 'Segundo Automotriz', 'gestion' => '2026']);
        Inscripcion::create([
            'estudiante_id' => $estudiante->id,
            'curso_id' => $cursoActual->id,
            'gestion_id' => $gestionActual->id,
            'estado' => 'Activo',
        ]);
        $licencia = $this->licencia($estudiante, 'Solicitud gestion actual');

        $this->actingAs($jefeSistemas)
            ->put(route('jefe.licencias.update', $licencia->id), ['estado' => 'aprobada'])
            ->assertNotFound();

        $this->actingAs($jefeAutomotriz)
            ->put(route('jefe.licencias.update', $licencia->id), ['estado' => 'aprobada'])
            ->assertRedirect();

        $this->assertDatabaseHas('licencias', ['id' => $licencia->id, 'estado' => 'aprobada']);
    }

    public function test_licencia_historica_corresponde_a_carrera_del_anio_de_solicitud(): void
    {
        [$jefeSistemas] = $this->jefe('Sistemas Informáticos', 'sistemas@sita.test');
        [$jefeAutomotriz] = $this->jefe('Mecánica Automotriz', 'auto@sita.test');
        [$estudiante] = $this->estudiante('Primero Sistemas', '2025');
        $gestionActual = Gestion::create(['anio' => '2026', 'es_activa' => true]);
        $cursoActual = Curso::create(['nombre' => 'Segundo Automotriz', 'gestion' => '2026']);
        Inscripcion::create([
            'estudiante_id' => $estudiante->id,
            'curso_id' => $cursoActual->id,
            'gestion_id' => $gestionActual->id,
            'estado' => 'Activo',
        ]);

        $this->travelTo(Carbon::parse('2025-05-02 09:00:00'));
        $licencia = $this->licencia($estudiante, 'Licencia historica', 'pendiente', '2025-05-01');
        $this->travelTo(Carbon::parse('2026-05-02 09:00:00'));

        $this->actingAs($jefeAutomotriz)
            ->put(route('jefe.licencias.update', $licencia->id), ['estado' => 'aprobada'])
            ->assertNotFound();
        $this->actingAs($jefeSistemas)
            ->get(route('jefe.licencias'))->assertOk()->assertSee('Licencia historica')
            ->assertSee('Primero Sistemas');
    }

    public function test_pdf_de_jefatura_no_incluye_licencias_ajenas(): void
    {
        [$usuario] = $this->jefe('Sistemas Informáticos');
        [$sistemas] = $this->estudiante('Primero Sistemas');
        [$automotriz] = $this->estudiante('Primero Automotriz');
        $propia = $this->licencia($sistemas, 'Licencia propia');
        $ajena = $this->licencia($automotriz, 'Licencia ajena');

        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('stream')->once()
            ->andReturn(response('PDF de prueba', 200, ['Content-Type' => 'application/pdf']));

        Pdf::shouldReceive('loadView')->once()
            ->with('reportes.jefe_licencias', Mockery::on(function (array $datos) use ($propia, $ajena) {
                $ids = $datos['licencias']->pluck('id')->all();
                return in_array($propia->id, $ids, true)
                    && !in_array($ajena->id, $ids, true)
                    && count($ids) === 1;
            }))
            ->andReturn($pdf);

        $this->actingAs($usuario)
            ->post(route('jefe.reportes.generar'), [
                'fecha_inicio' => '2026-05-01',
                'fecha_fin' => '2026-05-02',
                'estado' => 'todas',
            ])->assertOk();
    }

    public function test_dashboard_cuenta_solo_licencias_de_carrera_asignada(): void
    {
        [$usuario] = $this->jefe('Sistemas Informáticos');
        [$sistemas] = $this->estudiante('Primero Sistemas');
        [$automotriz] = $this->estudiante('Primero Automotriz');
        $this->licencia($sistemas, 'Propia');
        $this->licencia($automotriz, 'Ajena');

        $this->actingAs($usuario)->get(route('jefe.dashboard'))
            ->assertOk()
            ->assertViewHas('kpis', function ($kpis) {
                return $kpis['licencias_pendientes'] === 1
                    && $kpis['total_cursos'] === 1;
            });
    }

    public function test_comodin_en_nombre_de_carrera_no_permita_ver_todas(): void
    {
        [$usuario] = $this->jefe('%');
        [$estudiante] = $this->estudiante('Primero Sistemas');
        $licencia = $this->licencia($estudiante, 'No autorizada');

        $this->actingAs($usuario)->get(route('jefe.licencias'))
            ->assertOk()->assertDontSee('No autorizada');
        $this->actingAs($usuario)
            ->put(route('jefe.licencias.update', $licencia->id), ['estado' => 'aprobada'])
            ->assertNotFound();
    }

    public function test_licencia_con_fecha_futura_se_autoriza_segun_gestion_de_creacion(): void
    {
        [$usuario] = $this->jefe('Sistemas Informáticos');
        [$estudiante] = $this->estudiante('Primero Sistemas');
        $licencia = $this->licencia($estudiante, 'Fecha futura', 'pendiente', '2027-01-05');

        $this->actingAs($usuario)
            ->put(route('jefe.licencias.update', $licencia->id), ['estado' => 'aprobada'])
            ->assertRedirect();

        $this->assertDatabaseHas('licencias', ['id' => $licencia->id, 'estado' => 'aprobada']);
    }
}
