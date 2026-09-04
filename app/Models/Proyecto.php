<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proyecto extends Model
{
    use HasFactory, SoftDeletes;

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';    
    const DELETED_AT = 'eliminado_en';

    protected $table = 'proyectos';
    protected $primaryKey = 'id_proyecto';

    protected $fillable = [
        'codigo',
        'numero_sisin_web',
        'nombre',
        'fiscal_general',
        'fuente_financiamiento',
        'norma_financiador',
        'monto_decreto',
        'entidad_ejecutora',
        'componentes_lineas_descripcion',
        'fecha_inicio_contractual',
        'fecha_conclusion_inicial_contractual',
        'plazo_contractual_inicial_dias',
        'fecha_conclusion_actual',
        'plazo_contractual_actual_dias',
        'estado_actual_derecho_propietario',
        'id_usuario_creador',
        'id_usuario_actualizador',
    ];

    protected $casts = [
        'monto_decreto' => 'decimal:2',
        'fecha_inicio_contractual' => 'date',
        'fecha_conclusion_inicial_contractual' => 'date',
        'fecha_conclusion_actual' => 'date',
    ];

    public function getRouteKeyName()
    {
        return 'codigo';
    }   

    public function ubicaciones()
    {
    return $this->hasMany(UbicacionProyecto::class, 'id_proyecto', 'id_proyecto');
    }

    public function beneficiarios()
    {
        return $this->hasMany(BeneficiarioProyecto::class, 'id_proyecto', 'id_proyecto');
    }

    public function actividades()
    {
        return $this->hasMany(Actividad::class, 'id_proyecto', 'id_proyecto');
    }

    public function problemas()
    {
        return $this->hasMany(Problema::class, 'id_proyecto', 'id_proyecto');
    }

    public function componentes()
    {
        return $this->hasMany(ComponenteProyecto::class, 'id_proyecto', 'id_proyecto');
    }
    public function contratos()
    {
    return $this->hasMany(ContratoProyecto::class, 'id_proyecto', 'id_proyecto');
    }
    public function decretosSupremos()
    {
    return $this->hasMany(DecretoSupremoProyecto::class, 'id_proyecto', 'id_proyecto');
    }
   public function decretoSupremo()
    {
    return $this->belongsTo(DecretoSupremo::class, 'id_decreto_supremo', 'id_decreto_supremo');
    }
    public function partidasPresupuestarias()
    {
    return $this->hasMany(PartidaPresupuestaria::class, 'id_proyecto', 'id_proyecto');
    }
   
}