<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un diagnostico/tratamiento generado por la IA nace con aceptadoDoctor=false;
 * el doctor lo marca true (PATCH) cuando lo revisa y lo hace suyo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnosticos', function (Blueprint $table) {
            $table->boolean('aceptadoDoctor')->default(false)->after('estado');
        });

        Schema::table('tratamientos', function (Blueprint $table) {
            $table->boolean('aceptadoDoctor')->default(false)->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('diagnosticos', function (Blueprint $table) {
            $table->dropColumn('aceptadoDoctor');
        });

        Schema::table('tratamientos', function (Blueprint $table) {
            $table->dropColumn('aceptadoDoctor');
        });
    }
};
