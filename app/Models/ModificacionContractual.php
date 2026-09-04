<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModificacionContractual extends Model
{
    use SoftDeletes;

    protected $table = 'modificaciones_contractuales';
    protected $primaryKey = 'id_modificacion';
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';
    const DELETED_AT = 'eliminado_en';

    protected $fillable = [
        'id_contrato', 'numero', 'tipo_modificacion',
        'numero_documento_modificatorio', 'cite_documento_aprobacion',
        'fecha_anterior', 'nueva_fecha_conclusion', 'plazo_modificado_dias',
        'monto_modificacion', 'descripcion',
        'estado_registro_sicoes', 'fecha_informe_aprobacion', 'fecha_firma_documento',
        'estado_documento', 'archivo_pdf_path', 'archivo_pdf_nombre_original',
        'id_usuario_creador', 'id_usuario_actualizador',
    ];

    protected $casts = [
        'monto_modificacion' => 'decimal:2',
        'fecha_anterior' => 'date:Y-m-d',
        'nueva_fecha_conclusion' => 'date:Y-m-d',
        'fecha_informe_aprobacion' => 'date:Y-m-d',
        'fecha_firma_documento' => 'date:Y-m-d',
    ];

    public function contrato()
    {
        return $this->belongsTo(ContratoProyecto::class, 'id_contrato', 'id_contrato');
    }
}