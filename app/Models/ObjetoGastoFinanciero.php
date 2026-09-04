<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ObjetoGastoFinanciero extends Model
{
    use SoftDeletes;

    protected $table = 'objetos_gasto_financiero';
    protected $primaryKey = 'id_objeto';
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';
    const DELETED_AT = 'eliminado_en';

    protected $fillable = [
        'id_partida', 'numero', 'codigo_objeto', 'descripcion',
        'monto_ene', 'monto_feb', 'monto_mar', 'monto_abr', 'monto_may', 'monto_jun',
        'monto_jul', 'monto_ago', 'monto_sep', 'monto_oct', 'monto_nov', 'monto_dic',
        'monto_ejecutado',
        'id_usuario_creador', 'id_usuario_actualizador',
    ];

    protected $casts = [
        'monto_ene' => 'decimal:2', 'monto_feb' => 'decimal:2', 'monto_mar' => 'decimal:2',
        'monto_abr' => 'decimal:2', 'monto_may' => 'decimal:2', 'monto_jun' => 'decimal:2',
        'monto_jul' => 'decimal:2', 'monto_ago' => 'decimal:2', 'monto_sep' => 'decimal:2',
        'monto_oct' => 'decimal:2', 'monto_nov' => 'decimal:2', 'monto_dic' => 'decimal:2',
        'monto_ejecutado' => 'decimal:2',
    ];

    public function partida()
    {
        return $this->belongsTo(PartidaPresupuestaria::class, 'id_partida', 'id_partida');
    }
}