<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pacienteId')->constrained('pacientes');
            $table->foreignId('medicionId')->nullable()->constrained('mediciones');
            $table->foreignId('reglaId')->nullable()->constrained('reglas_alerta');
            $table->foreignId('casoId')->nullable()->constrained('casos');
            $table->enum('tipo', ['valor_critico', 'sin_registro', 'estudio_vencido', 'ciclo_vencido']);
            $table->enum('severidad', ['critica', 'alta', 'media']);
            $table->string('mensaje');
            $table->enum('estado', ['abierta', 'vista', 'atendida'])->default('abierta');
            $table->foreignId('atendidaPor')->nullable()->constrained('doctores');
            $table->timestamp('atendidaEn')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pacienteId', 'estado']);
            $table->index(['estado', 'severidad']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
