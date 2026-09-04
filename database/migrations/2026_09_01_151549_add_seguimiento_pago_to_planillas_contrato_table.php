<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planillas_contrato', function (Blueprint $table) {
            $table->decimal('importe_pagado_sigep', 15, 2)->default(0)->after('avance_fisico');
            $table->string('numero_c31', 100)->nullable()->after('importe_pagado_sigep');
            $table->decimal('monto_c31', 15, 2)->default(0)->after('numero_c31');
            $table->date('fecha_aprobacion_fiscal')->nullable()->after('monto_c31');
            $table->date('fecha_elaboracion_planilla')->nullable()->after('fecha_aprobacion_fiscal');
            $table->date('fecha_desembolso')->nullable()->after('fecha_elaboracion_planilla');
        });
    }

    public function down(): void
    {
        Schema::table('planillas_contrato', function (Blueprint $table) {
            $table->dropColumn([
                'importe_pagado_sigep',
                'numero_c31',
                'monto_c31',
                'fecha_aprobacion_fiscal',
                'fecha_elaboracion_planilla',
                'fecha_desembolso',
            ]);
        });
    }
};