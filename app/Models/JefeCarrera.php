<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JefeCarrera extends Model
{
    use HasFactory;
    protected $table = 'jefes_carrera';
    protected $fillable = ['user_id', 'carrera_asignada'];

    public function usuario() {
        return $this->belongsTo(Usuario::class, 'user_id');
    }
    
    // Licencias que este jefe ha validado (opcional)
    public function licenciasGestionadas() {
        return $this->hasMany(Licencia::class, 'jefe_carrera_id');
    }
}