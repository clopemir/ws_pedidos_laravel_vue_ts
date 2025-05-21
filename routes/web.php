<?php

use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProductoController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [ProductoController::class, 'home'])->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::post('pedidos', [PedidoController::class, 'store'])->name('pedidos.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('productos', ProductoController::class)->except('show');

    Route::get('pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
    Route::get('pedidos/reportes', [PedidoController::class, 'reporteCantidades'])->name('pedidos.reporte');

});



require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
