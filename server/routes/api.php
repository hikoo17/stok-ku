<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\StockInController;
use App\Http\Controllers\Api\V1\StockOutController;
use App\Http\Controllers\Api\V1\StockTransactionController;
use App\Http\Controllers\Api\V1\UnitController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/dashboard', DashboardController::class);

        Route::get('/products/options', [ProductController::class, 'options']);
        Route::post('/products/{product}/restore', [ProductController::class, 'restore']);
        Route::apiResource('products', ProductController::class);

        Route::get('/categories/options', [CategoryController::class, 'options']);
        Route::apiResource('categories', CategoryController::class);

        Route::get('/units/options', [UnitController::class, 'options']);
        Route::apiResource('units', UnitController::class);

        Route::get('/stock-transactions', [StockTransactionController::class, 'index']);
        Route::get('/stock-transactions/{stockTransaction}', [StockTransactionController::class, 'show']);
        Route::post('/stock-in', [StockInController::class, 'store']);
        Route::post('/stock-out', [StockOutController::class, 'store']);

        Route::get('/reports/inventory', [ReportController::class, 'inventory']);
        Route::get('/reports/inventory/export', [ReportController::class, 'exportInventory']);
        Route::get('/reports/stock-movements', [ReportController::class, 'stockMovements']);
        Route::get('/reports/stock-movements/export', [ReportController::class, 'exportStockMovements']);
        Route::get('/reports/low-stock', [ReportController::class, 'lowStock']);
        Route::get('/reports/low-stock/export', [ReportController::class, 'exportLowStock']);
    });
});
