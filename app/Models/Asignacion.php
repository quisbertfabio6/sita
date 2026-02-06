<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asignacion extends Model
{
    use HasFactory;
    protected $table = 'materia_docente'; // Apuntamos a la tabla pivote
    protected $fillable = ['docente_id', 'materia_id'];

    // Relación con Docente
    public function docente() {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    // Relación con Materia
    public function materia() {
        return $this->belongsTo(Materia::class, 'materia_id');
    }
}