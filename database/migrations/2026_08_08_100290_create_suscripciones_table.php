<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pacienteId')->constrained('pacientes');
            $table->foreignId('planId')->constrained('planes');
            // Nullable: si la clinica paga por el paciente, la cubre una licencia.
            $table->foreignId('licenciaId')->nullable()->constrained('licencias');
            $table->enum('estado', ['prueba', 'activa', 'vencida', 'cancelada'])->default('prueba');
            $table->date('inicio');
            $table->date('fin')->nullable();
            $table->date('proximoCobro')->nullable();
            $table->unsignedSmallInteger('consultasUsadas')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pacienteId', 'estado']);
            $table->index('proximoCobro');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripciones');
    }
};
