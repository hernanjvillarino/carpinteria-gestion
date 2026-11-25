<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DesignFileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\Taller\ConsumptionController;
use App\Http\Controllers\Taller\WorkshopController;
use App\Http\Controllers\WorkOrderPhotoController;
use App\Http\Controllers\WorkOrderUpdateController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home'); // sitio público: etapa 5

Route::middleware('guest')->group(function () {
    Route::get('/ingresar', [LoginController::class, 'create'])->name('login');
    Route::post('/ingresar', [LoginController::class, 'store'])->middleware('throttle:20,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/salir', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/panel', HomeController::class)->name('panel');

    Route::post('/trabajos/{workOrder}/avances', [WorkOrderUpdateController::class, 'store'])->name('trabajos.avances.store');
    Route::post('/trabajos/{workOrder}/consumos', [ConsumptionController::class, 'store'])->name('trabajos.consumos.store');
    Route::get('/fotos/{photo}', [WorkOrderPhotoController::class, 'show'])->name('fotos.show');
    Route::get('/disenos/{design}/imagen', [DesignFileController::class, 'preview'])->name('disenos.preview');
    Route::get('/disenos/{design}/sketchup', [DesignFileController::class, 'skp'])->name('disenos.skp');

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');
        Route::resource('trabajos', Admin\WorkOrderController::class)->parameters(['trabajos' => 'trabajo']);
        Route::resource('clientes', Admin\ClientController::class)->parameters(['clientes' => 'cliente']);
        Route::resource('barrios', Admin\GatedCommunityController::class)->except('show')->parameters(['barrios' => 'barrio']);
        Route::resource('usuarios', Admin\UserController::class)->except(['show', 'destroy'])->parameters(['usuarios' => 'usuario']);
        Route::post('/trabajos/{trabajo}/disenos', [Admin\DesignController::class, 'store'])->name('disenos.store');
        Route::post('/disenos/{design}/enviar', [Admin\DesignController::class, 'send'])->name('disenos.send');
        Route::delete('/disenos/{design}', [Admin\DesignController::class, 'destroy'])->name('disenos.destroy');
        Route::get('/stock/proveedores', [Admin\SupplierController::class, 'index'])->name('proveedores.index');
        Route::post('/stock/proveedores', [Admin\SupplierController::class, 'store'])->name('proveedores.store');
        Route::put('/stock/proveedores/{proveedor}', [Admin\SupplierController::class, 'update'])->name('proveedores.update');
        Route::post('/stock/categorias', [Admin\MaterialController::class, 'storeCategory'])->name('stock.categorias.store');
        Route::post('/stock/colores', [Admin\MaterialController::class, 'storeColor'])->name('stock.colores.store');
        Route::resource('stock', Admin\MaterialController::class)->except('destroy')->parameters(['stock' => 'material']);
        Route::post('/stock/{material}/movimientos', [Admin\MaterialController::class, 'move'])->name('stock.move');

        Route::post('/trabajos/{trabajo}/presupuestos', [Admin\QuoteController::class, 'store'])->name('presupuestos.store');
        Route::get('/presupuestos/{quote}', [Admin\QuoteController::class, 'edit'])->name('presupuestos.edit');
        Route::put('/presupuestos/{quote}', [Admin\QuoteController::class, 'update'])->name('presupuestos.update');
        Route::delete('/presupuestos/{quote}', [Admin\QuoteController::class, 'destroy'])->name('presupuestos.destroy');
        Route::post('/presupuestos/{quote}/items', [Admin\QuoteController::class, 'addItem'])->name('presupuestos.items.store');
        Route::delete('/presupuestos/{quote}/items/{item}', [Admin\QuoteController::class, 'removeItem'])->name('presupuestos.items.destroy');
        Route::post('/presupuestos/{quote}/enviar', [Admin\QuoteController::class, 'send'])->name('presupuestos.send');
        Route::get('/presupuestos/{quote}/pdf', [Admin\QuoteController::class, 'pdf'])->name('presupuestos.pdf');
        Route::patch('/fotos/{photo}/visibilidad', [WorkOrderPhotoController::class, 'toggleVisibility'])->name('fotos.visibilidad');
    });

    Route::prefix('taller')->name('taller.')->middleware('role:carpintero')->group(function () {
        Route::get('/', [WorkshopController::class, 'index'])->name('index');
        Route::get('/trabajos/{trabajo}', [WorkshopController::class, 'show'])->name('trabajos.show');
    });

    Route::prefix('mi-cuenta')->name('portal.')->middleware('role:cliente')->group(function () {
        Route::get('/', [PortalController::class, 'index'])->name('index');
        Route::get('/pedidos/{trabajo}', [PortalController::class, 'show'])->name('trabajos.show');
        Route::post('/disenos/{design}/respuesta', [PortalController::class, 'answerDesign'])->name('disenos.answer');
        Route::post('/presupuestos/{quote}/respuesta', [PortalController::class, 'answerQuote'])->name('presupuestos.answer');
        Route::get('/presupuestos/{quote}/pdf', [PortalController::class, 'quotePdf'])->name('presupuestos.pdf');
    });
});
