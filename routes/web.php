<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\EventosPublicController;
use App\Http\Controllers\MapaPublicoController;
use App\Http\Controllers\Admin\EventoController;
use App\Http\Controllers\Admin\MapaEditorController;
use App\Http\Controllers\ReservasPublicController;
use App\Http\Controllers\Admin\ZonasPrecioController;
use App\Http\Controllers\Admin\ReservasAdminController;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');
 
Route::get('/contacto', function () {
    return view('contacto');
})->name('contacto');


// Público: listado de eventos
Route::get('/eventos', [EventosPublicController::class, 'index'])->name('eventos.index');
Route::get('/eventos/{evento}/mapa', [MapaPublicoController::class, 'show'])->name('mapa.publico');
Route::get('/eventos/{evento}/mapa/status', [MapaPublicoController::class, 'status'])->name('mapa.publico.status');

// Flujo de reserva pública
Route::post('/reservas/start',   [ReservasPublicController::class, 'start'])->name('reservas.start');
Route::get('/reservas/promos',   [ReservasPublicController::class, 'promos'])->name('reservas.promos');
Route::get('/reservas/ok',       [ReservasPublicController::class, 'ok'])->name('reservas.ok');
Route::get('/reservas/continuar',[ReservasPublicController::class, 'resume'])->name('reservas.resume');

Route::get('/reservas/checkout', [ReservasPublicController::class, 'checkout'])->name('reservas.checkout');
Route::post('/reservas/checkout', [ReservasPublicController::class, 'store'])->name('reservas.checkout.store');
Route::post('/reservas/cancel', [ReservasPublicController::class, 'cancel'])->name('reservas.cancel');
Route::post('/reservas/promos',   [ReservasPublicController::class, 'promosStore'])->name('reservas.promos.store');
Route::post('/reservas/checkout', [ReservasPublicController::class, 'checkoutStore'])->name('reservas.checkout.store');

Route::get ('/reservas/resume',   [ReservasPublicController::class, 'resume'])->name('reservas.resume');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Panel
Route::middleware(['auth', 'verified'])->prefix('dashboard')->name('dashboard.')->group(function () {
    // CRUD eventos
    Route::resource('eventos', EventoController::class);
    // Editor de mapa del evento
    Route::get('eventos/{evento}/mapa', [MapaEditorController::class, 'edit'])->name('eventos.mapa');
    Route::post('eventos/{evento}/mapa/mesas/save', [MapaEditorController::class, 'save'])->name('eventos.mapa.mesas.save'); 
    // Zonas (JSON)
    Route::get('eventos/{evento}/zonas', [MapaEditorController::class, 'zonasIndex'])->name('eventos.zonas.index');
    Route::post('eventos/{evento}/zonas', [MapaEditorController::class, 'zonasSave'])->name('eventos.zonas.save');
    // RUTA PROMOCIONES 
    
    Route::get('reservas',       [ReservasAdminController::class, 'index'])->name('reservas.index');
    Route::get('reservas/{id}',  [ReservasAdminController::class, 'show'])->whereNumber('id')->name('reservas.show');

    // RUTA PUBLICIDAD
    // Otras rutas necesarias
});

    Route::prefix('dashboard/eventos/{evento}')->group(function () {       // Editor visual de zonas (página con mapa + drag/resize)
        Route::get('zonas/editor', [ZonasPrecioController::class, 'editor'])->name('dashboard.eventos.zonas.editor');

        // Cargar zonas existentes (JSON)
        Route::get('zonas/data', [ZonasPrecioController::class, 'data'])->name('dashboard.eventos.zonas.data');

        // Guardar TODO (reemplaza las zonas del evento)
        Route::post('zonas/save', [ZonasPrecioController::class, 'saveAll'])->name('dashboard.eventos.zonas.save');
    });


Route::middleware('auth')->prefix('dashboard/eventos/{evento}')
    ->name('dashboard.eventos.')
    ->group(function () {

        // Zonas de precio (página separada)
        Route::get('zonas',            [ZonasPrecioController::class, 'index'])->name('zonas.index');
        Route::get('zonas/create',     [ZonasPrecioController::class, 'create'])->name('zonas.create');
        Route::post('zonas',           [ZonasPrecioController::class, 'store'])->name('zonas.store');
        Route::get('zonas/{zona}/edit',[ZonasPrecioController::class, 'edit'])->name('zonas.edit');
        Route::put('zonas/{zona}',     [ZonasPrecioController::class, 'update'])->name('zonas.update');
        Route::delete('zonas/{zona}',  [ZonasPrecioController::class, 'destroy'])->name('zonas.destroy');
    });
require __DIR__.'/auth.php';
