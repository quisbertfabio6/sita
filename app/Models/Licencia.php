<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Licencia extends Model
{
    use HasFactory;
    protected $table = 'licencias';
    protected $fillable = [
        'estudiante_id', 'jefe_carrera_id', 'motivo', 'categoria_id', 'subcategoria_id', 
        'archivo_adjunto', 'fecha_inicio', 'fecha_fin', 
        'estado', 'comentario_admin'
    ];

    public function categoria() {
        return $this->belongsTo(CategoriaLicencia::class, 'categoria_id');
    }

    public function subcategoria() {
        return $this->belongsTo(SubcategoriaLicencia::class, 'subcategoria_id');
    }

    public function estudiante() {
        return $this->belongsTo(Estudiante::class);
    }

    public function jefeCarrera() {
        return $this->belongsTo(JefeCarrera::class, 'jefe_carrera_id');
    }

    /**
     * Mostrar el curso de la gestion en que se solicito la licencia.
     * estudiantes.curso_id ya no existe: ahora se usan inscripciones.
     */
    public function cursoDeLaSolicitud(): ?Curso
    {
        $inscripciones = $this->estudiante?->inscripciones;
        if (!$inscripciones || !$this->created_at) {
            return null;
        }

        $anio = $this->created_at->format('Y');
        return $inscripciones
            ->first(fn (Inscripcion $inscripcion) => $inscripcion->gestion?->anio === $anio)
            ?->curso;
    }
}