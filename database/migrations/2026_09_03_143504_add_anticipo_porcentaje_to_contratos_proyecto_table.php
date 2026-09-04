<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratos_proyecto', function (Blueprint $table) {
            $table->decimal('anticipo_porcentaje', 5, 2)->nullable()->after('anticipo');
        });
    }

    public function down(): void
    {
        Schema::table('contratos_proyecto', function (Blueprint $table) {
            $table->dropColumn('anticipo_porcentaje');
        });
    }
};