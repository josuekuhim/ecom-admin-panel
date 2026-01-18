<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DropController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductVariantController;
use App\Http\Controllers\Api\ProductImageController;
use App\Http\Controllers\Api\DropImageController as ApiDropImageController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\ShippingController;
use App\Http\Controllers\Api\ImageController;

/*
|\-------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Image serving routes (public access)
Route::get('/images/product/{id}', [ImageController::class, 'productImage']);
Route::get('/images/drop/{id}', [ImageController::class, 'dropImage']);

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/clerk', [AuthController::class, 'clerkAuth']);
Route::post('/auth/clerk/verify', [AuthController::class, 'verifyClerkSession']);
Route::post('/auth/google', [\App\Http\Controllers\GoogleAuthController::class, 'handleGoogleAuth']);
Route::post('/shipping/calculate', [ShippingController::class, 'calculate']);

// Public catalog (for storefront)
Route::apiResource('drops', DropController::class)->only(['index', 'show']);
Route::apiResource('products', ProductController::class)->only(['index', 'show']);
Route::apiResource('products.variants', ProductVariantController::class)->shallow()->only(['index', 'show']);
Route::apiResource('products.images', ProductImageController::class)->shallow()->only(['index', 'show']);
Route::apiResource('drops.images', ApiDropImageController::class)->shallow()->only(['index', 'show']);

// Guest cart routes (public)
Route::get('/cart/count', [CartController::class, 'getCount']);
Route::post('/cart/items', [CartController::class, 'addItemOptimistic']);

// Health check endpoints
require_once __DIR__ . '/health.php';

// Webhooks
Route::post('/webhooks/clerk', [\App\Http\Controllers\Api\ClerkWebhookController::class, 'handle']);
Route::post('/webhooks/infinitepay', [WebhookController::class, 'handle']);
// Local-only webhook simulator (development)
if (app()->environment('local')) {
    Route::post('/webhooks/infinitepay/simulate', [WebhookController::class, 'simulate']);
}

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Customer Profile Management
    Route::get('/profile', [AuthController::class, 'getProfile']);
    Route::get('/customer', [\App\Http\Controllers\GoogleAuthController::class, 'getCurrentCustomer']);
    Route::get('/auth/current-customer', [\App\Http\Controllers\GoogleAuthController::class, 'getCurrentCustomer']);
    Route::put('/profile', [AuthController::class, 'completeProfile']);

    // Admin catalog management
    Route::apiResource('drops', DropController::class)->except(['create', 'edit', 'index', 'show']);
    Route::apiResource('products', ProductController::class)->except(['create', 'edit', 'index', 'show']);
    Route::apiResource('products.variants', ProductVariantController::class)->shallow()->except(['create', 'edit', 'index', 'show']);
    Route::apiResource('products.images', ProductImageController::class)->shallow()->except(['create', 'edit', 'index', 'show']);
    Route::apiResource('drops.images', ApiDropImageController::class)->shallow()->except(['create', 'edit', 'index', 'show']);

    // Cart & Orders
    Route::get('/cart', [CartController::class, 'show']);
    Route::get('/cart/debug', [CartController::class, 'debug']);
    Route::put('/cart/items/{cartItem}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'removeItem']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    // Payment
    Route::post('/checkout/{id}', [PaymentController::class, 'checkout']);
});
