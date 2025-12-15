<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

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
    return redirect('/admin');
});

// Routes d'authentification Admin
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [App\Http\Controllers\Admin\AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [App\Http\Controllers\Admin\AuthController::class, 'login']);
    });
    Route::post('/logout', [App\Http\Controllers\Admin\AuthController::class, 'logout'])->middleware('auth')->name('logout');
});

// Routes Admin (protégées)
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\AdminController::class, 'dashboard'])->name('dashboard');
    
    Route::resource('users', App\Http\Controllers\Admin\UserController::class);
    Route::resource('products', App\Http\Controllers\Admin\ProductController::class);
    Route::resource('alerts', App\Http\Controllers\Admin\AlertController::class);
    Route::resource('subscriptions', App\Http\Controllers\Admin\SubscriptionController::class);
    Route::get('price-histories', [App\Http\Controllers\Admin\PriceHistoryController::class, 'index'])->name('price-histories.index');
    Route::get('price-histories/{priceHistory}', [App\Http\Controllers\Admin\PriceHistoryController::class, 'show'])->name('price-histories.show');
    Route::get('affiliate-clicks', [App\Http\Controllers\Admin\AffiliateClickController::class, 'index'])->name('affiliate-clicks.index');
    Route::get('affiliate-clicks/{affiliateClick}', [App\Http\Controllers\Admin\AffiliateClickController::class, 'show'])->name('affiliate-clicks.show');
});
