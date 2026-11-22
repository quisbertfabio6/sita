<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\Inscripcion;
use App\Models\Rol;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeguridadCredencialesTokensTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol = 'estudiante', string $email = 'alumno@sita.test'): Usuario
    {
        $rolModelo = Rol::firstOrCreate(['nombre' => $rol]);

        return Usuario::create([
            'nombre_completo' => 'Usuario de prueba',
            'email' => $email,
            'password' => Hash::make('ClaveSegura1234'),
            'rol_id' => $rolModelo->id,
            'activo' => true,
        ]);
    }

    private function estudiante(): array
    {
        $usuario = $this->usuario();
        $estudiante = Estudiante::create([
            'user_id' => $usuario->id,
            'codigo_qr' => 'QR-CREDENCIALES-1',
        ]);
        $gestion = Gestion::create(['anio' => '2026', 'es_activa' => true]);
        $curso = Curso::create(['nombre' => 'Primero Sistemas', 'gestion' => '2026']);
        Inscripcion::create([
            'estudiante_id' => $estudiante->id,
            'curso_id' => $curso->id,
            'gestion_id' => $gestion->id,
            'estado' => 'Activo',
        ]);

        return [$usuario, $estudiante, $curso];
    }

    public function test_login_api_emite_token_con_caducidad(): void
    {
        $this->travelTo(Carbon::parse('2026-05-02 09:00:00'));
        $usuario = $this->usuario();

        $respuesta = $this->postJson('/api/login', [
            'email' => $usuario->email,
            'password' => 'ClaveSegura1234',
            'device_name' => 'Celular Android',
        ])->assertOk()->assertJsonStructure(['token', 'user', 'rol']);

        $this->assertNotEmpty($respuesta->json('token'));
        $token = $usuario->tokens()->firstOrFail();
        $this->assertSame('Celular Android', $token->name);
        $this->assertNotNull($token->expires_at);
        $this->assertSame(43200, (int) config('sanctum.expiration'));
        $this->assertTrue($token->expires_at->equalTo(now()->addDays(30)));
    }

    public function test_token_vencido_no_accede_a_api(): void
    {
        $this->travelTo(Carbon::parse('2026-05-02 09:00:00'));
        $usuario = $this->usuario();
        $token = $usuario->createToken('celular', ['*'], now()->addDays(30))->plainTextToken;

        $this->withToken($token)->getJson('/api/user')->assertOk();

        $this->travelTo(Carbon::parse('2026-06-02 09:00:00'));
        // Forzar que el guard lea nuevamente el token en la siguiente peticion.
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_logout_api_revoca_token_actual(): void
    {
        $usuario = $this->usuario();
        $token = $usuario->createToken('celular')->plainTextToken;
        $id = $usuario->tokens()->firstOrFail()->id;

        $this->withToken($token)->postJson('/api/logout')
            ->assertOk()->assertJsonPath('message', 'Sesion cerrada correctamente');

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $id]);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_logout_api_requiere_autenticacion(): void
    {
        $this->postJson('/api/logout')->assertUnauthorized();
    }

    public function test_login_api_rechaza_device_name_demasiado_largo(): void
    {
        $usuario = $this->usuario();
        $this->postJson('/api/login', [
            'email' => $usuario->email,
            'password' => 'ClaveSegura1234',
            'device_name' => str_repeat('a', 101),
        ])->assertUnprocessable()->assertJsonValidationErrors('device_name');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_api_limita_intentos_fallidos(): void
    {
        $this->usuario();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'alumno@sita.test',
                'password' => 'incorrecta',
                'device_name' => 'celular',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/login', [
            'email' => 'alumno@sita.test',
            'password' => 'incorrecta',
            'device_name' => 'celular',
        ])->assertStatus(429);
    }

    public function test_cuentas_distintas_de_la_misma_red_no_comparten_bloqueo(): void
    {
        $this->usuario('estudiante', 'alumno@sita.test');
        $this->usuario('docente', 'docente@sita.test');

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'alumno@sita.test',
                'password' => 'incorrecta',
                'device_name' => 'celular',
            ])->assertUnauthorized();
        }

        // El limite por cuenta no debe impedir el acceso a otro usuario
        // de la misma red, salvo que se supere el limite general por IP.
        $this->postJson('/api/login', [
            'email' => 'docente@sita.test',
            'password' => 'ClaveSegura1234',
            'device_name' => 'celular',
        ])->assertOk();
    }

    public function test_login_web_limita_intentos_fallidos(): void
    {
        $this->usuario('administrador', 'admin@sita.test');
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.post'), [
                'email' => 'admin@sita.test',
                'password' => 'incorrecta',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login.post'), [
            'email' => 'admin@sita.test',
            'password' => 'incorrecta',
        ])->assertStatus(429);
    }
}
