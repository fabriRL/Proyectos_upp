<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('problemas', function (Blueprint $table) {
            $table->string('archivo_resolucion_path')->nullable()->after('fecha_cierre');
            $table->string('archivo_resolucion_nombre_original')->nullable()->after('archivo_resolucion_path');
        });
    }

    public function down(): void
    {
        Schema::table('problemas', function (Blueprint $table) {
            $table->dropColumn(['archivo_resolucion_path', 'archivo_resolucion_nombre_original']);
        });
    }
};