<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DesignFileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\Taller\ConsumptionController;
use App\Http\Controllers\Taller\WorkshopController;
use App\Http\Controllers\WorkOrderPhotoController;
use App\Http\Controllers\WorkOrderTaskController;
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
    Route::patch('/trabajos/{workOrder}/tareas/{task}', [WorkOrderTaskController::class, 'toggle'])->name('trabajos.tareas.toggle');
    Route::post('/trabajos/{workOrder}/tareas', [WorkOrderTaskController::class, 'store'])->name('trabajos.tareas.store');
    Route::delete('/trabajos/{workOrder}/tareas/{task}', [WorkOrderTaskController::class, 'destroy'])->name('trabajos.tareas.destroy');
    Route::post('/trabajos/{workOrder}/consumos', [ConsumptionController::class, 'store'])->name('trabajos.consumos.store');
    Route::get('/avisos', [NotificationController::class, 'index'])->name('avisos.index');
    Route::get('/avisos/sin-leer', [NotificationController::class, 'count'])->name('avisos.count');
    Route::post('/avisos/leer-todo', [NotificationController::class, 'readAll'])->name('avisos.read-all');
    Route::get('/avisos/{id}', [NotificationController::class, 'open'])->name('avisos.open');
    Route::post('/push/suscripcion', [PushSubscriptionController::class, 'store'])->name('push.store');
    Route::delete('/push/suscripcion', [PushSubscriptionController::class, 'destroy'])->name('push.destroy');
    Route::get('/calificaciones/fotos/{photo}', [RatingController::class, 'photo'])->name('calificaciones.fotos');
    Route::get('/fotos/{photo}', [WorkOrderPhotoController::class, 'show'])->name('fotos.show');
    Route::get('/disenos/{design}/imagen', [DesignFileController::class, 'preview'])->name('disenos.preview');
    Route::get('/disenos/{design}/sketchup', [DesignFileController::class, 'skp'])->name('disenos.skp');

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/actividad', [Admin\DashboardController::class, 'activity'])->name('dashboard.activity');
        Route::get('/indicaciones', [Admin\InstructionTemplateController::class, 'index'])->name('indicaciones.index');
        Route::post('/indicaciones', [Admin\InstructionTemplateController::class, 'store'])->name('indicaciones.store');
        Route::put('/indicaciones/{indicacion}', [Admin\InstructionTemplateController::class, 'update'])->name('indicaciones.update');
        Route::delete('/indicaciones/{indicacion}', [Admin\InstructionTemplateController::class, 'destroy'])->name('indicaciones.destroy');
        Route::resource('trabajos', Admin\WorkOrderController::class)->parameters(['trabajos' => 'trabajo']);
        Route::resource('clientes', Admin\ClientController::class)->parameters(['clientes' => 'cliente']);
        Route::resource('barrios', Admin\GatedCommunityController::class)->parameters(['barrios' => 'barrio']);
        Route::resource('personal', Admin\EmployeeController::class)->parameters(['personal' => 'empleado']);
        Route::post('/personal/{empleado}/documentos', [Admin\EmployeeDocumentController::class, 'store'])->name('personal.documentos.store');
        Route::get('/documentos/{document}', [Admin\EmployeeDocumentController::class, 'show'])->name('documentos.show');
        Route::delete('/documentos/{document}', [Admin\EmployeeDocumentController::class, 'destroy'])->name('documentos.destroy');
        Route::get('/documentacion', [Admin\DocumentTypeController::class, 'index'])->name('documentacion.index');
        Route::post('/documentacion', [Admin\DocumentTypeController::class, 'store'])->name('documentacion.store');
        Route::put('/documentacion/{tipo}', [Admin\DocumentTypeController::class, 'update'])->name('documentacion.update');
        Route::delete('/documentacion/{tipo}', [Admin\DocumentTypeController::class, 'destroy'])->name('documentacion.destroy');
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
        Route::post('/pedidos/{trabajo}/calificacion', [RatingController::class, 'store'])->name('trabajos.calificacion');
    });
});
