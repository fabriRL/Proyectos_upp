<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planillas_contrato', function (Blueprint $table) {
            $table->timestamp('actualizado_en')->nullable()->after('creado_en');
            $table->timestamp('eliminado_en')->nullable()->after('actualizado_en');
        });
    }

    public function down(): void
    {
        Schema::table('planillas_contrato', function (Blueprint $table) {
            $table->dropColumn(['actualizado_en', 'eliminado_en']);
        });
    }
};