<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContratoProyecto;
use App\Models\ModificacionContractual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ModificacionContractualController extends Controller
{
    public function index(ContratoProyecto $contrato)
    {
        $modificaciones = $contrato->modificaciones()->orderBy('numero')->get();

        $formateadas = $modificaciones->map(function ($m) {
            return [
                'id_modificacion' => $m->id_modificacion,
                'numero' => $m->numero,
                'tipo_modificacion' => $m->tipo_modificacion,
                'numero_documento_modificatorio' => $m->numero_documento_modificatorio,
                'cite_documento_aprobacion' => $m->cite_documento_aprobacion,
                'fecha_anterior' => $m->fecha_anterior,
                'nueva_fecha_conclusion' => $m->nueva_fecha_conclusion,
                'plazo_modificado_dias' => $m->plazo_modificado_dias,
                'monto_modificacion' => $m->monto_modificacion !== null ? (float) $m->monto_modificacion : null,
                'descripcion' => $m->descripcion,
                'estado_registro_sicoes' => $m->estado_registro_sicoes,
                'fecha_informe_aprobacion' => $m->fecha_informe_aprobacion,
                'fecha_firma_documento' => $m->fecha_firma_documento,
                'estado_documento' => $m->estado_documento,
                'archivo_pdf_nombre_original' => $m->archivo_pdf_nombre_original,
                'archivo_pdf_url' => $m->archivo_pdf_path ? asset('storage/' . $m->archivo_pdf_path) : null,
            ];
        });

        return response()->json($formateadas);
    }

    public function store(Request $request, ContratoProyecto $contrato)
    {
        $data = $request->validate([
            'tipo_modificacion' => 'required|string|max:150',
            'numero_documento_modificatorio' => 'nullable|string|max:150',
            'cite_documento_aprobacion' => 'nullable|string|max:150',
            'nueva_fecha_conclusion' => 'nullable|date',
            'monto_modificacion' => 'nullable|numeric',
            'descripcion' => 'nullable|string',
            'estado_registro_sicoes' => 'nullable|string|max:50',
            'fecha_informe_aprobacion' => 'nullable|date',
            'fecha_firma_documento' => 'nullable|date',
            'estado_documento' => 'nullable|string|max:50',
            'archivo_pdf' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $siguienteNumero = ($contrato->modificaciones()->withTrashed()->max('numero') ?? 0) + 1;

        $fechaAnterior = $contrato->fecha_conclusion_prevista;

        $plazoModificado = 0;
        if (!empty($data['nueva_fecha_conclusion']) && $fechaAnterior) {
            $tsAnterior = strtotime($fechaAnterior->format('Y-m-d'));
            $tsNueva = strtotime($data['nueva_fecha_conclusion']);
            $plazoModificado = (int) round(($tsNueva - $tsAnterior) / 86400);
        }

        $archivoPath = null;
        $archivoNombreOriginal = null;

        if ($request->hasFile('archivo_pdf')) {
            $archivo = $request->file('archivo_pdf');
            $archivoPath = $archivo->store('modificaciones', 'public');
            $archivoNombreOriginal = $archivo->getClientOriginalName();
        }

        $modificacion = ModificacionContractual::create([
            ...$data,
            'id_contrato' => $contrato->id_contrato,
            'numero' => $siguienteNumero,
            'fecha_anterior' => $fechaAnterior?->format('Y-m-d'),
            'plazo_modificado_dias' => $plazoModificado,
            'archivo_pdf_path' => $archivoPath,
            'archivo_pdf_nombre_original' => $archivoNombreOriginal,
            'id_usuario_creador' => $request->user()->id_usuario ?? $request->user()->id,
        ]);

        if (!empty($data['nueva_fecha_conclusion'])) {
            $contrato->update([
                'fecha_conclusion_prevista' => $data['nueva_fecha_conclusion'],
                'id_usuario_actualizador' => $request->user()->id_usuario ?? $request->user()->id,
            ]);
        }

        return response()->json($modificacion, 201);
    }

    public function update(Request $request, ModificacionContractual $modificacion)
    {
        $data = $request->validate([
            'tipo_modificacion' => 'sometimes|required|string|max:150',
            'numero_documento_modificatorio' => 'nullable|string|max:150',
            'cite_documento_aprobacion' => 'nullable|string|max:150',
            'nueva_fecha_conclusion' => 'nullable|date',
            'monto_modificacion' => 'nullable|numeric',
            'descripcion' => 'nullable|string',
            'estado_registro_sicoes' => 'nullable|string|max:50',
            'fecha_informe_aprobacion' => 'nullable|date',
            'fecha_firma_documento' => 'nullable|date',
            'estado_documento' => 'nullable|string|max:50',
            'archivo_pdf' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $contrato = $modificacion->contrato;

        // Regla de negocio: solo se puede cambiar la fecha si esta es la
        // ÚLTIMA modificación del contrato — editar una fecha intermedia
        // rompería el historial de todas las modificaciones posteriores
        // (su "fecha_anterior" quedaría desincronizada del cambio).
        $numeroMasReciente = $contrato->modificaciones()->max('numero');
        $esLaUltima = $modificacion->numero === $numeroMasReciente;

        if (array_key_exists('nueva_fecha_conclusion', $data)
            && $data['nueva_fecha_conclusion'] !== $modificacion->nueva_fecha_conclusion?->format('Y-m-d')
            && !$esLaUltima) {
            return response()->json([
                'message' => 'No se puede cambiar la fecha de una modificación que no es la más reciente del contrato.',
                'errors' => [
                    'nueva_fecha_conclusion' => ['Solo la modificación más reciente puede cambiar de fecha, para no romper el historial de las modificaciones posteriores.'],
                ],
            ], 422);
        }

        // Si sí se permite el cambio de fecha (es la última), se recalcula
        // el plazo de ESTA modificación usando su propia fecha_anterior
        // (que nunca se toca), y se actualiza la fecha vigente del contrato.
        if (array_key_exists('nueva_fecha_conclusion', $data) && !empty($data['nueva_fecha_conclusion']) && $esLaUltima) {
            if ($modificacion->fecha_anterior) {
                $tsAnterior = strtotime($modificacion->fecha_anterior->format('Y-m-d'));
                $tsNueva = strtotime($data['nueva_fecha_conclusion']);
                $data['plazo_modificado_dias'] = (int) round(($tsNueva - $tsAnterior) / 86400);
            }

            $contrato->update([
                'fecha_conclusion_prevista' => $data['nueva_fecha_conclusion'],
                'id_usuario_actualizador' => $request->user()->id_usuario ?? $request->user()->id,
            ]);
        }

        if ($request->hasFile('archivo_pdf')) {
            if ($modificacion->archivo_pdf_path) {
                Storage::disk('public')->delete($modificacion->archivo_pdf_path);
            }
            $archivo = $request->file('archivo_pdf');
            $data['archivo_pdf_path'] = $archivo->store('modificaciones', 'public');
            $data['archivo_pdf_nombre_original'] = $archivo->getClientOriginalName();
        }
        unset($data['archivo_pdf']);

        $data['id_usuario_actualizador'] = $request->user()->id_usuario ?? $request->user()->id;

        $modificacion->update($data);

        return response()->json($modificacion);
    }

    public function destroy(ModificacionContractual $modificacion)
    {
        if ($modificacion->archivo_pdf_path) {
            Storage::disk('public')->delete($modificacion->archivo_pdf_path);
        }
        $modificacion->delete();
        return response()->json(['message' => 'Modificación eliminada']);
    }
}