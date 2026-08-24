<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('customer.home');
})->name('customer.home');
