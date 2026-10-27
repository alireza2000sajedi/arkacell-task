<?php

use App\Http\Controllers\Api\StockLevelController;
use App\Http\Controllers\Api\StockMovementController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;

Route::get('/products/{sku}/history', [ProductController::class, 'history']);
Route::get('/stock-levels', [StockLevelController::class, 'index']);
Route::post('/stock-movements', [StockMovementController::class, 'store']);
