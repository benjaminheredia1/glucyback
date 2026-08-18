<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El formulario "Solicitar acceso" corre en la landing, antes de que
        // exista cuenta: no puede escribir en `doctores` ni en `users`. Queda
        // aqui como bandeja de entrada hasta que un admin verifique matricula.
        Schema::create('solicitudes_acceso_doctor', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('matricula');
            $table->string('especialidad');
            $table->string('correo');
            $table->string('institucion')->nullable();
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
            // Origen de la peticion: la ruta es publica, sirve para rastrear abuso.
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index('correo');
            $table->index('estado');
            $table->index('matricula');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_acceso_doctor');
    }
};
