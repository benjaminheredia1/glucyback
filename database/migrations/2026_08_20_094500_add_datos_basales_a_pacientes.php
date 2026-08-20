<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos basales que consume el agente AnalistaMedico. Van en migracion nueva
 * (no en add_pacientes) porque esa ya corrio en produccion: editarla no
 * cambia una base existente. softDeletes no se toca: lo agrego la 100050.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->string('peso')->nullable();
            $table->string('talla')->nullable();
            $table->string('telefono')->nullable();
            $table->string('imc')->nullable();
            $table->string('aniosConDiabetes')->nullable();
            $table->string('presionArterial')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropColumn(['peso', 'talla', 'telefono', 'imc', 'aniosConDiabetes', 'presionArterial']);
        });
    }
};
