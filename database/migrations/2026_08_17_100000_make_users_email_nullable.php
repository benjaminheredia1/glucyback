<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Un paciente anonimo (POST /auth/anonimo) nace sin correo: lo aporta
            // Auth0 recien al reclamar la cuenta. El indice unico se conserva:
            // MySQL y SQLite admiten varios NULL en una columna unique.
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Advertencia: restaurar NOT NULL falla si existen anonimos vivos.
            // Un fallo ruidoso es preferible a inventar un correo o borrarlos.
            $table->string('email')->nullable(false)->change();
        });
    }
};
