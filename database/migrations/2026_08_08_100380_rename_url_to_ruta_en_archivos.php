<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `url` daba a entender que se guardaba una direccion publica. Lo que se
     * guarda es la ruta relativa dentro del disco privado: el acceso siempre pasa
     * por la API, nunca por una URL directa.
     */
    public function up(): void
    {
        Schema::table('archivos', function (Blueprint $table) {
            $table->renameColumn('url', 'ruta');
        });

        Schema::table('archivos', function (Blueprint $table) {
            $table->string('disk')->default('medico')->change();
        });
    }

    public function down(): void
    {
        Schema::table('archivos', function (Blueprint $table) {
            $table->renameColumn('ruta', 'url');
        });

        Schema::table('archivos', function (Blueprint $table) {
            $table->string('disk')->default('local')->change();
        });
    }
};
