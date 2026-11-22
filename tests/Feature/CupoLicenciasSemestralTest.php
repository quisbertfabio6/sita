<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Licencia;
use App\Models\Rol;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CupoLicenciasSemestralTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-05-02 09:00:00', 'UTC'));
    }

    private function usuario(string $rol = 'estudiante', string $email = 'alumno@sita.test'): Usuario
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

    private function estudiante(): Estudiante
    {
        $usuario = $this->usuario();

        return Estudiante::create([
            'user_id' => $usuario->id,
            'codigo_qr' => 'QR-SEMESTRE-' . $usuario->id,
        ]);
    }

    private function solicitud(array $datos = []): array
    {
        return array_merge([
            'motivo' => 'Solicitud de prueba',
            'fecha_inicio' => '2026-05-03',
            'fecha_fin' => '2026-05-04',
        ], $datos);
    }

    private function crearLicencia(Estudiante $estudiante, string $estado = 'pendiente'): Licencia
    {
        return Licencia::create([
            'estudiante_id' => $estudiante->id,
            'motivo' => 'Licencia existente',
            'fecha_inicio' => '2026-05-03',
            'fecha_fin' => '2026-05-04',
            'estado' => $estado,
        ]);
    }

    public function test_cupo_requiere_autenticacion_y_rol_estudiante(): void
    {
        $ruta = '/api/estudiante/licencias/cupo';
        $this->getJson($ruta)->assertUnauthorized();

        Sanctum::actingAs($this->usuario('docente', 'docente@sita.test'));
        $this->getJson($ruta)->assertForbidden();

        $estudiante = $this->estudiante();
        Sanctum::actingAs($estudiante->usuario);
        $this->getJson($ruta)
            ->assertOk()
            ->assertJsonPath('data.maximo', 3)
            ->assertJsonPath('data.utilizadas', 0)
            ->assertJsonPath('data.disponibles', 3)
            ->assertJsonPath('data.semestre', 1)
            ->assertJsonPath('data.fecha_inicio', '2026-01-01')
            ->assertJsonPath('data.fecha_fin', '2026-06-30')
            ->assertJsonPath('data.puede_solicitar', true);
    }

    public function test_cupo_requiere_perfil_estudiante(): void
    {
        Sanctum::actingAs($this->usuario());
        $this->getJson('/api/estudiante/licencias/cupo')->assertForbidden();
    }

    public function test_acepta_tres_solicitudes_y_bloquea_la_cuarta(): void
    {
        $estudiante = $this->estudiante();
        Sanctum::actingAs($estudiante->usuario);

        foreach (range(1, 3) as $numero) {
            $this->postJson('/api/estudiante/licencias', $this->solicitud([
                'motivo' => 'Solicitud ' . $numero,
            ]))->assertCreated()
                ->assertJsonPath('cupo.utilizadas', $numero);
        }

        $this->postJson('/api/estudiante/licencias', $this->solicitud([
            'motivo' => 'Cuarta solicitud',
        ]))->assertStatus(409)
            ->assertJsonPath('codigo', 'LIMITE_LICENCIAS_SEMESTRALES')
            ->assertJsonPath('cupo.disponibles', 0)
            ->assertJsonPath('cupo.puede_solicitar', false);

        $this->assertDatabaseCount('licencias', 3);
        $this->getJson('/api/estudiante/licencias/cupo')
            ->assertOk()->assertJsonPath('data.utilizadas', 3);
    }

    public function test_pendientes_y_aprobadas_cuentan_pero_rechazadas_no(): void
    {
        $estudiante = $this->estudiante();
        $this->crearLicencia($estudiante, 'pendiente');
        $this->crearLicencia($estudiante, 'aprobada');
        $this->crearLicencia($estudiante, 'rechazada');

        Sanctum::actingAs($estudiante->usuario);
        $this->getJson('/api/estudiante/licencias/cupo')
            ->assertOk()
            ->assertJsonPath('data.utilizadas', 2)
            ->assertJsonPath('data.disponibles', 1);

        $this->postJson('/api/estudiante/licencias', $this->solicitud())
            ->assertCreated()->assertJsonPath('cupo.disponibles', 0);
    }

    public function test_rechazar_licencia_devuelve_un_cupo(): void
    {
        $estudiante = $this->estudiante();
        $licencias = [
            $this->crearLicencia($estudiante),
            $this->crearLicencia($estudiante),
            $this->crearLicencia($estudiante),
        ];
        Sanctum::actingAs($estudiante->usuario);
        $this->getJson('/api/estudiante/licencias/cupo')
            ->assertJsonPath('data.disponibles', 0);

        $licencias[0]->update(['estado' => 'rechazada']);

        $this->getJson('/api/estudiante/licencias/cupo')
            ->assertJsonPath('data.disponibles', 1);
        $this->postJson('/api/estudiante/licencias', $this->solicitud())
            ->assertCreated();
    }

    public function test_anular_pendiente_devuelve_un_cupo(): void
    {
        $estudiante = $this->estudiante();
        $licencia = $this->crearLicencia($estudiante);
        $this->crearLicencia($estudiante);
        $this->crearLicencia($estudiante);
        Sanctum::actingAs($estudiante->usuario);

        $this->deleteJson('/api/estudiante/licencias/' . $licencia->id)
            ->assertOk()->assertJsonPath('success', true);

        $this->getJson('/api/estudiante/licencias/cupo')
            ->assertJsonPath('data.utilizadas', 2)
            ->assertJsonPath('data.disponibles', 1);
    }

    public function test_el_semestre_se_reinicia_el_primero_de_julio_hora_bolivia(): void
    {
        $estudiante = $this->estudiante();
        Sanctum::actingAs($estudiante->usuario);
        $this->travelTo(Carbon::parse('2026-07-01 03:59:00', 'UTC')); // 30/06 23:59 Bolivia
        foreach (range(1, 3) as $_) {
            $this->crearLicencia($estudiante);
        }

        $this->getJson('/api/estudiante/licencias/cupo')
            ->assertJsonPath('data.semestre', 1)
            ->assertJsonPath('data.utilizadas', 3);

        $this->travelTo(Carbon::parse('2026-07-01 04:00:00', 'UTC')); // 01/07 00:00 Bolivia
        $this->getJson('/api/estudiante/licencias/cupo')
            ->assertJsonPath('data.semestre', 2)
            ->assertJsonPath('data.utilizadas', 0)
            ->assertJsonPath('data.disponibles', 3)
            ->assertJsonPath('data.fecha_inicio', '2026-07-01')
            ->assertJsonPath('data.fecha_fin', '2026-12-31');

        $this->postJson('/api/estudiante/licencias', $this->solicitud([
            'fecha_inicio' => '2026-07-05',
            'fecha_fin' => '2026-07-06',
        ]))->assertCreated();
    }

    public function test_el_cupo_se_reinicia_en_enero_del_anio_siguiente(): void
    {
        $estudiante = $this->estudiante();
        Sanctum::actingAs($estudiante->usuario);
        $this->travelTo(Carbon::parse('2026-12-15 12:00:00', 'UTC'));
        $this->crearLicencia($estudiante);

        $this->travelTo(Carbon::parse('2027-01-01 04:00:00', 'UTC'));
        $this->getJson('/api/estudiante/licencias/cupo')
            ->assertJsonPath('data.anio', 2027)
            ->assertJsonPath('data.semestre', 1)
            ->assertJsonPath('data.utilizadas', 0)
            ->assertJsonPath('data.disponibles', 3);
    }

    public function test_cuenta_fecha_de_envio_y_no_fecha_de_ausencia(): void
    {
        $estudiante = $this->estudiante();
        Sanctum::actingAs($estudiante->usuario);

        $this->postJson('/api/estudiante/licencias', $this->solicitud([
            'fecha_inicio' => '2026-08-03',
            'fecha_fin' => '2026-08-04',
        ]))->assertCreated();

        $this->getJson('/api/estudiante/licencias/cupo')
            ->assertJsonPath('data.semestre', 1)
            ->assertJsonPath('data.utilizadas', 1);

        $this->travelTo(Carbon::parse('2026-07-01 04:00:00', 'UTC'));
        $this->getJson('/api/estudiante/licencias/cupo')
            ->assertJsonPath('data.semestre', 2)
            ->assertJsonPath('data.utilizadas', 0);
    }

    public function test_bloqueo_se_aplica_a_app_anterior_y_no_guarda_archivo(): void
    {
        Storage::fake('public');
        $estudiante = $this->estudiante();
        foreach (range(1, 3) as $_) {
            $this->crearLicencia($estudiante);
        }
        Sanctum::actingAs($estudiante->usuario);

        $this->postJson('/api/estudiante/licencias', $this->solicitud([
            'archivo' => UploadedFile::fake()->image('evidencia.jpg'),
        ]))->assertStatus(409);

        $this->assertDatabaseCount('licencias', 3);
        $this->assertSame([], Storage::disk('public')->allFiles('licencias'));
    }

    public function test_cupo_es_individual_por_estudiante(): void
    {
        $estudianteA = $this->estudiante();
        foreach (range(1, 3) as $_) {
            $this->crearLicencia($estudianteA);
        }

        $usuarioB = $this->usuario('estudiante', 'otro@sita.test');
        $estudianteB = Estudiante::create([
            'user_id' => $usuarioB->id,
            'codigo_qr' => 'QR-OTRO',
        ]);
        Sanctum::actingAs($estudianteB->usuario);

        $this->getJson('/api/estudiante/licencias/cupo')
            ->assertJsonPath('data.utilizadas', 0)
            ->assertJsonPath('data.disponibles', 3);
    }
}
