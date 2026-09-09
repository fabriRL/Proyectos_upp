<?php

namespace App\Providers;

use App\Models\Actividad;
use App\Models\BeneficiarioProyecto;
use App\Models\ComponenteProyecto;
use App\Models\ContratoProyecto;
use App\Models\DecretoSupremo;
use App\Models\ModificacionContractual;
use App\Models\ObjetoGastoFinanciero;
use App\Models\PartidaPresupuestaria;
use App\Models\PlanillaContrato;
use App\Models\Problema;
use App\Models\Producto;
use App\Models\Proyecto;
use App\Models\UbicacionProyecto;
use App\Observers\AuditoriaObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Auditoría automática — registra creación, edición, eliminación
        // (lógica y permanente) y restauración de estos modelos en
        // registros_auditoria, sin tocar ningún controller.
        Proyecto::observe(AuditoriaObserver::class);
        ContratoProyecto::observe(AuditoriaObserver::class);
        PlanillaContrato::observe(AuditoriaObserver::class);
        ModificacionContractual::observe(AuditoriaObserver::class);
        Problema::observe(AuditoriaObserver::class);
        DecretoSupremo::observe(AuditoriaObserver::class);
        ComponenteProyecto::observe(AuditoriaObserver::class);
        Producto::observe(AuditoriaObserver::class);
        UbicacionProyecto::observe(AuditoriaObserver::class);
        BeneficiarioProyecto::observe(AuditoriaObserver::class);
        Actividad::observe(AuditoriaObserver::class);
        PartidaPresupuestaria::observe(AuditoriaObserver::class);
        ObjetoGastoFinanciero::observe(AuditoriaObserver::class);
    }
}