<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->date('fechaNacimiento');
            $table->foreignId('usuarioId')->constrained('users');
            $table->boolean('apto')->default(true);
            $table->string('tipoDiabetes');
            $table->date('diagnosticadoEn')->nullable();
            $table->string('alergias')->nullable();
            $table->string('contactoEmergencia')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
