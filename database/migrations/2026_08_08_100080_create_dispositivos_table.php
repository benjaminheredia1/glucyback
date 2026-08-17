<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispositivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuarioId')->constrained('users')->cascadeOnDelete();
            $table->string('pushToken')->unique();
            $table->enum('plataforma', ['ios', 'android', 'web']);
            $table->string('modelo')->nullable();
            $table->timestamp('ultimoUsoEn')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispositivos');
    }
};
