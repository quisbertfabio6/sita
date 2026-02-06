<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    use HasFactory;
    protected $table = 'docentes';
    protected $fillable = ['user_id', 'codigo_docente'];

    public function usuario() {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    // Relación muchos a muchos con Materias (usando la tabla pivote)
    public function materias() {
        return $this->belongsToMany(Materia::class, 'materia_docente');
    }
}