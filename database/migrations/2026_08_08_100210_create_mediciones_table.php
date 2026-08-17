<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mediciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pacienteId')->constrained('pacientes');
            $table->foreignId('cicloId')->nullable()->constrained('ciclos');
            $table->float('valor');
            $table->string('unidad', 10)->default('mg/dL');
            // El seguimiento compara siempre contra el mismo tipo de medicion.
            $table->enum('momento', ['ayunas', 'preprandial', 'postprandial', 'nocturna']);
            $table->enum('fuente', ['manual', 'dispositivo'])->default('manual');
            $table->timestamp('medidoEn');
            $table->string('nota')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pacienteId', 'medidoEn']);
            $table->index(['cicloId', 'momento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mediciones');
    }
};
