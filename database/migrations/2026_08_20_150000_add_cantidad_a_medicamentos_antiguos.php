<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La medicacion actual del perfil guarda nombre y cantidad ("850 mg",
     * "2 tomas al dia"): texto libre, no dosis estructurada.
     */
    public function up(): void
    {
        Schema::table('paciente_medicamentos_antiguos', function (Blueprint $table) {
            $table->string('cantidad', 100)->nullable()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('paciente_medicamentos_antiguos', function (Blueprint $table) {
            $table->dropColumn('cantidad');
        });
    }
};
