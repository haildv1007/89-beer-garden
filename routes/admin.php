<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\EmployeeAccountController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeeStatusController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RestaurantTableController;
use App\Http\Controllers\Admin\RoleController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'admin.home')->name('home');

Route::resource('categories', CategoryController::class)
    ->middleware('can:category.manage');

Route::get('products/create', [ProductController::class, 'create'])
    ->middleware(['can:product.manage', 'can:product.update-price'])
    ->name('products.create');
Route::post('products', [ProductController::class, 'store'])
    ->middleware(['can:product.manage', 'can:product.update-price'])
    ->name('products.store');
Route::resource('products', ProductController::class)
    ->except(['create', 'store'])
    ->middleware('can:product.manage');
Route::patch('products/{product}/price', [ProductController::class, 'updatePrice'])
    ->middleware(['can:product.manage', 'can:product.update-price'])
    ->name('products.price.update');

Route::resource('employees', EmployeeController::class)
    ->only(['index', 'show', 'create', 'store', 'edit', 'update'])
    ->middleware('can:employee.manage');
Route::post('employees/{employee}/account', [EmployeeAccountController::class, 'store'])
    ->middleware(['can:employee.manage', 'can:permission.assign'])
    ->name('employees.accounts.store');
Route::patch('employees/{employee}/role', [EmployeeAccountController::class, 'update'])
    ->middleware('can:permission.assign')
    ->name('employees.roles.update');
Route::patch('employees/{employee}/disable', [EmployeeStatusController::class, 'destroy'])
    ->middleware('can:employee.disable')
    ->name('employees.disable');

Route::get('roles', [RoleController::class, 'index'])
    ->middleware('can:permission.assign')
    ->name('roles.index');
Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])
    ->middleware('can:permission.assign')
    ->name('roles.permissions.update');

Route::resource('restaurant-tables', RestaurantTableController::class)
    ->middleware('can:restaurant-table.manage');

Route::resource('customers', CustomerController::class)
    ->only(['index', 'show'])
    ->middleware('can:customer.view');
