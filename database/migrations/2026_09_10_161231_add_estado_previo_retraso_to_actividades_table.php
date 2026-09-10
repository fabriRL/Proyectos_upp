<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            // Guarda el Estado que tenía la actividad ANTES de que el
            // sistema la forzara a "Retrasada" por un problema abierto —
            // así se puede devolver exactamente a ese estado cuando se
            // resuelvan todos los problemas que la afectan.
            $table->string('estado_previo_retraso', 100)->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->dropColumn('estado_previo_retraso');
        });
    }
};