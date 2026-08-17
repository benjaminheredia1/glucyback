<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuarioId')->unique()->constrained('users');
            $table->foreignId('clinicaId')->constrained('clinicas');
            // Requisito legal para firmar un plan.
            $table->string('matricula')->unique();
            $table->string('especialidad')->nullable();
            $table->foreignId('firmaArchivoId')->nullable()->constrained('archivos');
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctores');
    }
};
