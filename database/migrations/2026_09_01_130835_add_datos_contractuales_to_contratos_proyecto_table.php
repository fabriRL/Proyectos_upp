<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratos_proyecto', function (Blueprint $table) {
            $table->string('numero_minuta', 255)->nullable()->after('contratista');
            $table->date('fecha_firma_contrato')->nullable()->after('numero_minuta');
            $table->date('fecha_orden_proceder')->nullable()->after('fecha_firma_contrato');
            $table->integer('plazo_dias')->nullable()->after('fecha_orden_proceder');
        });
    }

    public function down(): void
    {
        Schema::table('contratos_proyecto', function (Blueprint $table) {
            $table->dropColumn(['numero_minuta', 'fecha_firma_contrato', 'fecha_orden_proceder', 'plazo_dias']);
        });
    }
};