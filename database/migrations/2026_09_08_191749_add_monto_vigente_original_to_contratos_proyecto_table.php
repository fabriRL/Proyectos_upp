<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratos_proyecto', function (Blueprint $table) {
            $table->decimal('monto_vigente_original', 15, 2)->nullable()->after('monto_vigente');
        });

        // Backfill: hasta hoy, monto_vigente NUNCA incluyó modificaciones
        // (ese es justo el bug que estamos corrigiendo) — así que el valor
        // actual de monto_vigente ES el original para todos los contratos
        // existentes. El siguiente paso (script de tinker) recalcula los
        // que ya tengan modificaciones registradas.
        DB::statement('UPDATE contratos_proyecto SET monto_vigente_original = monto_vigente');
    }

    public function down(): void
    {
        Schema::table('contratos_proyecto', function (Blueprint $table) {
            $table->dropColumn('monto_vigente_original');
        });
    }
};