<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContratoProyecto;
use App\Models\Proyecto;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContratoController extends Controller
{
    public function index(Proyecto $proyecto)
    {
        $contratos = $proyecto->contratos()->orderBy('numero')->get();

        return response()->json([
            'contratos' => $contratos,
            'totales' => $this->calcularTotales($contratos->where('activo', true)),
        ]);
    }

    public function store(Request $request, Proyecto $proyecto)
    {
        $validated = $request->validate([
            'tipo_contrato' => 'required|string|max:150',
            'contratista' => 'required|string|max:255',
            'numero_minuta' => 'nullable|string|max:255',
            'fecha_firma_contrato' => 'nullable|date',
            'fecha_orden_proceder' => 'required|date',
            'archivo_orden_proceder' => 'required|file|mimes:pdf|max:10240',
            'monto_vigente' => 'required|numeric',
            'anticipo' => 'nullable|numeric|min:0',
            'anticipo_porcentaje' => 'nullable|numeric|min:0|max:100',
            'amortizacion_acumulada' => 'nullable|numeric',
            'monto_ejecutado_acumulado' => 'nullable|numeric',
            'liquido_pagable_acumulado' => 'nullable|numeric',
            'multas' => 'nullable|numeric',
            'retencion_gcc' => 'nullable|numeric',
            'total_descuentos' => 'nullable|numeric',
            'saldo_por_pagar' => 'nullable|numeric',
            'estado_contractual' => 'required|string|max:50',
            'fecha_conclusion_prevista' => 'nullable|date',
            'fecha_entrega_provisional' => 'nullable|date',
            'fecha_entrega_definitiva' => 'nullable|date',
            'avance_fisico' => 'nullable|numeric|min:0|max:100',
            'avance_financiero' => 'nullable|numeric|min:0|max:100',
            'estado_fisico' => 'nullable|string|max:50',
        ]);

        if (!empty($validated['anticipo_porcentaje'])) {
            $validated['anticipo'] = round(
                $validated['monto_vigente'] * ($validated['anticipo_porcentaje'] / 100),
                2
            );
        } else {
            $validated['anticipo'] = $validated['anticipo'] ?? 0;
        }

        $archivo = $request->file('archivo_orden_proceder');
        $archivoPath = $archivo->store('contratos/orden_proceder', 'public');
        $archivoNombreOriginal = $archivo->getClientOriginalName();

        unset($validated['archivo_orden_proceder']);

        $validated['id_proyecto'] = $proyecto->id_proyecto;
        $validated['numero'] = ($proyecto->contratos()->max('numero') ?? 0) + 1;
        $validated['id_usuario_creador'] = $request->user()->id_usuario ?? $request->user()->id;
        $validated['archivo_orden_proceder_path'] = $archivoPath;
        $validated['archivo_orden_proceder_nombre_original'] = $archivoNombreOriginal;
        $validated['activo'] = true;

        if (!empty($validated['fecha_orden_proceder']) && !empty($validated['fecha_conclusion_prevista'])) {
            $validated['plazo_dias'] = Carbon::parse($validated['fecha_orden_proceder'])
                ->diffInDays(Carbon::parse($validated['fecha_conclusion_prevista'])) + 1;
        }

        // Al crear el contrato, sin planillas todavía: Líquido Pagable
        // Acumulado = anticipo, y Saldo por Pagar = monto_vigente − anticipo.
        $validated['liquido_pagable_acumulado'] = $validated['anticipo'];
        $validated['saldo_por_pagar'] = $validated['monto_vigente'] - $validated['anticipo'];

        $contrato = ContratoProyecto::create($validated);

        return response()->json($contrato, 201);
    }

    public function update(Request $request, ContratoProyecto $contrato)
    {
        $validated = $request->validate([
            'tipo_contrato' => 'sometimes|string|max:150',
            'contratista' => 'sometimes|string|max:255',
            'numero_minuta' => 'nullable|string|max:255',
            'fecha_firma_contrato' => 'nullable|date',
            'fecha_orden_proceder' => 'nullable|date',
            'archivo_orden_proceder' => 'nullable|file|mimes:pdf|max:10240',
            'monto_vigente' => 'sometimes|numeric',
            'anticipo' => 'nullable|numeric|min:0',
            'anticipo_porcentaje' => 'nullable|numeric|min:0|max:100',
            'amortizacion_acumulada' => 'nullable|numeric',
            'monto_ejecutado_acumulado' => 'nullable|numeric',
            'liquido_pagable_acumulado' => 'nullable|numeric',
            'multas' => 'nullable|numeric',
            'retencion_gcc' => 'nullable|numeric',
            'total_descuentos' => 'nullable|numeric',
            'saldo_por_pagar' => 'nullable|numeric',
            'estado_contractual' => 'sometimes|string|max:50',
            'fecha_conclusion_prevista' => 'nullable|date',
            'fecha_entrega_provisional' => 'nullable|date',
            'fecha_entrega_definitiva' => 'nullable|date',
            'avance_fisico' => 'nullable|numeric|min:0|max:100',
            'avance_financiero' => 'nullable|numeric|min:0|max:100',
            'estado_fisico' => 'nullable|string|max:50',
        ]);

        $validated['id_usuario_actualizador'] = $request->user()->id_usuario ?? $request->user()->id;

        $fechaOrden = $validated['fecha_orden_proceder'] ?? $contrato->fecha_orden_proceder;
        $fechaConclusion = $validated['fecha_conclusion_prevista'] ?? $contrato->fecha_conclusion_prevista;

        if (!empty($fechaOrden) && !empty($fechaConclusion)) {
            $validated['plazo_dias'] = Carbon::parse($fechaOrden)
                ->diffInDays(Carbon::parse($fechaConclusion)) + 1;
        }

        $montoVigenteEfectivo = $validated['monto_vigente'] ?? $contrato->monto_vigente;
        if (!empty($validated['anticipo_porcentaje'])) {
            $validated['anticipo'] = round(
                $montoVigenteEfectivo * ($validated['anticipo_porcentaje'] / 100),
                2
            );
        }

        // Si se está editando el anticipo (o el monto vigente) y el contrato
        // todavía no tiene planillas, el líquido pagable y el saldo por
        // pagar se recalculan con la fórmula base (sin ejecución todavía).
        if ((array_key_exists('anticipo', $validated) || array_key_exists('monto_vigente', $validated))
            && (float) $contrato->monto_ejecutado_acumulado === 0.0) {
            $anticipoEfectivo = $validated['anticipo'] ?? $contrato->anticipo;
            $validated['liquido_pagable_acumulado'] = $anticipoEfectivo;
            $validated['saldo_por_pagar'] = $montoVigenteEfectivo - $anticipoEfectivo;
        }

        if ($request->hasFile('archivo_orden_proceder')) {
            if ($contrato->archivo_orden_proceder_path) {
                Storage::disk('public')->delete($contrato->archivo_orden_proceder_path);
            }
            $archivo = $request->file('archivo_orden_proceder');
            $validated['archivo_orden_proceder_path'] = $archivo->store('contratos/orden_proceder', 'public');
            $validated['archivo_orden_proceder_nombre_original'] = $archivo->getClientOriginalName();
        }
        unset($validated['archivo_orden_proceder']);

        $contrato->update($validated);

        return response()->json($contrato);
    }

    public function toggleActivo(Request $request, ContratoProyecto $contrato)
    {
        $contrato->update([
            'activo' => !$contrato->activo,
            'id_usuario_actualizador' => $request->user()->id_usuario ?? $request->user()->id,
        ]);

        return response()->json($contrato);
    }

    public function destroy(ContratoProyecto $contrato)
    {
        if ($contrato->archivo_orden_proceder_path) {
            Storage::disk('public')->delete($contrato->archivo_orden_proceder_path);
        }
        $contrato->delete();
        return response()->json(['message' => 'Contrato eliminado'], 200);
    }

    private function calcularTotales($contratos)
    {
        return [
            'monto_vigente' => (float) $contratos->sum('monto_vigente'),
            'monto_ejecutado' => (float) $contratos->sum('monto_ejecut  ado_acumulado'),
            'saldo_por_pagar' => (float) $contratos->sum('saldo_por_pagar'),
            'anticipo' => (float) $contratos->sum('anticipo'),
            'amortizacion_acumulada' => (float) $contratos->sum('amortizacion_acumulada'),
            'liquido_pagable_acumulado' => (float) $contratos->sum('liquido_pagable_acumulado'),
            'multas' => (float) $contratos->sum('multas'),
            'retencion_gcc' => (float) $contratos->sum('retencion_gcc'),
            'total_descuentos' => (float) $contratos->sum('total_descuentos'),
        ];
    }
}