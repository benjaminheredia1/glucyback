<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unidad de trabajo del doctor. La Bandeja ordena por `urgencia` y el SLA que
     * grafica el admin sale de abiertoEn / asignadoEn / cerradoEn.
     */
    public function up(): void
    {
        Schema::create('casos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pacienteId')->constrained('pacientes');
            $table->foreignId('doctorId')->nullable()->constrained('doctores');
            $table->foreignId('cicloId')->nullable()->constrained('ciclos');
            $table->enum('tipo', ['ingreso', 'ajuste_ciclo', 'revision_15d', 'alerta']);
            $table->enum('urgencia', ['urgente', 'pendiente', 'estable'])->default('pendiente');
            $table->enum('estado', ['abierto', 'en_proceso', 'cerrado'])->default('abierto');
            $table->string('titulo');
            $table->text('nota')->nullable();
            $table->timestamp('abiertoEn');
            $table->timestamp('asignadoEn')->nullable();
            $table->timestamp('cerradoEn')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['doctorId', 'estado', 'urgencia']);
            $table->index(['pacienteId', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casos');
    }
};
