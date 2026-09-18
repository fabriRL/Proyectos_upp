<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegistroAuditoria;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AuditoriaController extends Controller
{
    /**
     * Listado paginado de cambios registrados por los usuarios,
     * con filtros opcionales.
     */
    public function index(Request $request)
    {
        $query = RegistroAuditoria::with('usuario:id_usuario,nombre,correo_electronico')
            ->orderByDesc('creado_en');

        if ($request->filled('id_usuario')) {
            $query->where('id_usuario', $request->id_usuario);
        }

        if ($request->filled('tipo_registro')) {
            $query->where('tipo_registro', $request->tipo_registro);
        }

        if ($request->filled('accion')) {
            $query->where('accion', $request->accion);
        }

        if ($request->filled('desde')) {
            $query->whereDate('creado_en', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->whereDate('creado_en', '<=', $request->hasta);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('ruta', 'like', "%{$buscar}%")
                    ->orWhere('tipo_registro', 'like', "%{$buscar}%")
                    ->orWhereHas('usuario', function ($u) use ($buscar) {
                        $u->where('nombre', 'like', "%{$buscar}%");
                    });
            });
        }

        $registros = $query->paginate(30)->withQueryString();

        return response()->json($registros);
    }

    /**
     * Opciones disponibles para los filtros (evita mandar el listado
     * completo solo para armar los combos del frontend).
     */
    public function filtros()
    {
        $idsUsuarios = RegistroAuditoria::whereNotNull('id_usuario')
            ->select('id_usuario')
            ->distinct()
            ->pluck('id_usuario');

        return response()->json([
            'usuarios' => Usuario::whereIn('id_usuario', $idsUsuarios)
                ->orderBy('nombre')
                ->get(['id_usuario', 'nombre']),

            'tipos_registro' => RegistroAuditoria::select('tipo_registro')
                ->distinct()
                ->orderBy('tipo_registro')
                ->pluck('tipo_registro'),

            'acciones' => RegistroAuditoria::select('accion')
                ->distinct()
                ->pluck('accion'),
        ]);
    }

    /**
     * Estadísticas rápidas para las tarjetas de resumen del módulo.
     */
    public function resumen()
    {
        return response()->json([
            'total_registros' => RegistroAuditoria::count(),

            'cambios_hoy' => RegistroAuditoria::whereDate('creado_en', Carbon::today())->count(),

            'usuarios_activos' => RegistroAuditoria::whereNotNull('id_usuario')
                ->distinct('id_usuario')
                ->count('id_usuario'),

            'por_accion' => RegistroAuditoria::selectRaw('accion, count(*) as total')
                ->groupBy('accion')
                ->pluck('total', 'accion'),
        ]);
    }
}
