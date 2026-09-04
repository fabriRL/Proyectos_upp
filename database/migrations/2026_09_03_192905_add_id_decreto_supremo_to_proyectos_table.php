<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->foreignId('id_decreto_supremo')
                ->nullable()
                ->after('norma_financiador')
                ->constrained('decretos_supremos', 'id_decreto_supremo')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_decreto_supremo');
        });
    }
};