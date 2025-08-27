<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\EventosPublicController;
use App\Http\Controllers\MapaPublicoController;
use App\Http\Controllers\Admin\EventoController;
use App\Http\Controllers\Admin\MapaEditorController;
use App\Http\Controllers\ReservasPublicController;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');
 
Route::get('/contacto', function () {
    return view('contacto');
})->name('contacto');


// Público: listado de eventos
Route::get('/eventos', [EventosPublicController::class, 'index'])->name('eventos.index');
Route::get('/eventos/{evento}/mapa', [MapaPublicoController::class, 'show'])->name('mapa.publico');

// Flujo de reserva pública
Route::post('/reservas/start',   [ReservasPublicController::class, 'start'])->name('reservas.start');
Route::get('/reservas/promos',   [ReservasPublicController::class, 'promos'])->name('reservas.promos');
Route::post('/reservas/promos',  [ReservasPublicController::class, 'promosStore'])->name('reservas.promos.store');
Route::get('/reservas/checkout', [ReservasPublicController::class, 'checkout'])->name('reservas.checkout');
Route::post('/reservas/checkout',[ReservasPublicController::class, 'checkoutStore'])->name('reservas.checkout.store');
Route::get('/reservas/ok',       [ReservasPublicController::class, 'ok'])->name('reservas.ok');

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
    // RUTA MAPA
    // RUTA PROMOCIONES 
    // RUTA RESERVAS
    // RUTA PUBLICIDAD
    // Otras rutas necesarias
});


require __DIR__.'/auth.php';
