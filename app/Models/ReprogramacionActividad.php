<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReprogramacionActividad extends Model
{
    protected $table = 'reprogramaciones_actividades';
    protected $primaryKey = 'id_reprogramacion';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = null; // esta tabla no tiene actualizado_en

    protected $fillable = [
        'id_actividad',
        'fecha_inicio_anterior',
        'fecha_fin_anterior',
        'fecha_inicio_nueva',
        'fecha_fin_nueva',
        'motivo',
        'estado_aprobacion',
        'id_usuario_aprobador',
        'aprobado_en',
        'id_usuario_creador',
    ];

    protected $casts = [
        'fecha_inicio_anterior' => 'date',
        'fecha_fin_anterior' => 'date',
        'fecha_inicio_nueva' => 'date',
        'fecha_fin_nueva' => 'date',
        'aprobado_en' => 'datetime',
    ];

    public function actividad()
    {
        return $this->belongsTo(Actividad::class, 'id_actividad', 'id_actividad');
    }
}