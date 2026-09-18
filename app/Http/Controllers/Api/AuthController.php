<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Iniciar sesión
     */
    public function login(Request $request)
    {
        $datos = $request->validate([
            'correo_electronico' => [
                'required',
                'email',
            ],
            'contrasena' => [
                'required',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Buscar usuario
        |--------------------------------------------------------------------------
        */

        $usuario = Usuario::with([
            'rol.permisos'
        ])
        ->where(
            'correo_electronico',
            $datos['correo_electronico']
        )
        ->whereNull('eliminado_en')
        ->first();

        /*
        |--------------------------------------------------------------------------
        | Usuario no encontrado
        |--------------------------------------------------------------------------
        */

        if (!$usuario) {

            $this->registrarIntentoLogin(
                null,
                'LOGIN_FALLIDO',
                false,
                $request
            );

            return response()->json([
                'message' => 'Las credenciales son incorrectas.'
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Usuario inactivo
        |--------------------------------------------------------------------------
        */

        if (!$usuario->esta_activo) {

            $this->registrarIntentoLogin(
                $usuario->id_usuario,
                'LOGIN_USUARIO_INACTIVO',
                false,
                $request
            );

            return response()->json([
                'message' => 'El usuario se encuentra desactivado.'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Verificar contraseña
        |--------------------------------------------------------------------------
        */

        if (!Hash::check(
            $datos['contrasena'],
            $usuario->contrasena
        )) {

            $this->registrarIntentoLogin(
                $usuario->id_usuario,
                'LOGIN_FALLIDO',
                false,
                $request
            );

            return response()->json([
                'message' => 'Las credenciales son incorrectas.'
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Actualizar último inicio de sesión
        |--------------------------------------------------------------------------
        */

        $usuario->ultimo_inicio_sesion = now();
        $usuario->save();

        /*
        |--------------------------------------------------------------------------
        | Registrar inicio de sesión exitoso
        |--------------------------------------------------------------------------
        */

        $this->registrarIntentoLogin(
            $usuario->id_usuario,
            'LOGIN_EXITOSO',
            true,
            $request
        );

        /*
        |--------------------------------------------------------------------------
        | Crear token Sanctum
        |--------------------------------------------------------------------------
        */

        $token = $usuario->createToken(
            'web-login'
        )->plainTextToken;

        /*
        |--------------------------------------------------------------------------
        | Preparar permisos
        |--------------------------------------------------------------------------
        */

        $permisos = $usuario->rol->permisos->map(function ($permiso) {
            return [
                'id_permiso' => $permiso->id_permiso,
                'nombre' => $permiso->nombre,
                'descripcion' => $permiso->descripcion,
            ];
        })->values();

        /*
        |--------------------------------------------------------------------------
        | Respuesta
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'message' => 'Inicio de sesión exitoso.',

            'token' => $token,

            'usuario' => [
                'id_usuario' => $usuario->id_usuario,
                'nombre' => $usuario->nombre,
                'correo_electronico' => $usuario->correo_electronico,
                'ultimo_inicio_sesion' => $usuario->ultimo_inicio_sesion,
                'creado_en' => $usuario->creado_en,

                'rol' => [
                    'id_rol' => $usuario->rol->id_rol,
                    'nombre' => $usuario->rol->nombre,
                    'descripcion' => $usuario->rol->descripcion,
                ],

                'permisos' => $permisos,
            ],
        ], 200);
    }


    /**
     * Obtener usuario autenticado
     */
    public function user(Request $request)
    {
        $usuario = $request->user()->load(
            'rol.permisos'
        );

        return response()->json([
            'usuario' => [
                'id_usuario' => $usuario->id_usuario,
                'nombre' => $usuario->nombre,
                'correo_electronico' => $usuario->correo_electronico,
                'ultimo_inicio_sesion' => $usuario->ultimo_inicio_sesion,
                'creado_en' => $usuario->creado_en,

                'rol' => [
                    'id_rol' => $usuario->rol->id_rol,
                    'nombre' => $usuario->rol->nombre,
                    'descripcion' => $usuario->rol->descripcion,
                ],

                'permisos' => $usuario->rol->permisos
                    ->map(function ($permiso) {
                        return [
                            'id_permiso' => $permiso->id_permiso,
                            'nombre' => $permiso->nombre,
                            'descripcion' => $permiso->descripcion,
                        ];
                    })
                    ->values(),
            ],
        ]);
    }


    /**
     * Cerrar sesión
     */
    public function logout(Request $request)
    {
        $usuario = $request->user();

        if ($usuario) {

            /*
            |--------------------------------------------------------------------------
            | Registrar logout
            |--------------------------------------------------------------------------
            */

            $this->registrarIntentoLogin(
                $usuario->id_usuario,
                'LOGOUT',
                true,
                $request
            );

            /*
            |--------------------------------------------------------------------------
            | Eliminar token actual
            |--------------------------------------------------------------------------
            */

            $request->user()->currentAccessToken()?->delete();
        }

        return response()->json([
            'message' => 'Sesión cerrada correctamente.'
        ]);
    }


    /**
     * Registrar eventos de inicio/cierre de sesión
     */
    private function registrarIntentoLogin(
        ?int $idUsuario,
        string $evento,
        bool $exitoso,
        Request $request
    ): void {

        DB::table('registros_inicio_sesion')->insert([
            'id_usuario' => $idUsuario,
            'evento' => $evento,
            'inicio_exitoso' => $exitoso,
            'direccion_ip' => $request->ip(),
            'agente_usuario' => $request->userAgent(),
            'ocurrido_en' => now(),
        ]);
    }
}