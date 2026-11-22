<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubcategoriaLicencia extends Model
{
    protected $table = 'subcategorias_licencia';

    protected $fillable = ['categoria_id', 'codigo', 'nombre', 'activo', 'orden'];

    protected $casts = ['activo' => 'boolean'];

    public function categoria()
    {
        return $this->belongsTo(CategoriaLicencia::class, 'categoria_id');
    }

    public function licencias()
    {
        return $this->hasMany(Licencia::class, 'subcategoria_id');
    }
}
