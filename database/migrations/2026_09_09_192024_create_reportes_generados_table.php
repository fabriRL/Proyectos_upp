<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_generados', function (Blueprint $table) {
            $table->id('id_reporte');
            $table->foreignId('id_usuario')
                ->nullable()->constrained('usuarios', 'id_usuario')->nullOnDelete();
            $table->string('tipo', 10); // 'pdf' | 'excel'
            $table->string('nombre_archivo');
            $table->string('ruta_archivo');
            $table->integer('total_proyectos')->nullable();
            $table->timestamp('creado_en')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_generados');
    }
};