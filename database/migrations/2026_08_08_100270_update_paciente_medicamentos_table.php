<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paciente_medicamentos', function (Blueprint $table) {
            // Sin este FK no se sabe que plan firmado justifica la receta.
            $table->foreignId('tratamientoId')->nullable()->after('pacienteId')->constrained('tratamientos');
            $table->text('indicaciones')->nullable()->after('frecuencia');
            $table->softDeletes();

            $table->index(['pacienteId', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::table('paciente_medicamentos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tratamientoId');
            $table->dropColumn(['indicaciones', 'deleted_at']);
        });
    }
};
