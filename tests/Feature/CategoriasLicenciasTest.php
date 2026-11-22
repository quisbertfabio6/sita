<?php

namespace Tests\Feature;

use App\Models\CategoriaLicencia;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\Inscripcion;
use App\Models\JefeCarrera;
use App\Models\Licencia;
use App\Models\Rol;
use App\Models\SubcategoriaLicencia;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoriasLicenciasTest extends TestCase
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

    private function estudiante(string $email): Estudiante
    {
        $usuario = $this->usuario('estudiante', $email);

        return Estudiante::create([
            'user_id' => $usuario->id,
            'codigo_qr' => 'QR-' . $usuario->id,
        ]);
    }

    private function datosLicencia(array $extra = []): array
    {
        return array_merge([
            'fecha_inicio' => '2026-05-03',
            'fecha_fin' => '2026-05-04',
        ], $extra);
    }

    public function test_catalogo_inicial_tiene_cuatro_categorias(): void
    {
        $this->assertDatabaseCount('categorias_licencia', 4);
        $this->assertDatabaseHas('categorias_licencia', ['codigo' => 'salud']);
        $this->assertDatabaseHas('categorias_licencia', ['codigo' => 'familiar']);
        $this->assertDatabaseHas('categorias_licencia', ['codigo' => 'academico']);
        $this->assertDatabaseHas('categorias_licencia', ['codigo' => 'otro']);
        $this->assertGreaterThan(4, SubcategoriaLicencia::count());
    }

    public function test_catalogo_requiere_estudiante_autenticado_y_oculta_opciones_inactivas(): void
    {
        $ruta = '/api/estudiante/categorias-licencia';
        $this->getJson($ruta)->assertUnauthorized();

        Sanctum::actingAs($this->usuario('docente', 'docente@sita.test'));
        $this->getJson($ruta)->assertForbidden();

        $estudiante = $this->estudiante('alumno@sita.test');
        Sanctum::actingAs($estudiante->usuario);

        SubcategoriaLicencia::where('codigo', 'resfrio')->update(['activo' => false]);
        $this->getJson($ruta)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.codigo', 'salud')
            ->assertJsonMissing(['codigo' => 'resfrio']);
    }

    public function test_estudiante_guarda_licencia_categorizada_sin_detalle_escrito(): void
    {
        $estudiante = $this->estudiante('alumno@sita.test');
        Sanctum::actingAs($estudiante->usuario);
        $subcategoria = SubcategoriaLicencia::where('codigo', 'resfrio')->firstOrFail();

        $this->postJson('/api/estudiante/licencias', $this->datosLicencia([
            'categoria_id' => $subcategoria->categoria_id,
            'subcategoria_id' => $subcategoria->id,
        ]))->assertCreated()->assertJsonPath('success', true);

        $this->assertDatabaseHas('licencias', [
            'estudiante_id' => $estudiante->id,
            'categoria_id' => $subcategoria->categoria_id,
            'subcategoria_id' => $subcategoria->id,
            'motivo' => 'Resfrío',
            'estado' => 'pendiente',
        ]);

        $this->getJson('/api/estudiante/mis-licencias')
            ->assertOk()
            ->assertJsonPath('data.0.categoria.codigo', 'salud')
            ->assertJsonPath('data.0.subcategoria.codigo', 'resfrio');
    }

    public function test_estudiante_puede_agregar_detalle_y_se_conserva(): void
    {
        $estudiante = $this->estudiante('alumno@sita.test');
        Sanctum::actingAs($estudiante->usuario);
        $subcategoria = SubcategoriaLicencia::where('codigo', 'dentista')->firstOrFail();

        $this->postJson('/api/estudiante/licencias', $this->datosLicencia([
            'categoria_id' => $subcategoria->categoria_id,
            'subcategoria_id' => $subcategoria->id,
            'motivo' => 'Consulta odontológica programada',
        ]))->assertCreated();

        $this->assertDatabaseHas('licencias', [
            'estudiante_id' => $estudiante->id,
            'motivo' => 'Consulta odontológica programada',
            'subcategoria_id' => $subcategoria->id,
        ]);
    }

    public function test_no_acepta_subcategoria_de_otra_categoria_ni_inactiva(): void
    {
        $estudiante = $this->estudiante('alumno@sita.test');
        Sanctum::actingAs($estudiante->usuario);
        $salud = CategoriaLicencia::where('codigo', 'salud')->firstOrFail();
        $familiar = SubcategoriaLicencia::where('codigo', 'emergencia_familiar')->firstOrFail();

        $this->postJson('/api/estudiante/licencias', $this->datosLicencia([
            'categoria_id' => $salud->id,
            'subcategoria_id' => $familiar->id,
        ]))->assertUnprocessable()->assertJsonValidationErrors('subcategoria_id');

        $familiar->update(['activo' => false]);
        $this->postJson('/api/estudiante/licencias', $this->datosLicencia([
            'categoria_id' => $familiar->categoria_id,
            'subcategoria_id' => $familiar->id,
        ]))->assertUnprocessable()->assertJsonValidationErrors('subcategoria_id');

        $salud->update(['activo' => false]);
        $subcategoriaSalud = SubcategoriaLicencia::where('codigo', 'resfrio')->firstOrFail();
        $this->postJson('/api/estudiante/licencias', $this->datosLicencia([
            'categoria_id' => $salud->id,
            'subcategoria_id' => $subcategoriaSalud->id,
        ]))->assertUnprocessable()->assertJsonValidationErrors('categoria_id');

        $this->assertDatabaseCount('licencias', 0);
    }

    public function test_rechaza_categoria_sin_subcategoria_o_viceversa(): void
    {
        $estudiante = $this->estudiante('alumno@sita.test');
        Sanctum::actingAs($estudiante->usuario);
        $subcategoria = SubcategoriaLicencia::where('codigo', 'resfrio')->firstOrFail();

        $this->postJson('/api/estudiante/licencias', $this->datosLicencia([
            'categoria_id' => $subcategoria->categoria_id,
            'motivo' => 'Detalle',
        ]))->assertUnprocessable()->assertJsonValidationErrors('subcategoria_id');

        $this->postJson('/api/estudiante/licencias', $this->datosLicencia([
            'subcategoria_id' => $subcategoria->id,
        ]))->assertUnprocessable()->assertJsonValidationErrors('categoria_id');
    }

    public function test_app_antigua_puede_seguir_enviando_motivo_libre(): void
    {
        $estudiante = $this->estudiante('alumno@sita.test');
        Sanctum::actingAs($estudiante->usuario);

        $this->postJson('/api/estudiante/licencias', $this->datosLicencia([
            'motivo' => 'Motivo de solicitud anterior',
        ]))->assertCreated();

        $this->assertDatabaseHas('licencias', [
            'estudiante_id' => $estudiante->id,
            'motivo' => 'Motivo de solicitud anterior',
            'categoria_id' => null,
            'subcategoria_id' => null,
        ]);

        $this->getJson('/api/estudiante/mis-licencias')
            ->assertOk()
            ->assertJsonPath('data.0.categoria', null)
            ->assertJsonPath('data.0.subcategoria', null);
    }

    public function test_jefe_filtra_por_categoria_sin_ver_otra_carrera(): void
    {
        $jefeUsuario = $this->usuario('jefe_carrera', 'jefe@sita.test');
        JefeCarrera::create([
            'user_id' => $jefeUsuario->id,
            'carrera_asignada' => 'Sistemas Informáticos',
        ]);

        $gestion = Gestion::create(['anio' => '2026', 'es_activa' => true]);
        $cursoSistemas = Curso::create(['nombre' => 'Primero Sistemas', 'gestion' => '2026']);
        $cursoAutomotriz = Curso::create(['nombre' => 'Primero Automotriz', 'gestion' => '2026']);
        $alumnoSistemas = $this->estudiante('sistemas@sita.test');
        $alumnoAutomotriz = $this->estudiante('automotriz@sita.test');

        foreach ([[$alumnoSistemas, $cursoSistemas], [$alumnoAutomotriz, $cursoAutomotriz]] as [$alumno, $curso]) {
            Inscripcion::create([
                'estudiante_id' => $alumno->id,
                'curso_id' => $curso->id,
                'gestion_id' => $gestion->id,
                'estado' => 'Activo',
            ]);
        }

        $salud = CategoriaLicencia::where('codigo', 'salud')->firstOrFail();
        $familiar = CategoriaLicencia::where('codigo', 'familiar')->firstOrFail();

        foreach ([
            [$alumnoSistemas, 'Licencia salud visible', $salud->id],
            [$alumnoSistemas, 'Licencia familiar oculta', $familiar->id],
            [$alumnoAutomotriz, 'Licencia ajena oculta', $salud->id],
        ] as [$alumno, $motivo, $categoriaId]) {
            Licencia::create([
                'estudiante_id' => $alumno->id,
                'motivo' => $motivo,
                'categoria_id' => $categoriaId,
                'fecha_inicio' => '2026-05-03',
                'fecha_fin' => '2026-05-04',
                'estado' => 'pendiente',
            ]);
        }

        $this->actingAs($jefeUsuario)
            ->get(route('jefe.licencias', ['categoria_id' => $salud->id]))
            ->assertOk()
            ->assertSee('Licencia salud visible')
            ->assertDontSee('Licencia familiar oculta')
            ->assertDontSee('Licencia ajena oculta');
    }
}
