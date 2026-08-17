<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->enum('ambito', ['paciente', 'clinica']);
            $table->decimal('precio', 10, 2);
            $table->string('moneda', 3)->default('USD');
            $table->enum('periodicidad', ['mensual', 'anual']);
            $table->unsignedSmallInteger('consultasIncluidas')->default(0);
            $table->unsignedSmallInteger('diasPrueba')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planes');
    }
};
