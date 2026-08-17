<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas_laboratorio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pacienteId')->constrained('pacientes');
            $table->foreignId('laboratorioId')->constrained('laboratorios');
            $table->date('fecha');
            $table->enum('franja', ['manana', 'tarde']);
            $table->string('direccion');
            $table->string('referencia')->nullable();
            $table->enum('estado', ['agendada', 'realizada', 'cancelada'])->default('agendada');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pacienteId', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas_laboratorio');
    }
};
