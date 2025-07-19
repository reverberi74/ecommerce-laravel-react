<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:api')->get('/me', [AuthController::class, 'me']);

Route::middleware('auth:api')->group(function () {
    // Rotte CRUD per i prodotti
    Route::get('/products', [ProductController::class, 'index']);        // lista tutti i prodotti
    Route::post('/products', [ProductController::class, 'store']);       // crea nuovo prodotto
    Route::get('/products/{id}', [ProductController::class, 'show']);    // mostra prodotto singolo
    Route::put('/products/{id}', [ProductController::class, 'update']);  // aggiorna prodotto
    Route::delete('/products/{id}', [ProductController::class, 'destroy']); // elimina prodotto
});
