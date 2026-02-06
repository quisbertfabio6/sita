<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    use HasFactory;
    protected $table = 'asistencias';
    protected $fillable = [
        'estudiante_id', 'materia_id', 'fecha', 'hora', 
        'estado', 'observacion', 'es_sincronizado'
    ];

    public function estudiante() {
        return $this->belongsTo(Estudiante::class);
    }

    public function materia() {
        return $this->belongsTo(Materia::class);
    }
}