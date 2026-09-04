<?php

namespace Database\Seeders;

use App\Models\ContratoProyecto;
use App\Models\Proyecto;
use Illuminate\Database\Seeder;

class ContratoProyectoSeeder extends Seeder
{
    public function run(): void
    {
        $proyecto = Proyecto::where('codigo', 'PRY-07')->first();

        if (!$proyecto) {
            $this->command->error('No se encontró el proyecto con código PRY-07. Aborta el seeder.');
            return;
        }

        // Limpia contratos previos de este proyecto para evitar duplicados al re-sembrar
        ContratoProyecto::where('id_proyecto', $proyecto->id_proyecto)->delete();

        $contratos = [
            [
                'numero' => 1,
                'tipo_contrato' => 'Paquete I',
                'contratista' => 'Empresa Morales Larrazabal M.L.',
                'monto_vigente' => 50905840,
                'anticipo' => 10181168,
                'amortizacion_acumulada' => 7489475,
                'monto_ejecutado_acumulado' => 37447374,
                'liquido_pagable_acumulado' => 47628542,
                'multas' => 15000,
                'retencion_gcc' => 0,
                'total_descuentos' => 15000,
                'saldo_por_pagar' => 3277298,
                'estado_contractual' => 'Vencido',
                'fecha_conclusion_prevista' => '2025-11-21',
                'fecha_entrega_provisional' => null,
                'fecha_entrega_definitiva' => null,
                'avance_fisico' => 73.60,
                'avance_financiero' => 78.80,
                'estado_fisico' => 'Paralizado',
            ],
            [
                'numero' => 2,
                'tipo_contrato' => 'Paquete II',
                'contratista' => 'Asoc. Accidental Matadero Beni',
                'monto_vigente' => 78138044,
                'anticipo' => 0,
                'amortizacion_acumulada' => 0,
                'monto_ejecutado_acumulado' => 63619721,
                'liquido_pagable_acumulado' => 63619721,
                'multas' => 15000,
                'retencion_gcc' => 0,
                'total_descuentos' => 15000,
                'saldo_por_pagar' => 1451824,
                'estado_contractual' => 'Cerrado',
                'fecha_conclusion_prevista' => '2025-06-04',
                'fecha_entrega_provisional' => '2025-06-04',
                'fecha_entrega_definitiva' => '2025-07-18',
                'avance_fisico' => 100.00,
                'avance_financiero' => 81.40,
                'estado_fisico' => 'Concluido',
            ],
            [
                'numero' => 3,
                'tipo_contrato' => 'Vías y accesos',
                'contratista' => 'Vicstar Ingeniería S.R.L.',
                'monto_vigente' => 13664335,
                'anticipo' => 0,
                'amortizacion_acumulada' => 0,
                'monto_ejecutado_acumulado' => 8816203,
                'liquido_pagable_acumulado' => 8816203,
                'multas' => 0,
                'retencion_gcc' => 0,
                'total_descuentos' => 0,
                'saldo_por_pagar' => 4848131,
                'estado_contractual' => 'Cerrado',
                'fecha_conclusion_prevista' => '2025-11-14',
                'fecha_entrega_provisional' => '2025-11-14',
                'fecha_entrega_definitiva' => '2025-12-04',
                'avance_fisico' => 100.00,
                'avance_financiero' => 64.50,
                'estado_fisico' => 'Concluido',
            ],
            [
                'numero' => 4,
                'tipo_contrato' => 'Paquete III',
                'contratista' => 'Empresa Latigidconst SRL.',
                'monto_vigente' => 40855317,
                'anticipo' => 6923000,
                'amortizacion_acumulada' => 5355462,
                'monto_ejecutado_acumulado' => 3065194,
                'liquido_pagable_acumulado' => 37873951,
                'multas' => 15000,
                'retencion_gcc' => 0,
                'total_descuentos' => 15000,
                'saldo_por_pagar' => 3277238,
                'estado_contractual' => 'Vencido',
                'fecha_conclusion_prevista' => '2025-12-11',
                'fecha_entrega_provisional' => null,
                'fecha_entrega_definitiva' => null,
                'avance_fisico' => 75.00,
                'avance_financiero' => 78.90,
                'estado_fisico' => 'Paralizado',
            ],
            [
                'numero' => 5,
                'tipo_contrato' => 'Paquete IV',
                'contratista' => 'Asoc. Accidental Yacaré',
                'monto_vigente' => 34289413,
                'anticipo' => 6857883,
                'amortizacion_acumulada' => 6403356,
                'monto_ejecutado_acumulado' => 32016782,
                'liquido_pagable_acumulado' => 38874665,
                'multas' => 0,
                'retencion_gcc' => 0,
                'total_descuentos' => 0,
                'saldo_por_pagar' => -4585252,
                'estado_contractual' => 'Cerrado',
                'fecha_conclusion_prevista' => '2024-10-23',
                'fecha_entrega_provisional' => '2024-10-23',
                'fecha_entrega_definitiva' => '2025-04-21',
                'avance_fisico' => 100.00,
                'avance_financiero' => 94.70,
                'estado_fisico' => 'Concluido',
            ],
            [
                'numero' => 6,
                'tipo_contrato' => 'Supervisión',
                'contratista' => 'FPS',
                'monto_vigente' => 8294161,
                'anticipo' => 1658832,
                'amortizacion_acumulada' => 1037765,
                'monto_ejecutado_acumulado' => 5188827,
                'liquido_pagable_acumulado' => 6847659,
                'multas' => 0,
                'retencion_gcc' => 363218,
                'total_descuentos' => 363218,
                'saldo_por_pagar' => 1446502,
                'estado_contractual' => 'Vencido',
                'fecha_conclusion_prevista' => '2024-07-23',
                'fecha_entrega_provisional' => null,
                'fecha_entrega_definitiva' => null,
                'avance_fisico' => 62.60,
                'avance_financiero' => 70.00,
                'estado_fisico' => 'Vencido',
            ],
        ];

        foreach ($contratos as $data) {
            $data['id_proyecto'] = $proyecto->id_proyecto;
            ContratoProyecto::create($data);
        }

        $this->command->info('Sembrados ' . count($contratos) . ' contratos para el proyecto ' . $proyecto->codigo);
    }
}