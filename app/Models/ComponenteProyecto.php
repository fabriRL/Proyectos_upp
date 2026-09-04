<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComponenteProyecto extends Model
{
    use SoftDeletes;

    protected $table = 'componentes_proyecto';
    protected $primaryKey = 'id_componente';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';
    const DELETED_AT = 'eliminado_en';

    protected $fillable = [
        'id_proyecto',
        'nombre',
        'descripcion',
        'orden',
        'id_usuario_creador',
        'id_usuario_actualizador',
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'id_proyecto', 'id_proyecto');
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'id_componente', 'id_componente');
    }
}