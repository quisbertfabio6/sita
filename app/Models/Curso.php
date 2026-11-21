<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    use HasFactory;
    protected $table = 'cursos';
    protected $fillable = ['nombre', 'gestion'];

    public function estudiantes() {
        return $this->belongsToMany(Estudiante::class, 'inscripciones', 'curso_id', 'estudiante_id')
            ->withPivot(['gestion_id', 'estado'])
            ->withTimestamps();
    }

    public function materias() {
        return $this->hasMany(Materia::class);
    }
}