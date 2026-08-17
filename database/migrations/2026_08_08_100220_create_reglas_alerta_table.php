<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reglas_alerta', function (Blueprint $table) {
            $table->id();
            // Nullable: regla global cuando la clinica no define la suya.
            $table->foreignId('clinicaId')->nullable()->constrained('clinicas');
            $table->enum('momento', ['ayunas', 'preprandial', 'postprandial', 'nocturna', 'cualquiera'])->default('cualquiera');
            $table->float('minimo')->nullable();
            $table->float('maximo')->nullable();
            $table->enum('severidad', ['critica', 'alta', 'media']);
            $table->string('mensaje');
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->index(['clinicaId', 'activa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglas_alerta');
    }
};
