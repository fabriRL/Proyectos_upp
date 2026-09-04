<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decretos_supremos_proyecto', function (Blueprint $table) {
            $table->id('id_decreto');
            $table->foreignId('id_proyecto')
                ->constrained('proyectos', 'id_proyecto')
                ->cascadeOnDelete();
            $table->integer('numero');
            $table->string('numero_decreto', 255);
            $table->decimal('monto_inicial', 15, 2)->default(0);
            $table->decimal('incremento', 15, 2)->default(0);
            $table->decimal('monto_puesta_marcha_insumos', 15, 2)->default(0);
            $table->decimal('monto_auditoria_interna', 15, 2)->default(0);
            $table->foreignId('id_usuario_creador')
                ->nullable()
                ->constrained('usuarios', 'id_usuario')
                ->nullOnDelete();
            $table->foreignId('id_usuario_actualizador')
                ->nullable()
                ->constrained('usuarios', 'id_usuario')
                ->nullOnDelete();
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent();
            $table->timestamp('eliminado_en')->nullable();

            $table->unique(['id_proyecto', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decretos_supremos_proyecto');
    }
};