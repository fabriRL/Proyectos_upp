<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Actividad extends Model
{
    use SoftDeletes;

    protected $table = 'actividades';

    protected $primaryKey = 'id_actividad';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';
    const DELETED_AT = 'eliminado_en';

    protected $fillable = [
        'id_proyecto',
        'id_componente',
        'id_actividad_predecesora',

        'numero',
        'actividad',

        'fecha_inicio',
        'fecha_fin',
        'duracion_dias',

        'estado',

        'porcentaje_cumplimiento_programado',
        'porcentaje_cumplimiento_real',

        'id_usuario_creador',
        'id_usuario_actualizador',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',

        'duracion_dias' => 'integer',

        'porcentaje_cumplimiento_programado' => 'decimal:2',
        'porcentaje_cumplimiento_real' => 'decimal:2',

        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
        'eliminado_en' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    /**
     * Proyecto al que pertenece la actividad.
     */
    public function proyecto()
    {
        return $this->belongsTo(
            Proyecto::class,
            'id_proyecto',
            'id_proyecto'
        );
    }

    /**
     * Componente al que pertenece la actividad.
     */
    public function componente()
    {
        return $this->belongsTo(
            ComponenteProyecto::class,
            'id_componente',
            'id_componente'
        );
    }

    /**
     * Actividad de la cual depende esta actividad.
     *
     * Ejemplo:
     * Actividad 2 depende de Actividad 1.
     */
    public function predecesora()
    {
        return $this->belongsTo(
            Actividad::class,
            'id_actividad_predecesora',
            'id_actividad'
        );
    }

    /**
     * Actividades que dependen de esta actividad.
     *
     * Ejemplo:
     * Actividad 1 tiene como sucesora a Actividad 2.
     */
    public function sucesoras()
    {
        return $this->hasMany(
            Actividad::class,
            'id_actividad_predecesora',
            'id_actividad'
        );
    }

    /**
     * Usuario que creó la actividad.
     */
    public function usuarioCreador()
    {
        return $this->belongsTo(
            Usuario::class,
            'id_usuario_creador',
            'id_usuario'
        );
    }

    /**
     * Usuario que realizó la última actualización.
     */
    public function usuarioActualizador()
    {
        return $this->belongsTo(
            Usuario::class,
            'id_usuario_actualizador',
            'id_usuario'
        );
    }

    /**
     * Reprogramaciones realizadas sobre esta actividad.
     */
    public function reprogramaciones()
    {
        return $this->hasMany(
            ReprogramacionActividad::class,
            'id_actividad',
            'id_actividad'
        );
    }
}