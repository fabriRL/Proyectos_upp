<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id_rol';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = ['nombre', 'descripcion'];

    public function permisos()
    {
        return $this->belongsToMany(Permiso::class, 'roles_permisos', 'id_rol', 'id_permiso');
    }

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'id_rol', 'id_rol');
    }

    public function tienePermiso(string $nombrePermiso): bool
    {
        return $this->permisos()->where('nombre', $nombrePermiso)->exists();
    }
}