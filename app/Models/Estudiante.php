<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    use HasFactory;
    protected $table = 'estudiantes';
    protected $fillable = ['user_id', 'ci', 'matricula', 'codigo_qr'];

    // Un estudiante es un usuario
    public function usuario() {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    // Pertenece a un curso (ej. Primero A)
    /*public function curso() {
        return $this->belongsTo(Curso::class);
    }*/
    // Borra o comenta la función antigua public function curso() { ... }
    
    // Agrega esta nueva función:
    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class);
    }

    // Helper para obtener el curso actual más rápido
    public function cursoActual()
    {
        $gestionActiva = Gestion::where('es_activa', true)->first();
        if($gestionActiva) {
            $inscripcion = $this->inscripciones()->where('gestion_id', $gestionActiva->id)->first();
            return $inscripcion ? $inscripcion->curso : null;
        }
        return null;
    }

    public function asistencias() {
        return $this->hasMany(Asistencia::class);
    }

    public function licencias() {
        return $this->hasMany(Licencia::class);
    }
}