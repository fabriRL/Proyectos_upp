<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanillaContrato extends Model
{
    use SoftDeletes;

    protected $table = 'planillas_contrato';
    protected $primaryKey = 'id_planilla';
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';
    const DELETED_AT = 'eliminado_en';

    protected $fillable = [
        'id_contrato', 'numero', 'periodo_desde', 'periodo_hasta',
        'monto_certificado', 'dias_atraso', 'avance_fisico',
        'amortizacion', 'retencion_gcc', 'multa', 'liquido_pagable',
        'importe_pagado_sigep', 'numero_c31', 'monto_c31',
        'fecha_aprobacion_fiscal', 'fecha_elaboracion_planilla', 'fecha_desembolso',
        'id_usuario_creador', 'id_usuario_actualizador',
    ];

    protected $casts = [
        'periodo_desde' => 'date:Y-m-d',
        'periodo_hasta' => 'date:Y-m-d',
        'monto_certificado' => 'decimal:2',
        'avance_fisico' => 'decimal:2',
        'amortizacion' => 'decimal:2',
        'retencion_gcc' => 'decimal:2',
        'multa' => 'decimal:2',
        'liquido_pagable' => 'decimal:2',
        'importe_pagado_sigep' => 'decimal:2',
        'monto_c31' => 'decimal:2',
        'fecha_aprobacion_fiscal' => 'date:Y-m-d',
        'fecha_elaboracion_planilla' => 'date:Y-m-d',
        'fecha_desembolso' => 'date:Y-m-d',
    ];

    public function contrato()
    {
        return $this->belongsTo(ContratoProyecto::class, 'id_contrato', 'id_contrato');
    }
}