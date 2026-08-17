<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // idRol era un puntero generico sin FK: el rol lo da `rol` y el perfil
            // lo dan las tablas `doctores` / `pacientes`.
            $table->dropColumn('idRol');
            $table->string('telefono')->nullable()->after('email');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('idRol')->nullable();
            $table->dropColumn(['telefono', 'deleted_at']);
        });
    }
};
