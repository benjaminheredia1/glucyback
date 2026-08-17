<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licencias', function (Blueprint $table) {
            $table->foreignId('planId')->nullable()->after('clinicaId')->constrained('planes');
            // Contador contra `cantidad`: hoy nada impide sobreasignar la licencia.
            $table->unsignedInteger('usadas')->default(0)->after('cantidad');
            $table->softDeletes();
        });

        Schema::table('licencias', function (Blueprint $table) {
            $table->enum('estado', ['activa', 'inactiva', 'suspendida', 'vencida'])
                ->default('inactiva')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('licencias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('planId');
            $table->dropColumn(['usadas', 'deleted_at']);
            $table->enum('estado', ['activa', 'inactiva'])->default('inactiva')->change();
        });
    }
};
