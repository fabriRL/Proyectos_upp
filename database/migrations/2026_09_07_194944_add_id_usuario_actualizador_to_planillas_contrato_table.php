<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planillas_contrato', function (Blueprint $table) {
            $table->foreignId('id_usuario_actualizador')
                ->nullable()
                ->after('id_usuario_creador')
                ->constrained('usuarios', 'id_usuario')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('planillas_contrato', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_usuario_actualizador');
        });
    }
};