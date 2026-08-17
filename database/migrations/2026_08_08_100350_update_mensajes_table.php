<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mensajes', function (Blueprint $table) {
            // doctorId apuntaba a `users`; se reemplaza por un autor generico.
            $table->dropConstrainedForeignId('doctorId');
            $table->foreignId('autorId')->nullable()->after('chatId')->constrained('users');
            $table->foreignId('archivoId')->nullable()->after('autorId')->constrained('archivos');
            $table->timestamp('leidoEn')->nullable()->after('esIA');
            $table->softDeletes();

            $table->index(['chatId', 'created_at']);
        });

        Schema::table('mensajes', function (Blueprint $table) {
            // NOT NULL bloqueaba el chat interno entre doctor y soporte.
            $table->foreignId('pacienteId')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('mensajes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('autorId');
            $table->dropConstrainedForeignId('archivoId');
            $table->dropColumn(['leidoEn', 'deleted_at']);
            $table->foreignId('doctorId')->nullable()->constrained('users');
        });
    }
};
