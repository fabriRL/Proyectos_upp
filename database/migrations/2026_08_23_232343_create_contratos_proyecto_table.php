<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos_proyecto', function (Blueprint $table) {
            $table->id('id_contrato');
            $table->foreignId('id_proyecto')->constrained('proyectos', 'id_proyecto')->cascadeOnDelete();
            $table->foreignId('id_componente')->nullable()->constrained('componentes_proyecto', 'id_componente')->nullOnDelete();
            $table->integer('numero');
            $table->string('tipo_contrato', 150);
            $table->string('contratista', 255);
            $table->decimal('monto_vigente', 15, 2)->default(0);
            $table->decimal('anticipo', 15, 2)->default(0);
            $table->decimal('amortizacion_acumulada', 15, 2)->default(0);
            $table->decimal('monto_ejecutado_acumulado', 15, 2)->default(0);
            $table->decimal('liquido_pagable_acumulado', 15, 2)->default(0);
            $table->decimal('multas', 15, 2)->default(0);
            $table->decimal('retencion_gcc', 15, 2)->default(0);
            $table->decimal('total_descuentos', 15, 2)->default(0);
            $table->decimal('saldo_por_pagar', 15, 2)->default(0);
            $table->string('estado_contractual', 50);
            $table->date('fecha_conclusion_prevista')->nullable();
            $table->date('fecha_entrega_provisional')->nullable();
            $table->date('fecha_entrega_definitiva')->nullable();
            $table->decimal('avance_fisico', 5, 2)->default(0);
            $table->decimal('avance_financiero', 5, 2)->default(0);
            $table->string('estado_fisico', 50)->nullable();
            $table->foreignId('id_usuario_creador')->nullable()->constrained('usuarios', 'id_usuario')->nullOnDelete();
            $table->foreignId('id_usuario_actualizador')->nullable()->constrained('usuarios', 'id_usuario')->nullOnDelete();
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent();
            $table->softDeletes('eliminado_en');
            $table->unique(['id_proyecto', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos_proyecto');
    }
};