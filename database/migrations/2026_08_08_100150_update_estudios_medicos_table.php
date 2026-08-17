<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estudios_medicos', function (Blueprint $table) {
            $table->enum('estado', ['pendiente', 'en_revision', 'aprobado', 'rechazado'])
                ->default('pendiente')
                ->after('unidad');
            $table->string('motivoRechazo')->nullable()->after('estado');
            $table->foreignId('validadoPor')->nullable()->after('motivoRechazo')->constrained('doctores');
            $table->timestamp('validadoEn')->nullable()->after('validadoPor');
            // Un estudio rechazado se vuelve a subir sin perder el historial.
            $table->unsignedSmallInteger('intento')->default(1)->after('validadoEn');
            $table->enum('origen', ['carga', 'laboratorio'])->default('carga')->after('intento');
            $table->softDeletes();

            $table->index(['pacienteId', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::table('estudios_medicos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validadoPor');
            $table->dropColumn(['estado', 'motivoRechazo', 'validadoEn', 'intento', 'origen', 'deleted_at']);
        });
    }
};
