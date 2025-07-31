<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use Illuminate\Http\Request;
use App\Services\ImageUploadService;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderStatusController;
use App\Http\Controllers\Api\CheckoutOrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\RefundController;
use App\Http\Controllers\Api\WebhookController;

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

// 📦 Ordini (utente autenticato)
Route::prefix('orders')->middleware('auth:api')->group(function () {
    Route::get('/', [OrderController::class, 'index']);         // Lista ordini utente
    Route::get('/{id}', [OrderController::class, 'show']);      // Dettaglio ordine
});

// 🔄 Update stato ordine (solo admin)
Route::prefix('orders')->middleware(['auth:api', 'role:admin'])->group(function () {
    Route::patch('/{id}/status', [OrderStatusController::class, 'update']);
});


// 🧾 Checkout → crea un ordine dal carrello attuale
Route::middleware('auth:api')->post('/checkout', [CheckoutOrderController::class, 'store']);

// 💳 Pagamenti (autenticato)
Route::prefix('payments')->middleware('auth:api')->group(function () {
    // 🧾 Avvia un nuovo pagamento
    Route::post('/initiate', [PaymentController::class, 'initiate']);

    // ✅ Conferma il pagamento (es. dopo inserimento dati gateway)
    Route::post('/confirm', [PaymentController::class, 'confirm']);

    // 🔍 Recupera lo stato di un pagamento
    Route::get('/{id}/status', [PaymentController::class, 'status']);
});

// 💾 Metodi di pagamento salvati (utente autenticato)
Route::prefix('payment-methods')->middleware('auth:api')->group(function () {
    // 📋 Lista dei metodi salvati
    Route::get('/', [PaymentMethodController::class, 'index']);

    // ➕ Salva un nuovo metodo (es. carta o PayPal)
    Route::post('/', [PaymentMethodController::class, 'store']);

    // ❌ Elimina un metodo salvato
    Route::delete('/{id}', [PaymentMethodController::class, 'destroy']);

    // ⭐ Imposta come predefinito
    Route::patch('/{id}/default', [PaymentMethodController::class, 'setDefault']);
});

// 🔁 Rimborsi (solo admin)
Route::prefix('refunds')->middleware(['auth:api', 'role:admin'])->group(function () {
    // 🔄 Esegui un rimborso
    Route::post('/', [RefundController::class, 'store']);

    // 📂 Elenco rimborsi relativi a un pagamento
    Route::get('/payment/{paymentId}', [RefundController::class, 'index']);
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
