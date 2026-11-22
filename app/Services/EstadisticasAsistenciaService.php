<?php

namespace App\Services;

use App\Models\Asistencia;
use Illuminate\Support\Collection;

class EstadisticasAsistenciaService
{
    /**
     * Una clase se cuenta por fecha distinta con registros en la materia.
     * No representa aun el calendario oficial de sesiones programadas.
     */
    public function totalClases(int $materiaId, string $anio, ?string $desde = null, ?string $hasta = null): int
    {
        $consulta = Asistencia::where('materia_id', $materiaId)
            ->whereBetween('fecha', [$anio . '-01-01', $anio . '-12-31']);

        if ($desde && $hasta) {
            $consulta->whereBetween('fecha', [$desde, $hasta]);
        }

        return $consulta->distinct()->count('fecha');
    }

    /**
     * Presente = 1 punto; atraso = 0.5; licencia = 1 solo si esta aprobada
     * y cubre la fecha de la asistencia; falta = 0.
     *
     * Solo se contabilizan los estados que existen en la base de datos:
     * no se inventan faltas para fechas sin registro.
     */
    public function calcular(Collection $asistencias, Collection $licenciasAprobadas, int $totalClases): array
    {
        $conteos = [
            'presente' => 0,
            'atraso' => 0,
            'falta' => 0,
            'licencia' => 0,
        ];

        $puntos = 0.0;

        // Un estudiante solo debe tener una asistencia por materia y fecha.
        // Evitamos que registros duplicados inflen el porcentaje.
        foreach ($asistencias->sortBy('id')->unique('fecha') as $asistencia) {
            $estado = $asistencia->estado;
            if (!array_key_exists($estado, $conteos)) {
                continue;
            }

            $conteos[$estado]++;

            if ($estado === 'presente') {
                $puntos += 1;
            } elseif ($estado === 'atraso') {
                $puntos += 0.5;
            } elseif ($estado === 'licencia') {
                $fecha = substr((string) $asistencia->fecha, 0, 10);
                $aprobada = $licenciasAprobadas->contains(function ($licencia) use ($fecha) {
                    return $licencia->estado === 'aprobada'
                        && substr((string) $licencia->fecha_inicio, 0, 10) <= $fecha
                        && substr((string) $licencia->fecha_fin, 0, 10) >= $fecha;
                });

                if ($aprobada) {
                    $puntos += 1;
                }
            }
        }

        $porcentaje = $totalClases > 0
            ? min(100, (int) round(($puntos / $totalClases) * 100))
            : 0;

        return [
            'asistencias_puntos' => $puntos,
            'puntos' => $puntos,
            'porcentaje' => $porcentaje,
            'riesgo' => $totalClases > 0 && $porcentaje < 80,
            'sin_clases' => $totalClases === 0,
            'conteos' => $conteos,
        ];
    }
}
