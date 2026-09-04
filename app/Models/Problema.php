<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Problema extends Model
{
    use SoftDeletes;

    protected $table = 'problemas';
    protected $primaryKey = 'id_problema';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';
    const DELETED_AT = 'eliminado_en';

    protected $fillable = [
        'id_proyecto',
        'fecha_registro',
        'problema_identificado',
        'impacto',
        'solucion_propuesta',
        'responsable',
        'estado',
        'fecha_cierre',
        'archivo_resolucion_path',
        'archivo_resolucion_nombre_original',
        'id_usuario_creador',
        'id_usuario_actualizador',
    ];

    protected $casts = [
        'fecha_registro' => 'date:Y-m-d',
        'fecha_cierre' => 'date:Y-m-d',
    ];

    protected $appends = ['archivo_resolucion_url'];

    public function getArchivoResolucionUrlAttribute()
    {
        return $this->archivo_resolucion_path
            ? asset('storage/' . $this->archivo_resolucion_path)
            : null;
    }

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'id_proyecto', 'id_proyecto');
    }
}