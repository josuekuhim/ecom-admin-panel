<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DropController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\DropImageController as AdminDropImageController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CartController as AdminCartController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\StorefrontController;
use App\Http\Controllers\Admin\MonitoringController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('login');
});

Auth::routes();

Route::middleware('auth')->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::resource('drops', DropController::class);
        Route::resource('products', ProductController::class);
        Route::resource('products.variants', ProductVariantController::class)->shallow();
        Route::resource('products.images', ProductImageController::class)->only(['create', 'store', 'destroy']);
    Route::resource('drops.images', AdminDropImageController::class)->only(['create', 'store', 'destroy']);
        Route::resource('orders', OrderController::class)->only(['index', 'show']);
        Route::put('orders/{order}/update-status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
        
        // Settings
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingsController::class, 'store'])->name('settings.store');

        // Customers
        Route::resource('customers', CustomerController::class)->only(['index', 'show']);
        
        // Carts
        Route::resource('carts', AdminCartController::class)->only(['index', 'show']);
        Route::get('carts-analytics', [AdminCartController::class, 'analytics'])->name('carts.analytics');
        
        // Notifications
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/counts', [NotificationController::class, 'counts'])->name('notifications.counts');
        
        // Storefront Integration
        Route::prefix('storefront')->name('storefront.')->group(function () {
            Route::get('/', [StorefrontController::class, 'index'])->name('index');
            Route::get('/settings', [StorefrontController::class, 'settings'])->name('settings');
            Route::post('/settings', [StorefrontController::class, 'updateSettings'])->name('settings.update');
            Route::get('/api-status', [StorefrontController::class, 'apiStatus'])->name('api-status');
            Route::post('/clear-cache', [StorefrontController::class, 'clearCache'])->name('clear-cache');
        });

        // Monitoring
        Route::prefix('monitoring')->name('monitoring.')->group(function () {
            Route::get('/', [MonitoringController::class, 'index'])->name('index');
            Route::get('/status', [MonitoringController::class, 'status'])->name('status');
        });
    });
});
