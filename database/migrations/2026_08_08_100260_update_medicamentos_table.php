<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicamentos', function (Blueprint $table) {
            // "Metformina 850 mg" no cabe solo en `nombre`.
            $table->string('concentracion')->nullable()->after('nombre');
            $table->string('presentacion')->nullable()->after('concentracion');
            $table->string('viaAdministracion')->nullable()->after('presentacion');
            $table->boolean('activo')->default(true)->after('descripcion');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('medicamentos', function (Blueprint $table) {
            $table->dropColumn(['concentracion', 'presentacion', 'viaAdministracion', 'activo', 'deleted_at']);
        });
    }
};
