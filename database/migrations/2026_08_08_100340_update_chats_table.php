<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('chat', 'chats');

        Schema::table('chats', function (Blueprint $table) {
            // La tabla solo tenia `nombre`: no se sabia de quien era la conversacion.
            $table->foreignId('pacienteId')->nullable()->after('nombre')->constrained('pacientes');
            $table->enum('tipo', ['soporte', 'clinico'])->default('soporte')->after('pacienteId');
            $table->enum('estado', ['abierto', 'cerrado'])->default('abierto')->after('tipo');
            $table->timestamp('ultimoMensajeEn')->nullable()->after('estado');
            $table->softDeletes();

            $table->index(['pacienteId', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pacienteId');
            $table->dropColumn(['tipo', 'estado', 'ultimoMensajeEn', 'deleted_at']);
        });

        Schema::rename('chats', 'chat');
    }
};
