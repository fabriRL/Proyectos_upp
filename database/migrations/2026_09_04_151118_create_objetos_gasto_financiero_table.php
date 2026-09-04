<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objetos_gasto_financiero', function (Blueprint $table) {
            $table->id('id_objeto');
            $table->foreignId('id_partida')
                ->constrained('partidas_presupuestarias', 'id_partida')
                ->cascadeOnDelete();
            $table->integer('numero');
            $table->string('codigo_objeto', 50);
            $table->string('descripcion', 255);

            $table->decimal('monto_ene', 15, 2)->default(0);
            $table->decimal('monto_feb', 15, 2)->default(0);
            $table->decimal('monto_mar', 15, 2)->default(0);
            $table->decimal('monto_abr', 15, 2)->default(0);
            $table->decimal('monto_may', 15, 2)->default(0);
            $table->decimal('monto_jun', 15, 2)->default(0);
            $table->decimal('monto_jul', 15, 2)->default(0);
            $table->decimal('monto_ago', 15, 2)->default(0);
            $table->decimal('monto_sep', 15, 2)->default(0);
            $table->decimal('monto_oct', 15, 2)->default(0);
            $table->decimal('monto_nov', 15, 2)->default(0);
            $table->decimal('monto_dic', 15, 2)->default(0);

            // Monto Ejecutado (Bs): se llena a mano, mes a mes, según lo
            // que reporte contabilidad — no se calcula solo.
            $table->decimal('monto_ejecutado', 15, 2)->default(0);

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
        Schema::dropIfExists('objetos_gasto_financiero');
    }
};