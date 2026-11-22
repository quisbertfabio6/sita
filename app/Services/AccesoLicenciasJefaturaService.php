<?php

namespace App\Services;

use App\Models\JefeCarrera;
use Illuminate\Database\Eloquent\Builder;

class AccesoLicenciasJefaturaService
{
    /**
     * SITA guarda las carreras como texto y las identifica en el nombre del curso.
     * Centralizamos esa regla para que listado, PDF, KPIs y aprobación
     * utilicen exactamente el mismo filtro.
     */
    private function palabrasClave(?JefeCarrera $jefe): array
    {
        $mapa = [
            'Sistemas Informáticos' => 'Sistemas',
            'Mecánica Automotriz' => 'Automotriz',
            'Mecánica Industrial' => 'Mec. Industrial',
            'Electromecánica' => 'Electromecánica',
            'Electrónica' => 'Electrónica',
            'Construcción Civil' => 'Construcción Civil',
        ];

        if (!$jefe || !$jefe->carrera_asignada) {
            return [];
        }

        $palabras = [];
        foreach (explode(',', $jefe->carrera_asignada) as $carrera) {
            $nombre = trim($carrera);
            if ($nombre === '' || $nombre === 'Sin asignar') {
                continue;
            }

            $palabras[] = $mapa[$nombre] ?? $nombre;
        }

        return array_values(array_unique($palabras));
    }

    /**
     * Escapar comodines de LIKE. La asignación de carrera no debe permitir
     * que % o _ funcionen como patrones para acceder a todos los cursos.
     */
    private function aplicarNombresDeCarrera(Builder $query, array $palabras): Builder
    {
        if ($palabras === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($palabras) {
            foreach ($palabras as $palabra) {
                $literal = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $palabra);
                $q->orWhereRaw("cursos.nombre LIKE ? ESCAPE '!'", ['%' . $literal . '%']);
            }
        });
    }

    public function filtrarCursos(Builder $query, ?JefeCarrera $jefe): Builder
    {
        return $this->aplicarNombresDeCarrera($query, $this->palabrasClave($jefe));
    }

    /**
     * Una licencia corresponde a la carrera en la que el estudiante estaba
     * inscrito durante la gestión en que presentó su solicitud (created_at).
     * Así una inscripción de otro año no autoriza a otra jefatura.
     *
     * TODO: almacenar gestion_id/curso_id directamente en licencias para
     * identificar sin ambigüedad los traslados entre carreras en un mismo año.
     */
    public function filtrarLicencias(Builder $query, ?JefeCarrera $jefe): Builder
    {
        $palabras = $this->palabrasClave($jefe);
        if ($palabras === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('estudiante.inscripciones', function (Builder $inscripcion) use ($palabras) {
            $inscripcion
                ->whereHas('gestion', function (Builder $gestion) {
                    $gestion->whereRaw('gestiones.anio = substr(licencias.created_at, 1, 4)');
                })
                ->whereHas('curso', function (Builder $curso) use ($palabras) {
                    $this->aplicarNombresDeCarrera($curso, $palabras);
                });
        });
    }
}
