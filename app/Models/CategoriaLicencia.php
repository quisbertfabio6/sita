<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaLicencia extends Model
{
    protected $table = 'categorias_licencia';

    protected $fillable = ['codigo', 'nombre', 'activo', 'orden'];

    protected $casts = ['activo' => 'boolean'];

    public function subcategorias()
    {
        return $this->hasMany(SubcategoriaLicencia::class, 'categoria_id')
            ->orderBy('orden')->orderBy('nombre');
    }

    public function licencias()
    {
        return $this->hasMany(Licencia::class, 'categoria_id');
    }
}
