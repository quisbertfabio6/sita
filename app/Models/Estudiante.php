<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    use HasFactory;
    protected $table = 'estudiantes';
    protected $fillable = ['user_id', 'curso_id', 'ci', 'matricula', 'codigo_qr'];

    // Un estudiante es un usuario
    public function usuario() {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    // Pertenece a un curso (ej. Primero A)
    public function curso() {
        return $this->belongsTo(Curso::class);
    }

    public function asistencias() {
        return $this->hasMany(Asistencia::class);
    }

    public function licencias() {
        return $this->hasMany(Licencia::class);
    }
}