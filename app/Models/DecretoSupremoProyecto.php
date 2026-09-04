<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DecretoSupremoProyecto extends Model
{
    use SoftDeletes;

    protected $table = 'decretos_supremos_proyecto';
    protected $primaryKey = 'id_decreto';
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';
    const DELETED_AT = 'eliminado_en';

    protected $fillable = [
        'id_proyecto', 'numero', 'numero_decreto',
        'monto_inicial', 'incremento',
        'monto_puesta_marcha_insumos', 'monto_auditoria_interna',
        'id_usuario_creador', 'id_usuario_actualizador',
    ];

    protected $casts = [
        'monto_inicial' => 'decimal:2',
        'incremento' => 'decimal:2',
        'monto_puesta_marcha_insumos' => 'decimal:2',
        'monto_auditoria_interna' => 'decimal:2',
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'id_proyecto', 'id_proyecto');
    }
}