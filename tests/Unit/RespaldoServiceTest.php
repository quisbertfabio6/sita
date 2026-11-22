<?php

namespace Tests\Unit;

use App\Services\RespaldoService;
use Tests\TestCase;

class RespaldoServiceTest extends TestCase
{
    public function test_no_permite_descargar_rutas_ajenas_al_directorio_de_respaldos(): void
    {
        $servicio = new RespaldoService();

        $this->assertNull($servicio->rutaDescarga('../.env'));
        $this->assertNull($servicio->rutaDescarga('archivo.zip'));
        $this->assertNull($servicio->rutaDescarga('sita-completo-20260101-120000-12345678.zip/../../.env'));
    }

    public function test_un_archivo_con_nombre_valido_pero_inexistente_no_se_descarga(): void
    {
        $servicio = new RespaldoService();

        $this->assertNull($servicio->rutaDescarga('sita-completo-19000101-000000-00000000.zip'));
    }
}
