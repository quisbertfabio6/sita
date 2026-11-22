<?php

namespace App\Services;

use App\Models\Licencia;
use Carbon\Carbon;

class CupoLicenciasSemestralService
{
    public const MAXIMO = 3;

    /**
     * El semestre se define por la fecha de envio de la solicitud,
     * en horario de Bolivia (no por las fechas de ausencia).
     * Las fechas created_at se guardan en UTC.
     */
    public function resumen(int $estudianteId): array
    {
        $ahoraLocal = now()->setTimezone('America/La_Paz');
        $anio = (int) $ahoraLocal->year;
        $semestre = $ahoraLocal->month <= 6 ? 1 : 2;

        $inicioLocal = Carbon::create(
            $anio,
            $semestre === 1 ? 1 : 7,
            1, 0, 0, 0,
            'America/La_Paz'
        );
        $finExclusivoLocal = $inicioLocal->copy()->addMonths(6);

        $utilizadas = Licencia::query()
            ->where('estudiante_id', $estudianteId)
            ->whereIn('estado', ['pendiente', 'aprobada'])
            ->where('created_at', '>=', $inicioLocal->copy()->utc())
            ->where('created_at', '<', $finExclusivoLocal->copy()->utc())
            ->count();

        return [
            'anio' => $anio,
            'semestre' => $semestre,
            'fecha_inicio' => $inicioLocal->toDateString(),
            'fecha_fin' => $finExclusivoLocal->copy()->subDay()->toDateString(),
            'maximo' => self::MAXIMO,
            'utilizadas' => $utilizadas,
            'disponibles' => max(0, self::MAXIMO - $utilizadas),
            'puede_solicitar' => $utilizadas < self::MAXIMO,
        ];
    }
}
