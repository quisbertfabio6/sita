<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 3. Jefes de Carrera
        Schema::create('jefes_carrera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('usuarios')->onDelete('cascade');
            $table->string('carrera_asignada', 100)->nullable();
            $table->timestamps();
        });

        // 4. Docentes
        Schema::create('docentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('usuarios')->onDelete('cascade');
            $table->string('codigo_docente', 50)->nullable();
            $table->timestamps();
        });

        // 6. Estudiantes (Incluye la corrección de curso_id)
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('usuarios')->onDelete('cascade');
            $table->foreignId('curso_id')->constrained('cursos'); // ¡Corrección vital aplicada!
            $table->string('ci', 20)->nullable()->unique();
            $table->string('matricula', 50)->nullable()->unique();
            $table->string('codigo_qr', 255)->unique(); // Campo para el QR importado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
        Schema::dropIfExists('docentes');
        Schema::dropIfExists('jefes_carrera');
    }
};