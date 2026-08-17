<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            // `apto` booleano no puede expresar el semaforo de 3 estados ni caducar:
            // lo reemplaza la tabla `elegibilidades`.
            $table->dropColumn('apto');
            $table->foreignId('clinicaId')->nullable()->after('usuarioId')->constrained('clinicas');
            $table->enum('sexo', ['femenino', 'masculino', 'otro'])->nullable()->after('fechaNacimiento');
            $table->decimal('pesoKg', 5, 2)->nullable()->after('tipoDiabetes');
            $table->unsignedSmallInteger('tallaCm')->nullable()->after('pesoKg');
            $table->text('comorbilidades')->nullable()->after('alergias');
            $table->boolean('tabaquismo')->default(false)->after('comorbilidades');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('clinicaId');
            $table->dropColumn(['sexo', 'pesoKg', 'tallaCm', 'comorbilidades', 'tabaquismo', 'deleted_at']);
            $table->boolean('apto')->default(true);
        });
    }
};
