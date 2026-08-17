<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuarioId')->constrained('users')->cascadeOnDelete();
            $table->string('tipo');
            $table->string('titulo');
            $table->text('cuerpo')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('enviadaEn')->nullable();
            $table->timestamp('leidaEn')->nullable();
            $table->timestamps();

            $table->index(['usuarioId', 'leidaEn']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
