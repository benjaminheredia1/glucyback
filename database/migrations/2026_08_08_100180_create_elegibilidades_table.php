<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elegibilidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pacienteId')->constrained('pacientes');
            $table->string('nroInforme')->unique();
            $table->enum('semaforo', ['verde', 'amarillo', 'rojo']);
            $table->text('resumen')->nullable();
            // Criterios evaluados y su resultado, para poder reconstruir el informe.
            $table->json('criterios')->nullable();
            $table->timestamp('generadaEn');
            $table->date('vigenciaHasta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pacienteId', 'semaforo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elegibilidades');
    }
};
