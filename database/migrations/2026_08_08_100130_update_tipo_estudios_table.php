<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipo_estudios', function (Blueprint $table) {
            $table->string('unidad')->nullable()->after('descripcion');
            // Sin rangos de referencia no se puede evaluar un valor automaticamente.
            $table->float('rangoMin')->nullable()->after('unidad');
            $table->float('rangoMax')->nullable()->after('rangoMin');
            // Migrado desde la tabla `estudios`, que se elimina.
            $table->boolean('esObligatorio')->default(false)->after('rangoMax');
            $table->unsignedSmallInteger('orden')->default(0)->after('esObligatorio');
            $table->string('codigoLoinc')->nullable()->after('orden');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('tipo_estudios', function (Blueprint $table) {
            $table->dropColumn(['unidad', 'rangoMin', 'rangoMax', 'esObligatorio', 'orden', 'codigoLoinc', 'deleted_at']);
        });
    }
};
