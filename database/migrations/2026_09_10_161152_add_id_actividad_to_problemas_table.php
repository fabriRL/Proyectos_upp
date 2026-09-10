<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('problemas', function (Blueprint $table) {
            $table->foreignId('id_actividad')
                ->nullable()
                ->after('id_proyecto')
                ->constrained('actividades', 'id_actividad')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('problemas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_actividad');
        });
    }
};
