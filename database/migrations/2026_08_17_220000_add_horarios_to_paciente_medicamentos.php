<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Horarios estructurados ("HH:MM") para generar las tomas del dia. La
 * `frecuencia` en texto libre se conserva como descripcion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paciente_medicamentos', function (Blueprint $table) {
            $table->json('horarios')->nullable()->after('frecuencia');
        });
    }

    public function down(): void
    {
        Schema::table('paciente_medicamentos', function (Blueprint $table) {
            $table->dropColumn('horarios');
        });
    }
};
