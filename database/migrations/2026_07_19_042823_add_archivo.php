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
        Schema::create('archivo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->notNullable();
            $table->string('descripcion')->nullable();
            $table->string('url')->notNullable();
            $table->foreignId('usuarioId')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archivo');
    }
};
