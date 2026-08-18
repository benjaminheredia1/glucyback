<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La materializacion de tomas hace `firstOrCreate` por medicamento + hora
 * programada; el unico evita duplicados ante dos peticiones simultaneas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tomas', function (Blueprint $table) {
            $table->unique(['pacienteMedicamentoId', 'programadaEn'], 'tomas_medicamento_programada_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tomas', function (Blueprint $table) {
            $table->dropUnique('tomas_medicamento_programada_unique');
        });
    }
};
