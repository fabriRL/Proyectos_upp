<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decretos_supremos', function (Blueprint $table) {
            $table->id('id_decreto_supremo');
            $table->string('numero_decreto', 255)->unique();
            $table->decimal('monto', 15, 2)->default(0);
            $table->text('descripcion')->nullable();
            $table->date('fecha_decreto')->nullable();
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
        Schema::dropIfExists('decretos_supremos');
    }
};