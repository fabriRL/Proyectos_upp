<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\UbicacionProyecto;
use App\Models\BeneficiarioProyecto;
use App\Models\ComponenteProyecto;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\DecretoSupremo;

class ProyectoController extends Controller
{
    public function index()
    {
        return response()->json(
            Proyecto::orderBy('creado_en', 'desc')->get()
        );
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'codigo' => 'required|string|max:30|unique:proyectos,codigo',
            'numero_sisin_web' => 'nullable|string|max:50',
            'nombre' => 'required|string|max:255',
            'estado' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string',
            'fiscal_general' => 'nullable|string|max:255',
            'entidad_ejecutora' => 'nullable|string|max:150',
            'fuente_financiamiento' => 'nullable|string|max:150',

            'id_decreto_supremo' => 'nullable|integer|exists:decretos_supremos,id_decreto_supremo',
            'nuevo_decreto_numero' => 'nullable|string|max:255',
            'nuevo_decreto_monto' => 'nullable|numeric|min:0',

            'familias_productoras' => 'nullable|integer|min:0',
            'total_beneficiarios' => 'nullable|integer|min:0',
            'empleos_directos_construccion' => 'nullable|integer|min:0',
            'empleos_indirectos_construccion' => 'nullable|integer|min:0',
            'empleos_directos_operacion' => 'nullable|integer|min:0',
            'empleos_indirectos_operacion' => 'nullable|integer|min:0',

            'ubicaciones' => 'nullable|array',
            'ubicaciones.*.departamento' => 'nullable|string|max:100',
            'ubicaciones.*.provincia' => 'nullable|string|max:150',
            'ubicaciones.*.municipio' => 'nullable|string|max:150',
            'ubicaciones.*.comunidad_localidad' => 'nullable|string|max:150',
            'ubicaciones.*.coordenada_norte' => 'nullable|numeric',
            'ubicaciones.*.coordenada_este' => 'nullable|numeric',
            'ubicaciones.*.zona_utm' => 'nullable|string|max:20',

            'fecha_inicio_contractual' => 'nullable|date',
            'fecha_conclusion_inicial_contractual' => 'nullable|date',
            'plazo_contractual_inicial_dias' => 'nullable|integer|min:0',
            'fecha_conclusion_actual' => 'nullable|date',
            'plazo_contractual_actual_dias' => 'nullable|integer|min:0',

            'componentes' => 'nullable|array',
            'componentes.*.nombre' => 'required_with:componentes|string|max:150',
            'componentes.*.descripcion' => 'nullable|string',
            'componentes.*.productos' => 'nullable|array',
            'componentes.*.productos.*.nombre' => 'required_with:componentes.*.productos|string|max:255',
            'componentes.*.productos.*.cantidad' => 'nullable|numeric|min:0',
            'componentes.*.productos.*.unidad' => 'nullable|string|max:50',
        ]);

        $proyecto = DB::transaction(function () use ($datos) {

            $userId = Auth::id();

            $idDecretoSupremo = null;
            $normaFinanciador = null;
            $montoDecreto = null;

            if (!empty($datos['id_decreto_supremo'])) {
                $decreto = DecretoSupremo::find($datos['id_decreto_supremo']);
                $idDecretoSupremo = $decreto->id_decreto_supremo;
                $normaFinanciador = $decreto->numero_decreto;
                $montoDecreto = $decreto->monto;
            } elseif (!empty($datos['nuevo_decreto_numero'])) {
                $decreto = DecretoSupremo::firstOrCreate(
                    ['numero_decreto' => $datos['nuevo_decreto_numero']],
                    [
                        'monto' => $datos['nuevo_decreto_monto'] ?? 0,
                        'id_usuario_creador' => $userId,
                        'id_usuario_actualizador' => $userId,
                    ]
                );
                $idDecretoSupremo = $decreto->id_decreto_supremo;
                $normaFinanciador = $decreto->numero_decreto;
                $montoDecreto = $decreto->monto;
            }

            $proyecto = Proyecto::create([
                'codigo' => $datos['codigo'],
                'numero_sisin_web' => $datos['numero_sisin_web'] ?? null,
                'nombre' => $datos['nombre'],
                'estado' => $datos['estado'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
                'fiscal_general' => $datos['fiscal_general'] ?? null,
                'entidad_ejecutora' => $datos['entidad_ejecutora'] ?? null,
                'fuente_financiamiento' => $datos['fuente_financiamiento'] ?? null,
                'id_decreto_supremo' => $idDecretoSupremo,
                'norma_financiador' => $normaFinanciador,
                'monto_decreto' => $montoDecreto,
                'fecha_inicio_contractual' => $datos['fecha_inicio_contractual'] ?? null,
                'fecha_conclusion_inicial_contractual' => $datos['fecha_conclusion_inicial_contractual'] ?? null,
                'plazo_contractual_inicial_dias' => $datos['plazo_contractual_inicial_dias'] ?? null,
                'fecha_conclusion_actual' => $datos['fecha_conclusion_actual'] ?? null,
                'plazo_contractual_actual_dias' => $datos['plazo_contractual_actual_dias'] ?? null,
                'id_usuario_creador' => $userId,
                'id_usuario_actualizador' => $userId,
            ]);

            foreach ($datos['ubicaciones'] ?? [] as $ubi) {
                $tieneDatos = collect([
                    $ubi['departamento'] ?? null, $ubi['provincia'] ?? null,
                    $ubi['municipio'] ?? null, $ubi['comunidad_localidad'] ?? null,
                    $ubi['coordenada_norte'] ?? null, $ubi['coordenada_este'] ?? null,
                ])->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();

                if (!$tieneDatos) continue;

                UbicacionProyecto::create([
                    'id_proyecto' => $proyecto->id_proyecto,
                    'departamento' => $ubi['departamento'] ?? null,
                    'provincia' => $ubi['provincia'] ?? null,
                    'municipio' => $ubi['municipio'] ?? null,
                    'comunidad_localidad' => $ubi['comunidad_localidad'] ?? null,
                    'coordenada_norte' => $ubi['coordenada_norte'] ?? null,
                    'coordenada_este' => $ubi['coordenada_este'] ?? null,
                    'zona_utm' => $ubi['zona_utm'] ?? null,
                    'id_usuario_creador' => $userId,
                    'id_usuario_actualizador' => $userId,
                ]);
            }

            $filasBeneficiarios = [
                ['categoria' => 'Beneficiarios', 'tipo' => 'Familias productoras', 'cantidad' => $datos['familias_productoras'] ?? null],
                ['categoria' => 'Beneficiarios', 'tipo' => 'Total beneficiarios', 'cantidad' => $datos['total_beneficiarios'] ?? null],
                ['categoria' => 'Empleo - Construcción', 'tipo' => 'Directos', 'cantidad' => $datos['empleos_directos_construccion'] ?? null],
                ['categoria' => 'Empleo - Construcción', 'tipo' => 'Indirectos', 'cantidad' => $datos['empleos_indirectos_construccion'] ?? null],
                ['categoria' => 'Empleo - Operación', 'tipo' => 'Directos', 'cantidad' => $datos['empleos_directos_operacion'] ?? null],
                ['categoria' => 'Empleo - Operación', 'tipo' => 'Indirectos', 'cantidad' => $datos['empleos_indirectos_operacion'] ?? null],
            ];

            foreach ($filasBeneficiarios as $fila) {
                if ($fila['cantidad'] !== null) {
                    BeneficiarioProyecto::create([
                        'id_proyecto' => $proyecto->id_proyecto,
                        'categoria' => $fila['categoria'],
                        'tipo' => $fila['tipo'],
                        'cantidad' => $fila['cantidad'],
                        'id_usuario_creador' => $userId,
                        'id_usuario_actualizador' => $userId,
                    ]);
                }
            }

            foreach ($datos['componentes'] ?? [] as $ordenComponente => $comp) {
                if (empty(trim($comp['nombre'] ?? ''))) continue;

                $componente = ComponenteProyecto::create([
                    'id_proyecto' => $proyecto->id_proyecto,
                    'nombre' => $comp['nombre'],
                    'descripcion' => $comp['descripcion'] ?? null,
                    'orden' => $ordenComponente + 1,
                    'id_usuario_creador' => $userId,
                    'id_usuario_actualizador' => $userId,
                ]);

                foreach ($comp['productos'] ?? [] as $ordenProducto => $prod) {
                    if (empty(trim($prod['nombre'] ?? ''))) continue;

                    Producto::create([
                        'id_componente' => $componente->id_componente,
                        'nombre' => $prod['nombre'],
                        'cantidad' => $prod['cantidad'] ?? null,
                        'unidad' => $prod['unidad'] ?? null,
                        'orden' => $ordenProducto + 1,
                        'id_usuario_creador' => $userId,
                        'id_usuario_actualizador' => $userId,
                    ]);
                }
            }

            return $proyecto;
        });

        return response()->json($proyecto, 201);
    }

    public function show(Proyecto $proyecto)
    {
        return response()->json(
            $proyecto->load(['ubicaciones', 'beneficiarios', 'componentes.productos', 'decretoSupremo'])
        );
    }

    public function update(Request $request, Proyecto $proyecto)
    {
        $datos = $request->validate([
            'codigo' => 'sometimes|required|string|max:30|unique:proyectos,codigo,' . $proyecto->id_proyecto . ',id_proyecto',
            'numero_sisin_web' => 'nullable|string|max:50',
            'nombre' => 'sometimes|required|string|max:255',
            'estado' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string',
            'fiscal_general' => 'nullable|string|max:255',
            'entidad_ejecutora' => 'nullable|string|max:150',
            'fuente_financiamiento' => 'nullable|string|max:150',

            // Igual que en store(): elegir un decreto existente O crear uno
            // nuevo. Antes faltaba por completo, así que Laravel descartaba
            // silenciosamente id_decreto_supremo del request.
            'id_decreto_supremo' => 'nullable|integer|exists:decretos_supremos,id_decreto_supremo',
            'nuevo_decreto_numero' => 'nullable|string|max:255',
            'nuevo_decreto_monto' => 'nullable|numeric|min:0',

            'familias_productoras' => 'nullable|integer|min:0',
            'total_beneficiarios' => 'nullable|integer|min:0',
            'empleos_directos_construccion' => 'nullable|integer|min:0',
            'empleos_indirectos_construccion' => 'nullable|integer|min:0',
            'empleos_directos_operacion' => 'nullable|integer|min:0',
            'empleos_indirectos_operacion' => 'nullable|integer|min:0',

            'ubicaciones' => 'nullable|array',
            'ubicaciones.*.departamento' => 'nullable|string|max:100',
            'ubicaciones.*.provincia' => 'nullable|string|max:150',
            'ubicaciones.*.municipio' => 'nullable|string|max:150',
            'ubicaciones.*.comunidad_localidad' => 'nullable|string|max:150',
            'ubicaciones.*.coordenada_norte' => 'nullable|numeric',
            'ubicaciones.*.coordenada_este' => 'nullable|numeric',
            'ubicaciones.*.zona_utm' => 'nullable|string|max:20',

            'fecha_inicio_contractual' => 'nullable|date',
            'fecha_conclusion_inicial_contractual' => 'nullable|date',
            'plazo_contractual_inicial_dias' => 'nullable|integer|min:0',
            'fecha_conclusion_actual' => 'nullable|date',
            'plazo_contractual_actual_dias' => 'nullable|integer|min:0',

            'componentes' => 'nullable|array',
            'componentes.*.nombre' => 'required_with:componentes|string|max:150',
            'componentes.*.descripcion' => 'nullable|string',
            'componentes.*.productos' => 'nullable|array',
            'componentes.*.productos.*.nombre' => 'required_with:componentes.*.productos|string|max:255',
            'componentes.*.productos.*.cantidad' => 'nullable|numeric|min:0',
            'componentes.*.productos.*.unidad' => 'nullable|string|max:50',
        ]);

        $proyecto = DB::transaction(function () use ($datos, $proyecto) {

            $userId = Auth::id();

            // Resuelve el decreto igual que en store(): si viene un ID
            // existente, se copian sus datos; si viene "nuevo_decreto_...",
            // se crea (o reutiliza uno con el mismo número). Si no viene
            // ninguno de los dos, el decreto del proyecto se queda como
            // estaba — no se toca.
            $camposDecreto = [];
            if (!empty($datos['id_decreto_supremo'])) {
                $decreto = DecretoSupremo::find($datos['id_decreto_supremo']);
                $camposDecreto = [
                    'id_decreto_supremo' => $decreto->id_decreto_supremo,
                    'norma_financiador' => $decreto->numero_decreto,
                    'monto_decreto' => $decreto->monto,
                ];
            } elseif (!empty($datos['nuevo_decreto_numero'])) {
                $decreto = DecretoSupremo::firstOrCreate(
                    ['numero_decreto' => $datos['nuevo_decreto_numero']],
                    [
                        'monto' => $datos['nuevo_decreto_monto'] ?? 0,
                        'id_usuario_creador' => $userId,
                        'id_usuario_actualizador' => $userId,
                    ]
                );
                $camposDecreto = [
                    'id_decreto_supremo' => $decreto->id_decreto_supremo,
                    'norma_financiador' => $decreto->numero_decreto,
                    'monto_decreto' => $decreto->monto,
                ];
            }

            $proyecto->update(array_filter([
                'codigo' => $datos['codigo'] ?? null,
                'numero_sisin_web' => $datos['numero_sisin_web'] ?? null,
                'nombre' => $datos['nombre'] ?? null,
                'estado' => $datos['estado'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
                'fiscal_general' => $datos['fiscal_general'] ?? null,
                'entidad_ejecutora' => $datos['entidad_ejecutora'] ?? null,
                'fuente_financiamiento' => $datos['fuente_financiamiento'] ?? null,
                'fecha_inicio_contractual' => $datos['fecha_inicio_contractual'] ?? null,
                'fecha_conclusion_inicial_contractual' => $datos['fecha_conclusion_inicial_contractual'] ?? null,
                'plazo_contractual_inicial_dias' => $datos['plazo_contractual_inicial_dias'] ?? null,
                'fecha_conclusion_actual' => $datos['fecha_conclusion_actual'] ?? null,
                'plazo_contractual_actual_dias' => $datos['plazo_contractual_actual_dias'] ?? null,
            ], fn ($v) => $v !== null) + $camposDecreto + ['id_usuario_actualizador' => $userId]);

            if (array_key_exists('ubicaciones', $datos)) {
                UbicacionProyecto::where('id_proyecto', $proyecto->id_proyecto)->delete();

                foreach ($datos['ubicaciones'] ?? [] as $ubi) {
                    $tieneDatos = collect([
                        $ubi['departamento'] ?? null,
                        $ubi['provincia'] ?? null,
                        $ubi['municipio'] ?? null,
                        $ubi['comunidad_localidad'] ?? null,
                        $ubi['coordenada_norte'] ?? null,
                        $ubi['coordenada_este'] ?? null,
                    ])->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();

                    if (!$tieneDatos) {
                        continue;
                    }

                    UbicacionProyecto::create([
                        'id_proyecto' => $proyecto->id_proyecto,
                        'departamento' => $ubi['departamento'] ?? null,
                        'provincia' => $ubi['provincia'] ?? null,
                        'municipio' => $ubi['municipio'] ?? null,
                        'comunidad_localidad' => $ubi['comunidad_localidad'] ?? null,
                        'coordenada_norte' => $ubi['coordenada_norte'] ?? null,
                        'coordenada_este' => $ubi['coordenada_este'] ?? null,
                        'zona_utm' => $ubi['zona_utm'] ?? null,
                        'id_usuario_creador' => $userId,
                        'id_usuario_actualizador' => $userId,
                    ]);
                }
            }

            $filasBeneficiarios = [
                ['categoria' => 'Beneficiarios', 'tipo' => 'Familias productoras', 'cantidad' => $datos['familias_productoras'] ?? null],
                ['categoria' => 'Beneficiarios', 'tipo' => 'Total beneficiarios', 'cantidad' => $datos['total_beneficiarios'] ?? null],
                ['categoria' => 'Empleo - Construcción', 'tipo' => 'Directos', 'cantidad' => $datos['empleos_directos_construccion'] ?? null],
                ['categoria' => 'Empleo - Construcción', 'tipo' => 'Indirectos', 'cantidad' => $datos['empleos_indirectos_construccion'] ?? null],
                ['categoria' => 'Empleo - Operación', 'tipo' => 'Directos', 'cantidad' => $datos['empleos_directos_operacion'] ?? null],
                ['categoria' => 'Empleo - Operación', 'tipo' => 'Indirectos', 'cantidad' => $datos['empleos_indirectos_operacion'] ?? null],
            ];

            foreach ($filasBeneficiarios as $fila) {
                if ($fila['cantidad'] !== null) {
                    BeneficiarioProyecto::updateOrCreate(
                        [
                            'id_proyecto' => $proyecto->id_proyecto,
                            'categoria' => $fila['categoria'],
                            'tipo' => $fila['tipo'],
                        ],
                        [
                            'cantidad' => $fila['cantidad'],
                            'id_usuario_actualizador' => $userId,
                            'id_usuario_creador' => $userId,
                        ]
                    );
                } else {
                    BeneficiarioProyecto::where('id_proyecto', $proyecto->id_proyecto)
                        ->where('categoria', $fila['categoria'])
                        ->where('tipo', $fila['tipo'])
                        ->delete();
                }
            }

            if (array_key_exists('componentes', $datos)) {
                $componentesExistentes = ComponenteProyecto::where('id_proyecto', $proyecto->id_proyecto)->get();

                foreach ($componentesExistentes as $componenteViejo) {
                    Producto::where('id_componente', $componenteViejo->id_componente)->delete();
                    $componenteViejo->delete();
                }

                foreach ($datos['componentes'] ?? [] as $ordenComponente => $comp) {
                    if (empty(trim($comp['nombre'] ?? ''))) {
                        continue;
                    }

                    $componenteNuevo = ComponenteProyecto::create([
                        'id_proyecto' => $proyecto->id_proyecto,
                        'nombre' => $comp['nombre'],
                        'descripcion' => $comp['descripcion'] ?? null,
                        'orden' => $ordenComponente + 1,
                        'id_usuario_creador' => $userId,
                        'id_usuario_actualizador' => $userId,
                    ]);

                    foreach ($comp['productos'] ?? [] as $ordenProducto => $prod) {
                        if (empty(trim($prod['nombre'] ?? ''))) {
                            continue;
                        }

                        Producto::create([
                            'id_componente' => $componenteNuevo->id_componente,
                            'nombre' => $prod['nombre'],
                            'cantidad' => $prod['cantidad'] ?? null,
                            'unidad' => $prod['unidad'] ?? null,
                            'orden' => $ordenProducto + 1,
                            'id_usuario_creador' => $userId,
                            'id_usuario_actualizador' => $userId,
                        ]);
                    }
                }
            }

            return $proyecto;
        });

        return response()->json($proyecto->load(['ubicaciones', 'beneficiarios', 'componentes.productos', 'decretoSupremo']));
    }

    public function destroy(Proyecto $proyecto)
    {
        $proyecto->update(['id_usuario_actualizador' => Auth::id()]);
        $proyecto->delete();

        return response()->json(['message' => 'Proyecto eliminado correctamente.']);
    }
}