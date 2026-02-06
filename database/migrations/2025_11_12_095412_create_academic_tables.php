<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 7. Materias
        Schema::create('materias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('sigla', 20)->nullable();
            $table->foreignId('curso_id')->constrained('cursos');
            $table->timestamps();
        });

        // 8. Materia Docente (Pivote)
        Schema::create('materia_docente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('docentes');
            $table->foreignId('materia_id')->constrained('materias');
            $table->timestamps();
        });

        // 9. Asistencias (Corazón del sistema offline)
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes');
            $table->foreignId('materia_id')->constrained('materias');
            $table->date('fecha');
            $table->time('hora');
            // Usamos enum para los estados
            $table->enum('estado', ['presente', 'falta', 'atraso', 'licencia'])->default('falta');
            $table->string('observacion', 255)->nullable();
            $table->boolean('es_sincronizado')->default(true); // Vital para la app
            $table->timestamps();
        });

        // 10. Licencias (Gestión digital)
        Schema::create('licencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes');
            $table->foreignId('jefe_carrera_id')->nullable()->constrained('jefes_carrera');
            $table->text('motivo');
            $table->string('archivo_adjunto', 255)->nullable(); // Para la foto/PDF
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
            $table->text('comentario_admin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licencias');
        Schema::dropIfExists('asistencias');
        Schema::dropIfExists('materia_docente');
        Schema::dropIfExists('materias');
    }
};