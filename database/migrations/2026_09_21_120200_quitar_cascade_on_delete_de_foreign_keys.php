<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estas foreign keys tenían cascadeOnDelete(): si alguna vez algo hiciera
 * un DELETE físico real (nunca debería pasar — todo pasa por SoftDeletes),
 * la base de datos borraría en cadena todos los registros hijos sin que la
 * app pudiera evitarlo. Se cambian a restrictOnDelete(): la base de datos
 * ahora BLOQUEA ese DELETE físico en vez de propagarlo, como salvaguarda
 * adicional a nivel de esquema (no solo a nivel de código de la app).
 */
return new class extends Migration
{
    private const FOREIGN_KEYS = [
        ['tabla' => 'decretos_supremos_proyecto', 'columna' => 'id_proyecto', 'tabla_referencia' => 'proyectos', 'columna_referencia' => 'id_proyecto'],
        ['tabla' => 'partidas_presupuestarias', 'columna' => 'id_proyecto', 'tabla_referencia' => 'proyectos', 'columna_referencia' => 'id_proyecto'],
        ['tabla' => 'modificaciones_contractuales', 'columna' => 'id_contrato', 'tabla_referencia' => 'contratos_proyecto', 'columna_referencia' => 'id_contrato'],
        ['tabla' => 'planillas_contrato', 'columna' => 'id_contrato', 'tabla_referencia' => 'contratos_proyecto', 'columna_referencia' => 'id_contrato'],
        ['tabla' => 'objetos_gasto_financiero', 'columna' => 'id_partida', 'tabla_referencia' => 'partidas_presupuestarias', 'columna_referencia' => 'id_partida'],
        ['tabla' => 'contratos_proyecto', 'columna' => 'id_proyecto', 'tabla_referencia' => 'proyectos', 'columna_referencia' => 'id_proyecto'],
    ];

    public function up(): void
    {
        foreach (self::FOREIGN_KEYS as $fk) {
            Schema::table($fk['tabla'], function (Blueprint $table) use ($fk) {
                $table->dropForeign([$fk['columna']]);
                $table->foreign($fk['columna'])
                    ->references($fk['columna_referencia'])
                    ->on($fk['tabla_referencia'])
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::FOREIGN_KEYS as $fk) {
            Schema::table($fk['tabla'], function (Blueprint $table) use ($fk) {
                $table->dropForeign([$fk['columna']]);
                $table->foreign($fk['columna'])
                    ->references($fk['columna_referencia'])
                    ->on($fk['tabla_referencia'])
                    ->cascadeOnDelete();
            });
        }
    }
};
