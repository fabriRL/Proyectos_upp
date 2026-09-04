<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContratoProyecto extends Model
{
    use SoftDeletes;

    protected $table = 'contratos_proyecto';
    protected $primaryKey = 'id_contrato';

    // --- Auditoría automática (ya la maneja Eloquent solo, no van en $fillable) ---
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';
    const DELETED_AT = 'eliminado_en'; // Borrado lógico — la fila NUNCA se elimina de la BD.

    protected $fillable = [
        'id_proyecto', 'id_componente', 'numero', 'tipo_contrato', 'contratista',
        'numero_minuta', 'fecha_firma_contrato', 'fecha_orden_proceder', 'plazo_dias',
        'archivo_orden_proceder_path', 'archivo_orden_proceder_nombre_original',
        'monto_vigente', 'anticipo', 'anticipo_porcentaje', 'amortizacion_acumulada', 'monto_ejecutado_acumulado',
        'liquido_pagable_acumulado', 'multas', 'retencion_gcc', 'total_descuentos',
        'saldo_por_pagar', 'estado_contractual', 'fecha_conclusion_prevista',
        'fecha_entrega_provisional', 'fecha_entrega_definitiva', 'avance_fisico',
        'avance_financiero', 'estado_fisico', 'activo',
        'id_usuario_creador', 'id_usuario_actualizador',
    ];

    protected $casts = [
        'monto_vigente' => 'decimal:2',
        'anticipo' => 'decimal:2',
        'anticipo_porcentaje' => 'decimal:2',
        'saldo_por_pagar' => 'decimal:2',
        'avance_fisico' => 'decimal:2',
        'avance_financiero' => 'decimal:2',
        'fecha_conclusion_prevista' => 'date:Y-m-d',
        'fecha_entrega_provisional' => 'date:Y-m-d',
        'fecha_entrega_definitiva' => 'date:Y-m-d',
        'fecha_firma_contrato' => 'date:Y-m-d',
        'fecha_orden_proceder' => 'date:Y-m-d',
        'activo' => 'boolean',
    ];

    protected $appends = ['archivo_orden_proceder_url'];

    public function getArchivoOrdenProcederUrlAttribute()
    {
        return $this->archivo_orden_proceder_path
            ? asset('storage/' . $this->archivo_orden_proceder_path)
            : null;
    }

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'id_proyecto', 'id_proyecto');
    }

    public function planillas()
    {
        return $this->hasMany(PlanillaContrato::class, 'id_contrato', 'id_contrato');
    }

    public function modificaciones()
    {
        return $this->hasMany(ModificacionContractual::class, 'id_contrato', 'id_contrato');
    }
}