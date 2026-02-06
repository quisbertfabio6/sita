<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Licencia extends Model
{
    use HasFactory;
    protected $table = 'licencias';
    protected $fillable = [
        'estudiante_id', 'jefe_carrera_id', 'motivo', 
        'archivo_adjunto', 'fecha_inicio', 'fecha_fin', 
        'estado', 'comentario_admin'
    ];

    public function estudiante() {
        return $this->belongsTo(Estudiante::class);
    }

    public function jefeCarrera() {
        return $this->belongsTo(JefeCarrera::class, 'jefe_carrera_id');
    }
}