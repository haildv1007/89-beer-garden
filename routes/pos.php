<?php

use App\Http\Controllers\POS\TableController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pos.home')->name('home');
Route::get('tables', [TableController::class, 'index'])
    ->middleware('can:table.view')
    ->name('tables.index');
Route::patch('tables/{restaurantTable}/available', [TableController::class, 'markAvailable'])
    ->middleware('can:table.operate')
    ->name('tables.mark-available');
