<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('suscripcionId')->constrained('suscripciones');
            $table->decimal('monto', 10, 2);
            $table->string('moneda', 3)->default('USD');
            $table->enum('metodo', ['qr', 'tarjeta', 'transferencia']);
            $table->enum('estado', ['pendiente', 'pagado', 'fallido', 'reembolsado'])->default('pendiente');
            // Identificador del proveedor de cobro.
            $table->string('referencia')->nullable()->unique();
            $table->timestamp('pagadoEn')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['suscripcionId', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
