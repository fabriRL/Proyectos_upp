<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use SoftDeletes;

    protected $table = 'productos';
    protected $primaryKey = 'id_producto';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';
    const DELETED_AT = 'eliminado_en';

    protected $fillable = [
        'id_componente',
        'nombre',
        'descripcion',
        'cantidad',
        'unidad',
        'orden',
        'id_usuario_creador',
        'id_usuario_actualizador',
    ];

    public function componente()
    {
        return $this->belongsTo(ComponenteProyecto::class, 'id_componente', 'id_componente');
    }
}