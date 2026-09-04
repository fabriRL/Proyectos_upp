<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modificaciones_contractuales', function (Blueprint $table) {
            $table->date('fecha_anterior')->nullable()->after('cite_documento_aprobacion');
        });
    }

    public function down(): void
    {
        Schema::table('modificaciones_contractuales', function (Blueprint $table) {
            $table->dropColumn('fecha_anterior');
        });
    }
};