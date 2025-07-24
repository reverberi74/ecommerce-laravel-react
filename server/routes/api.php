<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use Illuminate\Http\Request;
use App\Services\ImageUploadService;
use App\Http\Controllers\Api\CartController;

// 🔐 Autenticazione
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// 👤 Profilo utente autenticato
Route::middleware('auth:api')->get('/me', [AuthController::class, 'me']);

// 📦 Rotte pubbliche o comuni
Route::middleware('auth:api')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
});

// 🛒 Rotte Carrello (guest + auth)
Route::prefix('cart')->group(function () {
    // 📥 Visualizza carrello corrente (guest o user)
    Route::get('/', [CartController::class, 'getCart']);

    // ➕ Aggiungi un prodotto
    Route::post('/add', [CartController::class, 'addToCart']);

    // ✏️ Aggiorna quantità item (protetto)
    Route::put('/item/{id}', [CartController::class, 'updateItem'])->middleware('checkCart');

    // ❌ Rimuovi item (protetto)
    Route::delete('/item/{id}', [CartController::class, 'removeItem'])->middleware('checkCart');

    // 🧹 Svuota tutto il carrello
    Route::delete('/clear', [CartController::class, 'clearCart']);

    // 🔁 Merge carrello guest → user (richiede login)
    Route::middleware('auth:api')->post('/merge', [CartController::class, 'mergeGuestCart']);
});


// 🔐 Rotte solo per admin
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    // 📦 CRUD Prodotti
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']); // Solo dati
    Route::post('/products/{id}/image', [ProductController::class, 'updateImage']); // Solo immagine
    Route::post('/products/{id}/update', [ProductController::class, 'updateWithFile']); // Tutto
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // 🗂️ CRUD Categorie
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']); // Solo dati
    Route::post('/categories/{id}/image', [CategoryController::class, 'updateImage']); // Solo immagine
    Route::post('/categories/{id}/update', [CategoryController::class, 'updateWithFile']); // Tutto
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
});

Route::post('/test-image-upload', function (Request $request, ImageUploadService $uploader) {
    $request->validate([
        'image' => 'required|image|max:2048', // max 2MB
        'folder' => 'required|string',
    ]);

    $result = $uploader->upload($request->file('image'), $request->folder);

    return response()->json([
        'message' => 'Upload riuscito!',
        'data' => $result,
    ]);
});
