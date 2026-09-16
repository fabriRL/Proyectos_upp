<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'usuarios';

    protected $primaryKey = 'id_usuario';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'id_rol',
        'nombre',
        'correo_electronico',
        'contrasena',
        'esta_activo',
        'ultimo_inicio_sesion',
    ];

    protected $hidden = [
        'contrasena',
    ];

    protected $casts = [
        'esta_activo' => 'boolean',
        'ultimo_inicio_sesion' => 'datetime',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
        'eliminado_en' => 'datetime',
    ];

    public function getAuthPassword()
    {
        return $this->contrasena;
    }

    public function rol()
    {
        return $this->belongsTo(
            Rol::class,
            'id_rol',
            'id_rol'
        );
    }

    // Verifica si el usuario, a través de su rol, tiene un permiso
    // específico. Usuarios sin rol asignado nunca tienen permisos.
    public function tienePermiso(string $nombrePermiso): bool
    {
        if (!$this->id_rol) {
            return false;
        }

        return $this->rol?->tienePermiso($nombrePermiso) ?? false;
    }
}