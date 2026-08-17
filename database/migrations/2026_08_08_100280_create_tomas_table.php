<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adherencia. Los checkboxes de dosis de "Mi plan" son dato clinico y se
     * persisten, no quedan en estado de UI.
     */
    public function up(): void
    {
        Schema::create('tomas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pacienteMedicamentoId')->constrained('paciente_medicamentos')->cascadeOnDelete();
            $table->timestamp('programadaEn');
            $table->timestamp('tomadaEn')->nullable();
            $table->enum('estado', ['pendiente', 'tomada', 'omitida'])->default('pendiente');
            $table->timestamps();

            $table->index(['pacienteMedicamentoId', 'programadaEn']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tomas');
    }
};
