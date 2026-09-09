<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroAuditoria extends Model
{
    protected $table = 'registros_auditoria';
    protected $primaryKey = 'id_registro_auditoria';

    // Es un log — nunca se actualiza un registro después de creado.
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_usuario', 'accion', 'tipo_registro', 'id_registro',
        'valores_anteriores', 'valores_nuevos', 'ruta', 'metodo',
        'direccion_ip', 'agente_usuario',
    ];

    protected $casts = [
        'valores_anteriores' => 'array',
        'valores_nuevos' => 'array',
    ];
}