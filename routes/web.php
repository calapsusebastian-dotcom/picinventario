<?php

use App\Support\InventoryStages;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::view('inventario', 'inventario')
    ->middleware(['auth', 'verified'])
    ->name('inventario');

Route::get('inventario/{stage}', fn (string $stage) => view('inventario-stage', ['stage' => $stage]))
    ->whereIn('stage', InventoryStages::ORDER)
    ->middleware(['auth', 'verified'])
    ->name('inventario.stage');

Route::view('trilla', 'trilla')
    ->middleware(['auth', 'verified'])
    ->name('trilla');

Route::view('despacho', 'despacho')
    ->middleware(['auth', 'verified'])
    ->name('despacho');

Route::view('usuarios', 'usuarios')
    ->middleware(['auth', 'verified'])
    ->name('usuarios');

Route::view('productos', 'productos')
    ->middleware(['auth', 'verified'])
    ->name('productos');

Route::view('clientes', 'clientes')
    ->middleware(['auth', 'verified'])
    ->name('clientes');

Route::view('informes', 'informes')
    ->middleware(['auth', 'verified'])
    ->name('informes');

Route::view('stock', 'stock')
    ->middleware(['auth', 'verified'])
    ->name('stock');

Route::view('bodega', 'bodega')
    ->middleware(['auth', 'verified'])
    ->name('bodega');

require __DIR__.'/auth.php';
