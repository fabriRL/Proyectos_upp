<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ModificacionContractualController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProyectoController;
use App\Http\Controllers\Api\ActividadController;
use App\Http\Controllers\Api\ProblemaController;
use App\Http\Controllers\Api\ContratoController;
use App\Http\Controllers\Api\PlanillaController;
use App\Http\Controllers\Api\IndicadorController;
use App\Http\Controllers\Api\DecretoSupremoController;
use App\Http\Controllers\Api\ComponenteController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\CatalogoDecretoSupremoController;
use App\Http\Controllers\Api\ProgramacionFinancieraController;
use App\Http\Controllers\Api\ReporteGeneralController;



Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/proyectos/{proyecto}/resumen-general', [DashboardController::class, 'resumenProyecto']);
    // --- Dashboard ---
    
   Route::get('/dashboard', [DashboardController::class, 'index']);

    // --- Proyectos (ListaProyectos.vue, DatosGenerales.vue, NuevoProyecto.vue, EditarProyecto.vue) ---
    Route::apiResource('proyectos', ProyectoController::class);

    // --- Actividades / Cronograma (Cronograma.vue) ---
    Route::get('/proyectos/{proyecto}/actividades', [ActividadController::class, 'index']);
    Route::post('/proyectos/{proyecto}/actividades', [ActividadController::class, 'store']);
    Route::put('/actividades/{actividad}', [ActividadController::class, 'update']);
    Route::delete('/actividades/{actividad}', [ActividadController::class, 'destroy']);

    // --- Problemas (Problemas.vue) ---
    Route::get('/proyectos/{proyecto}/problemas', [ProblemaController::class, 'index']);
    Route::post('/proyectos/{proyecto}/problemas', [ProblemaController::class, 'store']);
    Route::put('/problemas/{problema}', [ProblemaController::class, 'update']);
    Route::delete('/problemas/{problema}', [ProblemaController::class, 'destroy']);

    // actividades
    Route::get(
        '/proyectos/{proyecto}/actividades',
        [ActividadController::class, 'index']
    );

    Route::post(
        '/proyectos/{proyecto}/actividades',
        [ActividadController::class, 'store']
    );

    Route::put(
        '/actividades/{actividad}',
        [ActividadController::class, 'update']
    );

    Route::delete(
        '/actividades/{actividad}',
        [ActividadController::class, 'destroy']
    );


    // --- Contratos (Contratos.vue) ---
    Route::get('/proyectos/{proyecto}/contratos', [ContratoController::class, 'index']);
    Route::post('/proyectos/{proyecto}/contratos', [ContratoController::class, 'store']);
    Route::put('/contratos/{contrato}', [ContratoController::class, 'update']);
    Route::delete('/contratos/{contrato}', [ContratoController::class, 'destroy']);
    Route::get('/proyectos/{proyecto:codigo}/contratos', [ContratoController::class, 'index']);
    Route::post('/proyectos/{proyecto:codigo}/contratos', [ContratoController::class, 'store']);
    Route::patch('/contratos/{contrato}/activo', [ContratoController::class, 'toggleActivo']);
    // Planillas (Planillas.vue)
    Route::get('/contratos/{contrato}/planillas', [PlanillaController::class, 'index']);
    Route::post('/contratos/{contrato}/planillas', [PlanillaController::class, 'store']);
    Route::delete('/planillas/{planilla}', [PlanillaController::class, 'destroy']);
    Route::put('/planillas/{planilla}', [PlanillaController::class, 'update']);
    
    // indicadores de la pestaña del resumen 
    Route::get('/proyectos/{proyecto}/indicadores', [IndicadorController::class, 'show']);
    //PESTAÑA DEL DECRETO SUPREMO 
    Route::get('/proyectos/{proyecto:codigo}/decretos', [DecretoSupremoController::class, 'index']);
    Route::post('/proyectos/{proyecto:codigo}/decretos', [DecretoSupremoController::class, 'store']);
    Route::put('/decretos/{decreto}', [DecretoSupremoController::class, 'update']);
    Route::delete('/decretos/{decreto}', [DecretoSupremoController::class, 'destroy']);
    //COMPONENTES PROYECTOS
    Route::get('/proyectos/{proyecto:codigo}/componentes', [ComponenteController::class, 'index']);
    Route::post('/proyectos/{proyecto:codigo}/componentes', [ComponenteController::class, 'store']);
    Route::put('/componentes/{componente}', [ComponenteController::class, 'update']);
    Route::delete('/componentes/{componente}', [ComponenteController::class, 'destroy']);
    //PRODUCTOS 
    Route::post('/componentes/{componente}/productos', [ProductoController::class, 'store']);
    Route::put('/productos/{producto}', [ProductoController::class, 'update']);
    Route::delete('/productos/{producto}', [ProductoController::class, 'destroy']);

    // DETALLE DE MODIFICACIONES CONTRACTUAL
    Route::get('/contratos/{contrato}/modificaciones', [ModificacionContractualController::class, 'index']);
    Route::post('/contratos/{contrato}/modificaciones', [ModificacionContractualController::class, 'store']);
    Route::put('/modificaciones/{modificacion}', [ModificacionContractualController::class, 'update']);
    Route::delete('/modificaciones/{modificacion}', [ModificacionContractualController::class, 'destroy']);
    //DECRETOS SUPREMOS
    Route::get('/decretos-supremos', [CatalogoDecretoSupremoController::class, 'index']);
    Route::post('/decretos-supremos', [CatalogoDecretoSupremoController::class, 'store']);
    //PROGRAMACION FINANCIERA 
    Route::get('/proyectos/{proyecto:codigo}/programacion-financiera', [ProgramacionFinancieraController::class, 'index']);
    Route::post('/proyectos/{proyecto:codigo}/partidas', [ProgramacionFinancieraController::class, 'storePartida']);
    Route::put('/partidas/{partida}', [ProgramacionFinancieraController::class, 'updatePartida']);
    Route::delete('/partidas/{partida}', [ProgramacionFinancieraController::class, 'destroyPartida']);
    Route::post('/partidas/{partida}/objetos', [ProgramacionFinancieraController::class, 'storeObjeto']);
    Route::put('/objetos-gasto/{objeto}', [ProgramacionFinancieraController::class, 'updateObjeto']);
    Route::delete('/objetos-gasto/{objeto}', [ProgramacionFinancieraController::class, 'destroyObjeto']);
    //REPORTES GENERALES DE LOS PROYECTOS
    Route::get('/reporte-general', [ReporteGeneralController::class, 'index']);
    Route::get('/reporte-general/pdf', [ReporteGeneralController::class, 'exportarPdf']);
    Route::get('/reporte-general/excel', [ReporteGeneralController::class, 'exportarExcel']);
    Route::get('/reportes-generados', [ReporteGeneralController::class, 'historial']);
    Route::delete('/reportes-generados/{reporte}', [ReporteGeneralController::class, 'destroyHistorial']);



});