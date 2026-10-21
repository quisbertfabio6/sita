<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('inscripciones', function (Blueprint $table) {
        $table->id();
        $table->foreignId('estudiante_id')->constrained('estudiantes')->onDelete('cascade');
        $table->foreignId('curso_id')->constrained('cursos')->onDelete('cascade');
        $table->foreignId('gestion_id')->constrained('gestiones')->onDelete('cascade');
        $table->string('estado')->default('Activo'); // Para saber si sigue activo, se retiró, etc.
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscripcions');
    }
};
