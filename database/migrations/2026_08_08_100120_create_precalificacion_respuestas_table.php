<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('precalificacion_respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('precalificacionId')->constrained('precalificaciones')->cascadeOnDelete();
            $table->foreignId('preguntaId')->constrained('preguntas_precalificacion');
            $table->enum('respuesta', ['si', 'no']);
            $table->timestamps();

            $table->unique(['precalificacionId', 'preguntaId']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('precalificacion_respuestas');
    }
};
