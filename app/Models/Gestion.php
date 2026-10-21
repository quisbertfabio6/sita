<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gestion extends Model
{
    use HasFactory;

    protected $table = 'gestiones';
    protected $fillable = ['anio', 'es_activa'];

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class);
    }
}