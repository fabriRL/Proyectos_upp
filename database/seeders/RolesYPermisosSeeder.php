<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolesYPermisosSeeder extends Seeder
{
    private const PERMISOS = [
        ['nombre' => 'proyectos.gestionar', 'descripcion' => 'Crear, editar y ver proyectos'],
        ['nombre' => 'cronograma.gestionar', 'descripcion' => 'Gestionar actividades del cronograma'],
        ['nombre' => 'problemas.gestionar', 'descripcion' => 'Gestionar problemas y su vínculo con actividades'],
        ['nombre' => 'resumen.ver', 'descripcion' => 'Ver la pestaña de indicadores (4. Resumen)'],
        ['nombre' => 'contratos.gestionar', 'descripcion' => 'Gestionar contratos, activar/desactivar'],
        ['nombre' => 'modificaciones.gestionar', 'descripcion' => 'Gestionar modificaciones contractuales'],
        ['nombre' => 'planillas.gestionar', 'descripcion' => 'Gestionar planillas de pago'],
        ['nombre' => 'decretos.gestionar', 'descripcion' => 'Gestionar Decreto Supremo del contrato y su catálogo'],
        ['nombre' => 'financiero.gestionar', 'descripcion' => 'Gestionar Programación Financiera'],
        ['nombre' => 'reportes.gestionar', 'descripcion' => 'Generar reportes y ver/eliminar su historial'],
        ['nombre' => 'dashboard.ver', 'descripcion' => 'Ver el Dashboard global'],
        ['nombre' => 'roles.gestionar', 'descripcion' => 'Administrar roles y permisos del sistema'],
    ];

    public function run(): void
    {
        $idsPermisos = [];

        foreach (self::PERMISOS as $datos) {
            $permiso = Permiso::firstOrCreate(['nombre' => $datos['nombre']], $datos);
            $idsPermisos[] = $permiso->id_permiso;
        }

        $administrador = Rol::firstOrCreate(
            ['nombre' => 'Administrador'],
            ['descripcion' => 'Acceso total al sistema — todos los permisos.']
        );

        $datosSincronizar = collect($idsPermisos)->mapWithKeys(fn ($id) => [
            $id => ['creado_en' => now()],
        ])->toArray();

        $administrador->permisos()->syncWithoutDetaching($datosSincronizar);

        $this->command->info('Roles y permisos creados. Administrador tiene ' . count($idsPermisos) . ' permisos.');
    }
}