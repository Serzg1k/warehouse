<?php

use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

// Main API
Route::get('/orders', [OrderController::class, 'index']);
Route::post('/order', [OrderController::class, 'store']);
Route::get('/orders/{id}', [OrderController::class, 'show']);

Route::get('/inventory/{sku}/movements', [InventoryController::class, 'movements']);

// Fake supplier integration
Route::post('/supplier/reserve', [SupplierController::class, 'reserve']);
Route::get('/supplier/status/{ref}', [SupplierController::class, 'status']);
