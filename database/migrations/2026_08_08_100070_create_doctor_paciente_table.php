<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_paciente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctorId')->constrained('doctores');
            $table->foreignId('pacienteId')->constrained('pacientes');
            $table->date('desde');
            $table->date('hasta')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['doctorId', 'activo']);
            $table->index(['pacienteId', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_paciente');
    }
};
