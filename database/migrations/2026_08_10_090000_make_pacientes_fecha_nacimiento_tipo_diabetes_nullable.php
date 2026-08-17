<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            // El alta por Auth0 (Auth0SessionController::resolverUsuario) crea un
            // paciente minimo con solo usuarioId: Auth0 no tiene forma de aportar
            // fecha de nacimiento ni tipo de diabetes en el signup. El resto de
            // estas columnas las completa despues el onboarding (fuera de aqui).
            $table->date('fechaNacimiento')->nullable()->change();
            $table->string('tipoDiabetes')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            // Advertencia: revertir esto falla en cuanto exista un paciente creado
            // por Auth0 con estas columnas en null (ver down() de
            // 2026_08_09_090000_add_auth0_to_users_table.php para el mismo patron).
            // Un fallo ruidoso es preferible a inventar una fecha o un tipo de
            // diabetes falsos solo para satisfacer el NOT NULL.
            $table->date('fechaNacimiento')->nullable(false)->change();
            $table->string('tipoDiabetes')->nullable(false)->change();
        });
    }
};
