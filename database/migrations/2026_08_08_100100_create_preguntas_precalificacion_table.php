<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preguntas_precalificacion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo');
            $table->text('texto');
            // Respuesta que dispara la alarma clinica.
            $table->enum('respuestaAlarma', ['si', 'no']);
            $table->boolean('esUrgente')->default(false);
            $table->string('motivo');
            $table->unsignedSmallInteger('orden')->default(0);
            // Si cambia el criterio clinico, las precalificaciones viejas siguen
            // siendo interpretables contra su propia version.
            $table->unsignedSmallInteger('version')->default(1);
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['codigo', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preguntas_precalificacion');
    }
};
