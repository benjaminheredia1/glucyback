<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinicas', function (Blueprint $table) {
            $table->enum('estado', ['activa', 'pago_pendiente', 'suspendida'])->default('activa')->after('telefono');
            $table->string('nit')->nullable()->after('estado');
            $table->string('email')->nullable()->after('nit');
            $table->foreignId('planId')->nullable()->after('email')->constrained('planes');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('clinicas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('planId');
            $table->dropColumn(['estado', 'nit', 'email', 'deleted_at']);
        });
    }
};
