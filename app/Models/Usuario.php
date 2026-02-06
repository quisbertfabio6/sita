<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // Importante para el Login
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Importante para la API móvil

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre_completo',
        'email',
        'password',
        'rol_id',
        'activo'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Relaciones
    public function rol() {
        return $this->belongsTo(Rol::class);
    }

    public function estudiante() {
        return $this->hasOne(Estudiante::class, 'user_id');
    }

    public function docente() {
        return $this->hasOne(Docente::class, 'user_id');
    }

    public function jefeCarrera() {
        return $this->hasOne(JefeCarrera::class, 'user_id');
    }
}