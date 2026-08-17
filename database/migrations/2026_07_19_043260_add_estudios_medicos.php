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
        Schema::create('estudios_medicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipoEstudioId')->constrained('tipo_estudios');
            $table->string('descripcion')->nullable();
            $table->foreignId('pacienteId')->constrained('pacientes');
            $table->foreignId('archivoId')->nullable()->constrained('archivo');
            $table->date('fecha');
            $table->float('valor')->nullable();
            $table->string('unidad')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudios_medicos');
    }
};
