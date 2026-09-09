<?php

namespace App\Observers;

use App\Models\RegistroAuditoria;
use Illuminate\Database\Eloquent\Model;

class AuditoriaObserver
{
    // Nunca se guardan estos campos, aunque el modelo los tenga —
    // protección por defecto contra filtrar contraseñas u otros
    // secretos al log de auditoría.
    private const CAMPOS_SENSIBLES = ['password', 'remember_token', 'token'];

    public function created(Model $model): void
    {
        $this->registrar('creado', $model, null, $this->filtrar($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        // Solo los campos que REALMENTE cambiaron — no el registro completo.
        $cambios = $this->filtrar($model->getChanges());

        // Si lo único que cambió fue el timestamp automático, no hay nada
        // que auditar de verdad.
        $marcaTiempo = $model::UPDATED_AT;
        if ($marcaTiempo) {
            unset($cambios[$marcaTiempo]);
        }
        if (empty($cambios)) {
            return;
        }

        $anteriores = [];
        foreach (array_keys($cambios) as $campo) {
            $anteriores[$campo] = $model->getOriginal($campo);
        }

        $this->registrar('actualizado', $model, $anteriores, $cambios);
    }

    public function deleted(Model $model): void
    {
        // Eloquent dispara el mismo evento 'deleted' tanto para borrado
        // lógico (SoftDeletes) como para forceDelete() — se distinguen
        // consultando isForceDeleting().
        $accion = (method_exists($model, 'isForceDeleting') && $model->isForceDeleting())
            ? 'eliminado_permanente'
            : 'eliminado';

        $this->registrar($accion, $model, $this->filtrar($model->getAttributes()), null);
    }

    public function restored(Model $model): void
    {
        $this->registrar('restaurado', $model, null, $this->filtrar($model->getAttributes()));
    }

    private function filtrar(array $datos): array
    {
        foreach (self::CAMPOS_SENSIBLES as $campo) {
            unset($datos[$campo]);
        }
        return $datos;
    }

    private function registrar(string $accion, Model $model, ?array $anteriores, ?array $nuevos): void
    {
        $request = request();

        // Mismo patrón de resolución de usuario que ya usan todos los
        // controllers de la app: prioriza id_usuario, cae a id de Sanctum.
        $usuarioAutenticado = auth()->user();
        $idUsuario = $usuarioAutenticado?->id_usuario ?? $usuarioAutenticado?->id;

        RegistroAuditoria::create([
            'id_usuario' => $idUsuario,
            'accion' => $accion,
            'tipo_registro' => class_basename($model),
            'id_registro' => $model->getKey(),
            'valores_anteriores' => $anteriores,
            'valores_nuevos' => $nuevos,
            'ruta' => $request?->path(),
            'metodo' => $request?->method(),
            'direccion_ip' => $request?->ip(),
            'agente_usuario' => $request?->userAgent(),
        ]);
    }
}