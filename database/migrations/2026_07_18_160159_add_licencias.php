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
        Schema::create('licencias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->notNullable()->unique();
            $table->enum('estado', ['activa', 'inactiva'])->notNullable()->default('inactiva');
            $table->foreignId('clinicaId')->constrained('clinicas');
            $table->string('nombre')->notNullable();
            $table->integer('cantidad');
            $table->date('fecha_expiracion')->nullable(false);
            $table->float('descuento')->nullable(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licencias');
    }
};
