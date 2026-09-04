<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partidas_presupuestarias', function (Blueprint $table) {
            $table->id('id_partida');
            $table->foreignId('id_proyecto')
                ->constrained('proyectos', 'id_proyecto')
                ->cascadeOnDelete();
            // Presupuesto Aprobado (SIGEP) — se llena UNA sola vez por
            // grupo/partida, y se comparte entre todos los "Objetos de
            // Gasto" que pertenezcan a ella (igual que en tu Excel, donde
            // la celda aparece combinada abarcando varias filas).
            $table->decimal('presupuesto_aprobado', 15, 2)->default(0);
            $table->foreignId('id_usuario_creador')
                ->nullable()->constrained('usuarios', 'id_usuario')->nullOnDelete();
            $table->foreignId('id_usuario_actualizador')
                ->nullable()->constrained('usuarios', 'id_usuario')->nullOnDelete();
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent();
            $table->timestamp('eliminado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partidas_presupuestarias');
    }
};