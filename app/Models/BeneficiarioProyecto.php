<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BeneficiarioProyecto extends Model
{
    use SoftDeletes;

    protected $table = 'beneficiarios_proyecto';
    protected $primaryKey = 'id_beneficiario_proyecto';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';
    const DELETED_AT = 'eliminado_en';

    protected $fillable = [
        'id_proyecto',
        'categoria',
        'tipo',
        'cantidad',
        'descripcion',
        'id_usuario_creador',
        'id_usuario_actualizador',
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'id_proyecto', 'id_proyecto');
    }
}