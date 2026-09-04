<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialDerechoPropietario extends Model
{
    protected $table = 'historial_derecho_propietario';
    protected $primaryKey = 'id_historial_derecho_propietario';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'id_proyecto',
        'estado',
        'descripcion',
        'fecha_evento',
        'observaciones',
        'id_usuario_creador',
        'id_usuario_actualizador',
    ];

    protected $casts = [
        'fecha_evento' => 'date',
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'id_proyecto', 'id_proyecto');
    }
}