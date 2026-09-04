<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratos_proyecto', function (Blueprint $table) {
            $table->string('archivo_orden_proceder_path')->nullable()->after('fecha_orden_proceder');
            $table->string('archivo_orden_proceder_nombre_original')->nullable()->after('archivo_orden_proceder_path');
        });
    }

    public function down(): void
    {
        Schema::table('contratos_proyecto', function (Blueprint $table) {
            $table->dropColumn(['archivo_orden_proceder_path', 'archivo_orden_proceder_nombre_original']);
        });
    }
};