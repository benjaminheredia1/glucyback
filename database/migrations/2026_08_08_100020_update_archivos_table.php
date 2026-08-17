<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('archivo', 'archivos');

        Schema::table('archivos', function (Blueprint $table) {
            $table->string('disk')->default('local')->after('url');
            $table->string('mime')->nullable()->after('disk');
            $table->unsignedBigInteger('sizeBytes')->nullable()->after('mime');
            $table->string('hashSha256', 64)->nullable()->after('sizeBytes');
            $table->boolean('esPrivado')->default(true)->after('hashSha256');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('archivos', function (Blueprint $table) {
            $table->dropColumn(['disk', 'mime', 'sizeBytes', 'hashSha256', 'esPrivado', 'deleted_at']);
        });

        Schema::rename('archivos', 'archivo');
    }
};
