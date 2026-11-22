<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Artisan::command('sita:respaldo', function () {
    try {
        $nombre = app(\App\Services\RespaldoService::class)->crear();
        $this->info('Copia de seguridad generada: ' . $nombre);
        $this->line('Ubicación: storage/app/private/sita-backups/');
    } catch (\Throwable $e) {
        $this->error('No se pudo generar el respaldo: ' . $e->getMessage());
        return 1;
    }

    return 0;
})->purpose('Generar una copia completa de SITA (todas las gestiones y archivos)');
