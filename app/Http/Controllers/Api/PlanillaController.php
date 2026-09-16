<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContratoProyecto;
use App\Models\PlanillaContrato;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PlanillaController extends Controller
{
    public function index(ContratoProyecto $contrato)
    {
        $planillas = $contrato->planillas()->orderBy('numero')->get();

        $saldoAnticipo = (float) $contrato->anticipo;

        $formateadas = $planillas->map(function ($p) use (&$saldoAnticipo) {
            $saldoAnticipo -= (float) $p->amortizacion;

            $diasDemora = ($p->fecha_desembolso && $p->fecha_aprobacion_fiscal)
                ? Carbon::parse($p->fecha_aprobacion_fiscal)->diffInDays(Carbon::parse($p->fecha_desembolso))
                : null;

            return [
                'id_planilla' => $p->id_planilla,
                'numero' => $p->numero,
                'periodo_desde' => $p->periodo_desde,
                'periodo_hasta' => $p->periodo_hasta,
                'monto_certificado' => (float) $p->monto_certificado,
                'retencion_gcc' => (float) $p->retencion_gcc,
                'dias_atraso' => $p->dias_atraso,
                'multa' => (float) $p->multa,
                'amortizacion' => (float) $p->amortizacion,
                'liquido_pagable' => (float) $p->liquido_pagable,
                'avance_fisico' => $p->avance_fisico,
                'saldo_anticipo_por_amortizar' => round($saldoAnticipo, 2),
                'importe_pagado_sigep' => (float) $p->importe_pagado_sigep,
                'diferencia_lp_f' => round((float) $p->liquido_pagable - (float) $p->importe_pagado_sigep, 2),
                'numero_c31' => $p->numero_c31,
                'monto_c31' => (float) $p->monto_c31,
                'diferencia_sigep_c31' => round((float) $p->importe_pagado_sigep - (float) $p->monto_c31, 2),
                'fecha_aprobacion_fiscal' => $p->fecha_aprobacion_fiscal,
                'fecha_elaboracion_planilla' => $p->fecha_elaboracion_planilla,
                'fecha_desembolso' => $p->fecha_desembolso,
                'dias_demora' => $diasDemora,
            ];
        });

        return response()->json($formateadas);
    }

    public function store(Request $request, ContratoProyecto $contrato)
    {
        $data = $request->validate([
            'periodo_desde'     => 'required|date',
            'periodo_hasta'     => 'required|date|after_or_equal:periodo_desde',
            'monto_certificado' => 'required|numeric|min:0',
            'retencion_gcc'     => 'nullable|numeric|min:0',
            'multa'             => 'nullable|numeric|min:0',
            'importe_pagado_sigep'       => 'required|numeric|min:0',
            'numero_c31'                 => 'required|string|max:100',
            'monto_c31'                  => 'required|numeric|min:0',
            'fecha_aprobacion_fiscal'    => 'required|date',
            'fecha_elaboracion_planilla' => 'required|date',
            'fecha_desembolso'           => 'required|date',
        ]);

        $yaEjecutado = $contrato->planillas()->sum('monto_certificado');
        $disponible = $contrato->monto_vigente - $yaEjecutado;

        if ($data['monto_certificado'] > $disponible) {
            return response()->json([
                'message' => 'El monto certificado supera el saldo disponible del contrato.',
                'errors' => [
                    'monto_certificado' => ["El monto certificado (Bs {$data['monto_certificado']}) supera el saldo disponible (Bs {$disponible})."],
                ],
            ], 422);
        }

        // "Días de atraso" es puramente informativo — se calcula igual que
        // "Días de Demora" (Desembolso − Aprobación Fiscal), pero ya NO
        // tiene ninguna relación con la multa.
        $diasAtraso = 0;
        if (!empty($data['fecha_aprobacion_fiscal']) && !empty($data['fecha_desembolso'])) {
            $diasAtraso = Carbon::parse($data['fecha_aprobacion_fiscal'])
                ->diffInDays(Carbon::parse($data['fecha_desembolso']));
        }

        $retencion = $data['retencion_gcc'] ?? 0;
        // Multa: 100% manual — el usuario decide el monto, sin fórmula ni
        // tope automático de por medio.
        $multa = $data['multa'] ?? 0;

        // D = Importe Ejecutado (A) − Retenciones (B)
        $d = $data['monto_certificado'] - $retencion;

        if (!empty($contrato->anticipo_porcentaje)) {
            $porcentajeAnticipo = $contrato->anticipo_porcentaje / 100;
        } elseif ($contrato->monto_vigente > 0 && $contrato->anticipo > 0) {
            $porcentajeAnticipo = $contrato->anticipo / $contrato->monto_vigente;
        } else {
            $porcentajeAnticipo = 0;
        }

        $amortizacion = $d * $porcentajeAnticipo;

        // Líquido Pagable = D − Amortización − Multa
        $liquido = $d - $amortizacion - $multa;

        $siguienteNumero = ($contrato->planillas()->withTrashed()->max('numero') ?? 0) + 1;

        $planilla = PlanillaContrato::create([
            ...$data,
            'id_contrato'         => $contrato->id_contrato,
            'numero'              => $siguienteNumero,
            'dias_atraso'         => $diasAtraso,
            'retencion_gcc'       => round($retencion, 2),
            'multa'               => round($multa, 2),
            'amortizacion'        => round($amortizacion, 2),
            'liquido_pagable'     => round($liquido, 2),
            'importe_pagado_sigep' => $data['importe_pagado_sigep'] ?? 0,
            'monto_c31'           => $data['monto_c31'] ?? 0,
            'id_usuario_creador'  => $request->user()->id_usuario ?? $request->user()->id,
        ]);

        $this->recalcularContrato($contrato);

        return response()->json($planilla, 201);
    }

    public function update(Request $request, PlanillaContrato $planilla)
    {
        $contrato = $planilla->contrato;

        $data = $request->validate([
            'periodo_desde'     => 'sometimes|required|date',
            'periodo_hasta'     => 'sometimes|required|date|after_or_equal:periodo_desde',
            'monto_certificado' => 'sometimes|required|numeric|min:0',
            'retencion_gcc'     => 'nullable|numeric|min:0',
            'multa'             => 'nullable|numeric|min:0',
            'importe_pagado_sigep'       => 'nullable|numeric|min:0',
            'numero_c31'                 => 'nullable|string|max:100',
            'monto_c31'                  => 'nullable|numeric|min:0',
            'fecha_aprobacion_fiscal'    => 'nullable|date',
            'fecha_elaboracion_planilla' => 'nullable|date',
            'fecha_desembolso'           => 'nullable|date',
        ]);

        $montoCertificadoNuevo = $data['monto_certificado'] ?? (float) $planilla->monto_certificado;
        $yaEjecutadoSinEsta = $contrato->planillas()
            ->where('id_planilla', '!=', $planilla->id_planilla)
            ->sum('monto_certificado');
        $disponible = $contrato->monto_vigente - $yaEjecutadoSinEsta;

        if ($montoCertificadoNuevo > $disponible) {
            return response()->json([
                'message' => 'El monto certificado supera el saldo disponible del contrato.',
                'errors' => [
                    'monto_certificado' => ["El monto certificado (Bs {$montoCertificadoNuevo}) supera el saldo disponible (Bs {$disponible})."],
                ],
            ], 422);
        }

        $fechaAprobacionEfectiva = $data['fecha_aprobacion_fiscal'] ?? $planilla->fecha_aprobacion_fiscal;
        $fechaDesembolsoEfectiva = $data['fecha_desembolso'] ?? $planilla->fecha_desembolso;

        $diasAtraso = 0;
        if (!empty($fechaAprobacionEfectiva) && !empty($fechaDesembolsoEfectiva)) {
            $diasAtraso = Carbon::parse($fechaAprobacionEfectiva)
                ->diffInDays(Carbon::parse($fechaDesembolsoEfectiva));
        }

        $retencion = $data['retencion_gcc'] ?? (float) $planilla->retencion_gcc;
        // Multa: 100% manual — si no viene en el request, se conserva la
        // que ya tenía la planilla (no se recalcula ni se resetea a 0).
        $multa = $data['multa'] ?? (float) $planilla->multa;

        $d = $montoCertificadoNuevo - $retencion;

        if (!empty($contrato->anticipo_porcentaje)) {
            $porcentajeAnticipo = $contrato->anticipo_porcentaje / 100;
        } elseif ($contrato->monto_vigente > 0 && $contrato->anticipo > 0) {
            $porcentajeAnticipo = $contrato->anticipo / $contrato->monto_vigente;
        } else {
            $porcentajeAnticipo = 0;
        }

        $amortizacion = $d * $porcentajeAnticipo;
        $liquido = $d - $amortizacion - $multa;

        $data['dias_atraso']     = $diasAtraso;
        $data['retencion_gcc']   = round($retencion, 2);
        $data['multa']           = round($multa, 2);
        $data['amortizacion']    = round($amortizacion, 2);
        $data['liquido_pagable'] = round($liquido, 2);
        $data['id_usuario_actualizador'] = $request->user()->id_usuario ?? $request->user()->id;

        $planilla->update($data);

        $this->recalcularContrato($contrato);

        return response()->json($planilla);
    }

    public function destroy(PlanillaContrato $planilla)
    {
        $contrato = $planilla->contrato;
        $planilla->delete();
        $this->recalcularContrato($contrato);

        return response()->json(['message' => 'Planilla eliminada']);
    }

    private function recalcularContrato(ContratoProyecto $contrato)
    {
        $planillas = $contrato->planillas()->orderBy('numero')->get();

        $ejecutadoBruto = $planillas->sum('monto_certificado');
        $amortizacion   = $planillas->sum('amortizacion');
        $retencion      = $planillas->sum('retencion_gcc');
        $multas         = $planillas->sum('multa');

        $ejecutadoNeto = $ejecutadoBruto - $retencion;
        $descuentos = $amortizacion + $retencion + $multas;
        $liquidoPagableAcumulado = $ejecutadoNeto + $contrato->anticipo;

        $contrato->update([
            'monto_ejecutado_acumulado' => $ejecutadoNeto,
            'amortizacion_acumulada'    => $amortizacion,
            'retencion_gcc'             => $retencion,
            'multas'                    => $multas,
            'liquido_pagable_acumulado' => $liquidoPagableAcumulado,
            'total_descuentos'          => $descuentos,
            'saldo_por_pagar'           => $contrato->monto_vigente - $liquidoPagableAcumulado,
            'avance_financiero'         => $contrato->monto_vigente > 0
                ? round((($ejecutadoNeto - $amortizacion + $contrato->anticipo) / $contrato->monto_vigente) * 100, 2)
                : 0,
            'avance_fisico' => $contrato->fecha_entrega_definitiva !== null
                ? 100
                : ($contrato->monto_vigente > 0 ? round(($ejecutadoNeto / $contrato->monto_vigente) * 100, 2) : 0),
        ]);
    }
}