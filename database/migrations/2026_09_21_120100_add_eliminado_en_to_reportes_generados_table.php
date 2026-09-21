<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reportes_generados', function (Blueprint $table) {
            $table->timestamp('eliminado_en')->nullable()->after('total_proyectos');
        });
    }

    public function down(): void
    {
        Schema::table('reportes_generados', function (Blueprint $table) {
            $table->dropColumn('eliminado_en');
        });
    }
};
