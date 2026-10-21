<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('materias', function (Blueprint $table) {
        $table->time('hora_inicio')->nullable();
        $table->integer('minutos_tolerancia')->default(15); // 15 minutos por defecto
    });
}

public function down(): void
{
    Schema::table('materias', function (Blueprint $table) {
        $table->dropColumn(['hora_inicio', 'minutos_tolerancia']);
    });
}
};
