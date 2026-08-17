<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articulos_ayuda', function (Blueprint $table) {
            $table->id();
            $table->string('categoria');
            $table->string('titulo');
            $table->text('cuerpo');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('publicado')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['categoria', 'publicado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articulos_ayuda');
    }
};
