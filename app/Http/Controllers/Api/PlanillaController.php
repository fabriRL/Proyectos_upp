<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContratoProyecto;
use App\Models\PlanillaContrato;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PlanillaController extends Controller
{
    // 1‰ diario de multa por atraso, con tope legal de 10% del monto vigente.
    private const PORCENTAJE_MULTA_DIARIA = 0.001;
    private const TOPE_MULTA = 0.10;

    public function index(ContratoProyecto $contrato)
    {
        $planillas = $contrato->planillas()->orderBy('numero')->get();

        // "Saldo de Anticipo por Amortizar" es un acumulado corriendo:
        // arranca en el anticipo total del contrato y cada planilla, en
        // orden, le resta su propia amortización — igual que el Excel
        // (cada fila resta de lo que dejó la fila anterior).
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
            'dias_atraso'       => 'nullable|integer|min:0',
            'avance_fisico'     => 'nullable|numeric|min:0|max:100',
            'importe_pagado_sigep'       => 'nullable|numeric|min:0',
            'numero_c31'                 => 'nullable|string|max:100',
            'monto_c31'                  => 'nullable|numeric|min:0',
            'fecha_aprobacion_fiscal'    => 'nullable|date',
            'fecha_elaboracion_planilla' => 'nullable|date',
            'fecha_desembolso'           => 'nullable|date',
        ]);

        // --- Validación de negocio: no se puede certificar más de lo que queda del contrato ---
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

        $diasAtraso = $data['dias_atraso'] ?? 0;
        $retencion  = $data['retencion_gcc'] ?? 0;

        // D = Importe Ejecutado (A) − Retenciones (B)
        $d = $data['monto_certificado'] - $retencion;

        // Amortización = D × (anticipo del contrato / monto vigente del contrato)
        $amortizacion = $contrato->monto_vigente > 0
            ? $d * ($contrato->anticipo / $contrato->monto_vigente)
            : 0;

        $multa = min(
            $contrato->monto_vigente * self::PORCENTAJE_MULTA_DIARIA * $diasAtraso,
            $contrato->monto_vigente * self::TOPE_MULTA
        );

        // Líquido Pagable = D − Amortización − Multa
        $liquido = $d - $amortizacion - $multa;

        $siguienteNumero = ($contrato->planillas()->max('numero') ?? 0) + 1;

        $planilla = PlanillaContrato::create([
            ...$data,
            'id_contrato'        => $contrato->id_contrato,
            'numero'              => $siguienteNumero,
            'retencion_gcc'       => round($retencion, 2),
            'amortizacion'        => round($amortizacion, 2),
            'multa'               => round($multa, 2),
            'liquido_pagable'     => round($liquido, 2),
            'importe_pagado_sigep' => $data['importe_pagado_sigep'] ?? 0,
            'monto_c31'           => $data['monto_c31'] ?? 0,
            'id_usuario_creador'  => $request->user()->id_usuario ?? $request->user()->id,
        ]);

        $this->recalcularContrato($contrato);

        return response()->json($planilla, 201);
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

        $ejecutado    = $planillas->sum('monto_certificado');
        $amortizacion = $planillas->sum('amortizacion');
        $retencion    = $planillas->sum('retencion_gcc');
        $multas       = $planillas->sum('multa');
        $descuentos   = $amortizacion + $retencion + $multas;

        $ultimaPlanilla = $planillas->last();

        $contrato->update([
            'monto_ejecutado_acumulado' => $ejecutado,
            'amortizacion_acumulada'    => $amortizacion,
            'retencion_gcc'             => $retencion,
            'multas'                    => $multas,
            'liquido_pagable_acumulado' => $ejecutado + $contrato->anticipo,
            'total_descuentos'          => $descuentos,
            'saldo_por_pagar'           => $contrato->monto_vigente - $ejecutado,
            'avance_financiero'         => $contrato->monto_vigente > 0
                ? round(($ejecutado / $contrato->monto_vigente) * 100, 2)
                : 0,
            'avance_fisico' => $ultimaPlanilla?->avance_fisico ?? $contrato->avance_fisico,
        ]);
    }
}