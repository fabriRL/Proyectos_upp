<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UbicacionProyecto extends Model
{
    use SoftDeletes;

    protected $table = 'ubicaciones_proyecto';
    protected $primaryKey = 'id_ubicacion_proyecto';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';
    const DELETED_AT = 'eliminado_en';

    protected $fillable = [
        'id_proyecto',
        'departamento',
        'provincia',
        'municipio',
        'comunidad_localidad',
        'coordenada_norte',
        'coordenada_este',
        'zona_utm',
        'id_usuario_creador',
        'id_usuario_actualizador',
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'id_proyecto', 'id_proyecto');
    }
}