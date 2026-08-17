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
        Schema::create('licencia_usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuarioId')->constrained('users');
            $table->foreignId('licenciaId')->constrained('licencias');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licencia_usuarios');
    }
};
