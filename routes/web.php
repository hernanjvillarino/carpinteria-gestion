<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Portal\PortalController;
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
    Route::get('/fotos/{photo}', [WorkOrderPhotoController::class, 'show'])->name('fotos.show');

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');
        Route::resource('trabajos', Admin\WorkOrderController::class)->parameters(['trabajos' => 'trabajo']);
        Route::resource('clientes', Admin\ClientController::class)->parameters(['clientes' => 'cliente']);
        Route::resource('barrios', Admin\GatedCommunityController::class)->except('show')->parameters(['barrios' => 'barrio']);
        Route::resource('usuarios', Admin\UserController::class)->except(['show', 'destroy'])->parameters(['usuarios' => 'usuario']);
        Route::patch('/fotos/{photo}/visibilidad', [WorkOrderPhotoController::class, 'toggleVisibility'])->name('fotos.visibilidad');
    });

    Route::prefix('taller')->name('taller.')->middleware('role:carpintero')->group(function () {
        Route::get('/', [WorkshopController::class, 'index'])->name('index');
        Route::get('/trabajos/{trabajo}', [WorkshopController::class, 'show'])->name('trabajos.show');
    });

    Route::prefix('mi-cuenta')->name('portal.')->middleware('role:cliente')->group(function () {
        Route::get('/', [PortalController::class, 'index'])->name('index');
    });
});
