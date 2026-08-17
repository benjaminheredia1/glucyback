<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuarioId')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entidad');
            $table->unsignedBigInteger('entidadId')->nullable();
            $table->string('accion');
            $table->json('antes')->nullable();
            $table->json('despues')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['entidad', 'entidadId']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
