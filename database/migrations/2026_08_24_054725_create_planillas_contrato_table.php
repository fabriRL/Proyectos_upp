<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planillas_contrato', function (Blueprint $table) {
            $table->id('id_planilla');
            $table->foreignId('id_contrato')
                ->constrained('contratos_proyecto', 'id_contrato')
                ->cascadeOnDelete();
            $table->integer('numero');
            $table->date('periodo_desde');
            $table->date('periodo_hasta');
            $table->decimal('monto_certificado', 15, 2);
            $table->integer('dias_atraso')->default(0);
            $table->decimal('avance_fisico', 5, 2)->nullable();
            $table->decimal('amortizacion', 15, 2)->default(0);
            $table->decimal('retencion_gcc', 15, 2)->default(0);
            $table->decimal('multa', 15, 2)->default(0);
            $table->decimal('liquido_pagable', 15, 2)->default(0);
            $table->foreignId('id_usuario_creador')
                ->nullable()
                ->constrained('usuarios', 'id_usuario')
                ->nullOnDelete();
            $table->timestamp('creado_en')->useCurrent();

            $table->unique(['id_contrato', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planillas_contrato');
    }
};