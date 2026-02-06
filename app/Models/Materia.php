<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    use HasFactory;
    protected $table = 'materias';
    protected $fillable = ['nombre', 'sigla', 'curso_id'];

    public function curso() {
        return $this->belongsTo(Curso::class);
    }

    public function docentes() {
        return $this->belongsToMany(Docente::class, 'materia_docente');
    }

    public function asistencias() {
        return $this->hasMany(Asistencia::class);
    }
}