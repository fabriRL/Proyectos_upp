<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DecretoSupremo extends Model
{
    use SoftDeletes;

    protected $table = 'decretos_supremos';
    protected $primaryKey = 'id_decreto_supremo';
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';
    const DELETED_AT = 'eliminado_en';

    protected $fillable = [
        'numero_decreto', 'monto', 'descripcion', 'fecha_decreto',
        'id_usuario_creador', 'id_usuario_actualizador',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_decreto' => 'date:Y-m-d',
    ];

    public function proyectos()
    {
        return $this->hasMany(Proyecto::class, 'id_decreto_supremo', 'id_decreto_supremo');
    }
}