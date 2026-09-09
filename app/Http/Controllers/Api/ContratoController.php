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
    // Solo las modificaciones ya CONCLUIDAS (aprobadas/firmadas) suman al
    // Monto Vigente del contrato — las Pendientes todavía no cuentan.
    // Debe coincidir exactamente con la misma lista en
    // ModificacionContractualController.
    private const ESTADOS_QUE_SUMAN = ['Concluido'];

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

        // El contrato nace sin modificaciones — el monto vigente ES el
        // original, y ambos arrancan iguales.
        $validated['monto_vigente_original'] = $validated['monto_vigente'];

        // Al crear, todavía no hay planillas: ejecutado = 0, amortización = 0.
        $validated['liquido_pagable_acumulado'] = $validated['anticipo'];
        $validated['saldo_por_pagar'] = $validated['monto_vigente'] - $validated['anticipo'];

        // Avance Financiero = (Ejecutado − Amortización + Anticipo) / Vigente × 100.
        // Al no existir ejecutado ni amortización todavía, se reduce a
        // anticipo / monto_vigente. Se calcula SIEMPRE aquí — nunca se usa
        // el valor que venga del formulario, aunque el campo lo acepte.
        $validated['avance_financiero'] = $validated['monto_vigente'] > 0
            ? round(($validated['anticipo'] / $validated['monto_vigente']) * 100, 2)
            : 0;

        // Avance Físico: 100% si ya tiene fecha real de entrega definitiva
        // cargada (poco común al crear, pero posible); si no, 0% (recién
        // creado, sin ejecución todavía).
        $validated['avance_fisico'] = !empty($validated['fecha_entrega_definitiva'])
            ? 100
            : 0;

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

        // Si el usuario edita Monto Vigente DIRECTO en este formulario (no
        // a través de una modificación), hay que correr monto_vigente_original
        // hacia arriba/abajo por la misma diferencia — para que la próxima
        // vez que se cree/edite una modificación, el recálculo automático
        // no "pise" este ajuste manual.
        if (array_key_exists('monto_vigente', $validated)) {
            $sumaModificaciones = (float) $contrato->modificaciones()
                ->whereIn('estado_documento', self::ESTADOS_QUE_SUMAN)
                ->sum('monto_modificacion');

            $validated['monto_vigente_original'] = $validated['monto_vigente'] - $sumaModificaciones;
        }

        // ---------------------------------------------------------------
        // Recalcula SIEMPRE que cambie el anticipo o el monto vigente —
        // antes esto solo pasaba si el contrato no tenía ejecución
        // (monto_ejecutado_acumulado === 0), lo que dejaba desactualizados
        // Avance Financiero y Saldo por Pagar en contratos con planillas
        // ya registradas. La fórmula funciona igual de bien en ambos
        // casos: sin ejecución, ejecutado_neto y amortización valen 0 y
        // el resultado es el mismo de siempre.
        // ---------------------------------------------------------------
        if (array_key_exists('anticipo', $validated) || array_key_exists('monto_vigente', $validated)) {
            $anticipoEfectivo = $validated['anticipo'] ?? $contrato->anticipo;
            $ejecutadoNeto = (float) $contrato->monto_ejecutado_acumulado;
            $amortizacionAcumulada = (float) $contrato->amortizacion_acumulada;

            $liquidoPagableAcumulado = $ejecutadoNeto + $anticipoEfectivo;

            $validated['liquido_pagable_acumulado'] = round($liquidoPagableAcumulado, 2);
            $validated['saldo_por_pagar'] = round($montoVigenteEfectivo - $liquidoPagableAcumulado, 2);

            // Avance Financiero = (Ejecutado − Amortización + Anticipo) / Vigente × 100
            $validated['avance_financiero'] = $montoVigenteEfectivo > 0
                ? round((($ejecutadoNeto - $amortizacionAcumulada + $anticipoEfectivo) / $montoVigenteEfectivo) * 100, 2)
                : 0;
        }

        // Avance Físico: 100% si tiene fecha real de entrega definitiva
        // (nueva o ya existente); si no, ejecutado/vigente.
        if (array_key_exists('monto_vigente', $validated) || array_key_exists('fecha_entrega_definitiva', $validated)) {
            $montoVigenteParaFisico = $validated['monto_vigente'] ?? $contrato->monto_vigente;
            $fechaEntregaDefEfectiva = array_key_exists('fecha_entrega_definitiva', $validated)
                ? $validated['fecha_entrega_definitiva']
                : $contrato->fecha_entrega_definitiva;

            $validated['avance_fisico'] = !empty($fechaEntregaDefEfectiva)
                ? 100
                : ($montoVigenteParaFisico > 0
                    ? round(((float) $contrato->monto_ejecutado_acumulado / $montoVigenteParaFisico) * 100, 2)
                    : 0);
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
            'monto_ejecutado' => (float) $contratos->sum('monto_ejecutado_acumulado'),
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