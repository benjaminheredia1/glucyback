<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licencia_usuarios', function (Blueprint $table) {
            $table->enum('estado', ['activa', 'revocada'])->default('activa')->after('licenciaId');
            $table->timestamp('asignadoEn')->nullable()->after('estado');
            $table->timestamp('revocadoEn')->nullable()->after('asignadoEn');

            $table->unique(['licenciaId', 'usuarioId']);
        });
    }

    public function down(): void
    {
        Schema::table('licencia_usuarios', function (Blueprint $table) {
            $table->dropUnique(['licenciaId', 'usuarioId']);
            $table->dropColumn(['estado', 'asignadoEn', 'revocadoEn']);
        });
    }
};
