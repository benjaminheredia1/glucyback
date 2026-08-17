<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnosticos', function (Blueprint $table) {
            // El doctorId apuntaba a `users`; la firma la hace un doctor con matricula.
            $table->dropConstrainedForeignId('doctorId');
            $table->foreignId('doctorId')->nullable()->after('pacienteId')->constrained('doctores');
            $table->foreignId('casoId')->nullable()->after('doctorId')->constrained('casos');
            $table->foreignId('cicloId')->nullable()->after('casoId')->constrained('ciclos');
            $table->enum('estado', ['borrador', 'pendiente_firma', 'firmado'])->default('borrador')->after('cicloId');
            $table->timestamp('firmadoEn')->nullable()->after('estado');
            $table->unsignedSmallInteger('version')->default(1)->after('firmadoEn');
            $table->softDeletes();

            $table->index(['pacienteId', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::table('diagnosticos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('casoId');
            $table->dropConstrainedForeignId('cicloId');
            $table->dropConstrainedForeignId('doctorId');
            $table->dropColumn(['estado', 'firmadoEn', 'version', 'deleted_at']);
            $table->foreignId('doctorId')->nullable()->constrained('users');
        });
    }
};
