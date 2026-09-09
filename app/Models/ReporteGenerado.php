<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteGenerado extends Model
{
    protected $table = 'reportes_generados';
    protected $primaryKey = 'id_reporte';
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_usuario', 'tipo', 'nombre_archivo', 'ruta_archivo', 'total_proyectos',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->ruta_archivo);
    }
}