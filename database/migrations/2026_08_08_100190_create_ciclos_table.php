<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ciclos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pacienteId')->constrained('pacientes');
            $table->unsignedInteger('numero');
            $table->date('inicio');
            $table->date('fin');
            $table->unsignedSmallInteger('medicionesRequeridas')->default(3);
            $table->unsignedSmallInteger('medicionesRegistradas')->default(0);
            $table->enum('estado', ['activo', 'completo', 'vencido'])->default('activo');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['pacienteId', 'numero']);
            $table->index(['pacienteId', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciclos');
    }
};
