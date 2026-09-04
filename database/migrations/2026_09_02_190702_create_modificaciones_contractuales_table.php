<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modificaciones_contractuales', function (Blueprint $table) {
            $table->id('id_modificacion');
            $table->foreignId('id_contrato')
                ->constrained('contratos_proyecto', 'id_contrato')
                ->cascadeOnDelete();
            $table->integer('numero');
            $table->string('tipo_modificacion', 150);
            $table->string('numero_documento_modificatorio', 150)->nullable();
            $table->string('cite_documento_aprobacion', 150)->nullable();
            $table->integer('plazo_modificado_dias')->nullable();
            $table->decimal('monto_modificacion', 15, 2)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('estado_registro_sicoes', 50)->nullable();
            $table->date('fecha_informe_aprobacion')->nullable();
            $table->date('fecha_firma_documento')->nullable();
            $table->string('estado_documento', 50)->nullable();
            $table->string('archivo_pdf_path')->nullable();
            $table->string('archivo_pdf_nombre_original')->nullable();
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

            $table->unique(['id_contrato', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modificaciones_contractuales');
    }
};