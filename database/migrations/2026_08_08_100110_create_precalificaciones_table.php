<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('precalificaciones', function (Blueprint $table) {
            $table->id();
            // Nullable: el filtro clinico corre antes de crear la cuenta.
            $table->foreignId('pacienteId')->nullable()->constrained('pacientes');
            $table->string('leadEmail')->nullable();
            $table->enum('resultado', ['apto', 'no_apto', 'urgente']);
            $table->string('motivo')->nullable();
            $table->unsignedSmallInteger('versionCuestionario')->default(1);
            $table->timestamp('respondidoEn');
            $table->timestamps();

            $table->index('resultado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('precalificaciones');
    }
};
