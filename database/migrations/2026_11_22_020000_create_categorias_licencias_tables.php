<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_licencia', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('subcategorias_licencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias_licencia')->restrictOnDelete();
            $table->string('codigo', 50);
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
            $table->unique(['categoria_id', 'codigo']);
        });

        // Campos opcionales: las licencias existentes conservan su motivo original.
        Schema::table('licencias', function (Blueprint $table) {
            $table->foreignId('categoria_id')->nullable()->constrained('categorias_licencia')->restrictOnDelete();
            $table->foreignId('subcategoria_id')->nullable()->constrained('subcategorias_licencia')->restrictOnDelete();
        });

        // Catalogo inicial editable en BD; validar los nombres con la institucion.
        $catalogo = [
            ['salud', 'Salud', [
                ['resfrio', 'Resfrío'],
                ['dentista', 'Dentista'],
                ['operacion', 'Operación'],
                ['dolor_estomacal', 'Dolor estomacal'],
                ['nauseas', 'Náuseas'],
                ['otro_salud', 'Otro motivo de salud'],
            ]],
            ['familiar', 'Familiar', [
                ['emergencia_familiar', 'Emergencia familiar'],
                ['fallecimiento', 'Fallecimiento de familiar'],
                ['cuidado_familiar', 'Cuidado de familiar'],
                ['otro_familiar', 'Otro motivo familiar'],
            ]],
            ['academico', 'Académico', [
                ['actividad_academica', 'Actividad académica'],
                ['tramite_academico', 'Trámite académico'],
                ['otro_academico', 'Otro motivo académico'],
            ]],
            ['otro', 'Otro', [
                ['otro', 'Otro motivo (especificar)'],
            ]],
        ];

        foreach ($catalogo as $orden => [$codigo, $nombre, $subcategorias]) {
            $categoriaId = DB::table('categorias_licencia')->insertGetId([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'orden' => $orden + 1,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($subcategorias as $posicion => [$subcodigo, $subnombre]) {
                DB::table('subcategorias_licencia')->insert([
                    'categoria_id' => $categoriaId,
                    'codigo' => $subcodigo,
                    'nombre' => $subnombre,
                    'orden' => $posicion + 1,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('licencias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subcategoria_id');
            $table->dropConstrainedForeignId('categoria_id');
        });

        Schema::dropIfExists('subcategorias_licencia');
        Schema::dropIfExists('categorias_licencia');
    }
};
