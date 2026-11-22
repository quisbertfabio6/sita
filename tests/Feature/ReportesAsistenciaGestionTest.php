<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\Inscripcion;
use App\Models\Licencia;
use App\Models\Materia;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\EstadisticasAsistenciaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class ReportesAsistenciaGestionTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol, string $email): Usuario
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

    private function materia(): array
    {
        $curso = Curso::create(['nombre' => 'Primero A', 'gestion' => '2026']);
        $materia = Materia::create(['nombre' => 'Matematicas', 'curso_id' => $curso->id]);

        return [$curso, $materia];
    }

    private function estudiante(Curso $curso, Gestion $gestion, string $codigo): Estudiante
    {
        $usuario = $this->usuario('estudiante', 'alumno-' . $codigo . '@sita.test');
        $estudiante = Estudiante::create([
            'user_id' => $usuario->id,
            'codigo_qr' => 'QR-' . $codigo,
            'matricula' => 'MAT-' . $codigo,
        ]);

        Inscripcion::create([
            'estudiante_id' => $estudiante->id,
            'curso_id' => $curso->id,
            'gestion_id' => $gestion->id,
            'estado' => 'Activo',
        ]);

        return $estudiante;
    }

    private function asistencia(Estudiante $estudiante, Materia $materia, string $fecha, string $estado): void
    {
        Asistencia::create([
            'estudiante_id' => $estudiante->id,
            'materia_id' => $materia->id,
            'fecha' => $fecha,
            'hora' => '08:00:00',
            'estado' => $estado,
        ]);
    }

    private function pdfSimulado(string $vista, callable $verificarDatos): void
    {
        $pdf = Mockery::mock();
        $pdf->shouldReceive('stream')
            ->once()
            ->andReturn(response('PDF de prueba', 200, ['Content-Type' => 'application/pdf']));

        Pdf::shouldReceive('loadView')
            ->once()
            ->with($vista, Mockery::on($verificarDatos))
            ->andReturn($pdf);
    }

    public function test_total_clases_cuenta_fechas_distintas_solo_del_anio_y_periodo(): void
    {
        [$curso, $materia] = $this->materia();
        $gestion = Gestion::create(['anio' => '2026', 'es_activa' => true]);
        $alumno1 = $this->estudiante($curso, $gestion, 'A');
        $alumno2 = $this->estudiante($curso, $gestion, 'B');

        $this->asistencia($alumno1, $materia, '2025-05-01', 'presente');
        $this->asistencia($alumno1, $materia, '2026-05-01', 'presente');
        $this->asistencia($alumno2, $materia, '2026-05-01', 'atraso');
        $this->asistencia($alumno1, $materia, '2026-05-02', 'falta');

        $servicio = app(EstadisticasAsistenciaService::class);
        $this->assertSame(2, $servicio->totalClases($materia->id, '2026'));
        $this->assertSame(1, $servicio->totalClases($materia->id, '2026', '2026-05-01', '2026-05-01'));
        $this->assertSame(1, $servicio->totalClases($materia->id, '2025'));
    }

    public function test_porcentaje_presente_y_atraso_equivale_a_75(): void
    {
        $servicio = app(EstadisticasAsistenciaService::class);
        $asistencias = collect([
            new Asistencia(['fecha' => '2026-05-01', 'estado' => 'presente']),
            new Asistencia(['fecha' => '2026-05-02', 'estado' => 'atraso']),
        ]);

        $resumen = $servicio->calcular($asistencias, collect(), 2);
        $this->assertSame(75, $resumen['porcentaje']);
        $this->assertSame(1.5, $resumen['puntos']);
        $this->assertTrue($resumen['riesgo']);
        $this->assertSame(1, $resumen['conteos']['presente']);
        $this->assertSame(1, $resumen['conteos']['atraso']);
    }

    public function test_licencia_solo_suma_puntos_si_esta_aprobada_y_cubre_fecha(): void
    {
        $servicio = app(EstadisticasAsistenciaService::class);
        $asistencias = collect([
            new Asistencia(['fecha' => '2026-05-01', 'estado' => 'licencia']),
            new Asistencia(['fecha' => '2026-05-02', 'estado' => 'licencia']),
        ]);
        $licencias = collect([
            new Licencia([
                'fecha_inicio' => '2026-05-01',
                'fecha_fin' => '2026-05-01',
                'estado' => 'aprobada',
            ]),
            new Licencia([
                'fecha_inicio' => '2026-05-02',
                'fecha_fin' => '2026-05-02',
                'estado' => 'pendiente',
            ]),
        ]);

        $resumen = $servicio->calcular($asistencias, $licencias, 2);
        $this->assertSame(50, $resumen['porcentaje']);
        $this->assertSame(1.0, $resumen['puntos']);
        $this->assertSame(2, $resumen['conteos']['licencia']);
    }

    public function test_sin_clases_no_marca_al_estudiante_en_riesgo(): void
    {
        $resumen = app(EstadisticasAsistenciaService::class)->calcular(collect(), collect(), 0);
        $this->assertSame(0, $resumen['porcentaje']);
        $this->assertFalse($resumen['riesgo']);
        $this->assertTrue($resumen['sin_clases']);
    }

    public function test_registros_duplicados_en_un_dia_no_inflan_porcentaje(): void
    {
        $resumen = app(EstadisticasAsistenciaService::class)->calcular(collect([
            new Asistencia(['fecha' => '2026-05-01', 'estado' => 'presente']),
            new Asistencia(['fecha' => '2026-05-01', 'estado' => 'presente']),
        ]), collect(), 2);

        $this->assertSame(50, $resumen['porcentaje']);
        $this->assertSame(1, $resumen['conteos']['presente']);
    }

    public function test_reporte_por_rango_no_mezcla_anios_y_separa_estudiantes(): void
    {
        $this->actingAs($this->usuario('administrador', 'admin@sita.test'));
        [$curso, $materia] = $this->materia();
        $anterior = Gestion::create(['anio' => '2025', 'es_activa' => false]);
        $actual = Gestion::create(['anio' => '2026', 'es_activa' => true]);
        $alumnoActual = $this->estudiante($curso, $actual, 'ACTUAL');
        $alumnoAnterior = $this->estudiante($curso, $anterior, 'ANTERIOR');

        $this->asistencia($alumnoActual, $materia, '2025-05-01', 'presente');
        $this->asistencia($alumnoActual, $materia, '2026-05-01', 'presente');
        $this->asistencia($alumnoActual, $materia, '2026-05-02', 'atraso');
        $this->asistencia($alumnoAnterior, $materia, '2025-05-01', 'presente');

        $this->pdfSimulado('reportes.reporte_fechas', function ($datos) use ($actual) {
            return $datos['gestion']->id === $actual->id
                && $datos['totalClases'] === 2
                && $datos['datos']->count() === 1
                && $datos['datos']->first()['porcentaje'] === 75
                && $datos['datos']->first()['conteos']['atraso'] === 1;
        });

        $this->post(route('reportes.generar'), [
            'materia_id' => $materia->id,
            'gestion_id' => $actual->id,
            'fecha_inicio' => '2026-05-01',
            'fecha_fin' => '2026-05-02',
        ])->assertOk();
    }

    public function test_reporte_oficial_respeta_gestion_historica(): void
    {
        $this->actingAs($this->usuario('administrador', 'admin@sita.test'));
        [$curso, $materia] = $this->materia();
        $anterior = Gestion::create(['anio' => '2025', 'es_activa' => false]);
        Gestion::create(['anio' => '2026', 'es_activa' => true]);
        $alumno = $this->estudiante($curso, $anterior, 'HISTORICO');
        $this->asistencia($alumno, $materia, '2025-06-01', 'presente');
        $this->asistencia($alumno, $materia, '2026-06-01', 'falta');

        $this->pdfSimulado('reportes.lista_asistencia', function ($datos) use ($anterior) {
            return $datos['gestion']->id === $anterior->id
                && $datos['totalClases'] === 1
                && $datos['datos']->count() === 1
                && $datos['datos']->first()['porcentaje'] === 100;
        });

        $this->get(route('reportes.lista', ['id' => $materia->id, 'gestion_id' => $anterior->id]))
            ->assertOk();
    }

    public function test_historial_personal_solo_incluye_anio_solicitado(): void
    {
        [$curso, $materia] = $this->materia();
        $anterior = Gestion::create(['anio' => '2025', 'es_activa' => false]);
        Gestion::create(['anio' => '2026', 'es_activa' => true]);
        $alumno = $this->estudiante($curso, $anterior, 'PERSONAL');
        $this->actingAs($alumno->usuario);

        $this->asistencia($alumno, $materia, '2025-06-01', 'presente');
        $this->asistencia($alumno, $materia, '2026-06-01', 'falta');

        $this->pdfSimulado('reportes.historial_personal', function ($datos) use ($anterior) {
            return $datos['gestion']->id === $anterior->id
                && $datos['datos'][0]['total_clases'] === 1
                && $datos['datos'][0]['porcentaje'] === 100
                && $datos['datos'][0]['detalle']->count() === 1
                && $datos['datos'][0]['detalle']->first()->fecha === '2025-06-01';
        });

        $this->get(route('reportes.personal', ['gestion_id' => $anterior->id]))
            ->assertOk();
    }

    public function test_reporte_rechaza_rango_fuera_de_gestion(): void
    {
        $this->actingAs($this->usuario('administrador', 'admin@sita.test'));
        [, $materia] = $this->materia();
        $actual = Gestion::create(['anio' => '2026', 'es_activa' => true]);

        $this->post(route('reportes.generar'), [
            'materia_id' => $materia->id,
            'gestion_id' => $actual->id,
            'fecha_inicio' => '2025-12-30',
            'fecha_fin' => '2026-01-02',
        ])->assertSessionHasErrors('fecha_inicio');
    }

    public function test_reporte_sin_gestion_activa_pide_seleccionar_una(): void
    {
        $this->actingAs($this->usuario('administrador', 'admin@sita.test'));
        [, $materia] = $this->materia();

        $this->post(route('reportes.generar'), [
            'materia_id' => $materia->id,
            'fecha_inicio' => '2026-05-01',
            'fecha_fin' => '2026-05-02',
        ])->assertSessionHasErrors('gestion_id');
    }
}
