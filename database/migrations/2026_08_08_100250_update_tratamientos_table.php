<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tratamientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('doctorId');
            $table->foreignId('doctorId')->nullable()->after('pacienteId')->constrained('doctores');
            $table->foreignId('casoId')->nullable()->after('doctorId')->constrained('casos');
            $table->foreignId('cicloId')->nullable()->after('casoId')->constrained('ciclos');
            $table->enum('estado', ['borrador', 'pendiente_firma', 'firmado', 'enviado'])->default('borrador')->after('cicloId');
            $table->timestamp('firmadoEn')->nullable()->after('estado');
            $table->timestamp('enviadoEn')->nullable()->after('firmadoEn');
            $table->unsignedSmallInteger('version')->default(1)->after('enviadoEn');
            // Un tratamiento firmado no se edita: se reemplaza y queda encadenado.
            $table->foreignId('reemplazaA')->nullable()->after('version')->constrained('tratamientos');
            $table->text('notaDoctor')->nullable()->after('reemplazaA');
            $table->softDeletes();

            $table->index(['pacienteId', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::table('tratamientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('casoId');
            $table->dropConstrainedForeignId('cicloId');
            $table->dropConstrainedForeignId('reemplazaA');
            $table->dropConstrainedForeignId('doctorId');
            $table->dropColumn(['estado', 'firmadoEn', 'enviadoEn', 'version', 'notaDoctor', 'deleted_at']);
            $table->foreignId('doctorId')->nullable()->constrained('users');
        });
    }
};
