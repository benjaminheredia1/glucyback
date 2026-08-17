<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `estudios` duplicaba `tipo_estudios` salvo por `esObligatorio`, que ya vive
     * en el catalogo. Se traslada el dato antes de borrar la tabla.
     */
    public function up(): void
    {
        if (Schema::hasTable('estudios')) {
            $obligatorios = DB::table('estudios')
                ->where('esObligatorio', true)
                ->pluck('tipoEstudioId')
                ->unique()
                ->all();

            if ($obligatorios !== []) {
                DB::table('tipo_estudios')
                    ->whereIn('id', $obligatorios)
                    ->update(['esObligatorio' => true]);
            }
        }

        Schema::dropIfExists('estudios');
    }

    public function down(): void
    {
        Schema::create('estudios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipoEstudioId')->constrained('tipo_estudios');
            $table->boolean('esObligatorio')->default(false);
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });
    }
};
