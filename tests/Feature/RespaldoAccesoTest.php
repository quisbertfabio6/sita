<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Usuario;
use Tests\TestCase;

class RespaldoAccesoTest extends TestCase
{
    public function test_visitantes_no_pueden_acceder_a_los_respaldos(): void
    {
        $this->get('/admin/respaldos')->assertRedirect(route('login'));
        $this->post('/admin/respaldos/crear')->assertRedirect(route('login'));
        $this->get('/admin/respaldos/descargar/sita-completo-20260101-000000-12345678.zip')
            ->assertRedirect(route('login'));
    }

    public function test_un_docente_no_puede_listar_crear_ni_descargar_respaldos(): void
    {
        $usuario = new Usuario();
        $usuario->activo = true;
        $usuario->setRelation('rol', new Rol(['nombre' => 'docente']));

        $this->actingAs($usuario);
        $this->get('/admin/respaldos')->assertForbidden();
        $this->post('/admin/respaldos/crear')->assertForbidden();
        $this->get('/admin/respaldos/descargar/sita-completo-20260101-000000-12345678.zip')
            ->assertForbidden();
    }
}
