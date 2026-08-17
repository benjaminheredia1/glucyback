<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Identificador estable de la identidad en Auth0: 'auth0|abc',
            // 'google-oauth2|123'. Nulo mientras el usuario no haya entrado nunca.
            $table->string('auth0Sub')->nullable()->unique()->after('email');

            // La identidad vive en Auth0: Laravel deja de guardar contrasenas.
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['auth0Sub']);
            $table->dropColumn('auth0Sub');
            // Advertencia: restaurar NOT NULL falla si existen usuarios solo Auth0.
            // Un fallo ruidoso es preferible a inventar una contrasena o borrar la cuenta.
            $table->string('password')->nullable(false)->change();
        });
    }
};
