<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartidaPresupuestaria extends Model
{
    use SoftDeletes;

    protected $table = 'partidas_presupuestarias';
    protected $primaryKey = 'id_partida';
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';
    const DELETED_AT = 'eliminado_en';

    protected $fillable = [
        'id_proyecto', 'presupuesto_aprobado',
        'id_usuario_creador', 'id_usuario_actualizador',
    ];

    protected $casts = [
        'presupuesto_aprobado' => 'decimal:2',
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'id_proyecto', 'id_proyecto');
    }

    public function objetos()
    {
        return $this->hasMany(ObjetoGastoFinanciero::class, 'id_partida', 'id_partida');
    }
}