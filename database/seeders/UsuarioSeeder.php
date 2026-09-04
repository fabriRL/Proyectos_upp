<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::create([
            'id_rol' => 1,
            'nombre' => 'Administrador',
            'correo_electronico' => 'admin@proyectos.com',
            'contrasena' => Hash::make('Admin12345'),
            'esta_activo' => true,
        ]);
    }
}