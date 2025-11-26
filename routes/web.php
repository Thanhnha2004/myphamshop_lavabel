<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\UserProductController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('api')->group(function () {    
    // Lấy dữ liệu cho trang chủ
    Route::get('/home/hotProducts', [UserProductController::class, 'getHotProducts']);
    Route::get('/home/brands', [UserProductController::class, 'getBrands']);
    Route::get('/home/makeupProducts', [UserProductController::class, 'getMakeupProducts']);
    Route::get('/home/lipstickProducts', [UserProductController::class, 'getLipstickProducts']);
    Route::get('/home/skincareProducts', [UserProductController::class, 'getSkincareProducts']);
    Route::get('/products/{id}', [UserProductController::class, 'getProductDetail']);
});

// --- ROUTES DÀNH CHO KHU VỰC ADMIN ---
Route::prefix('admin')->group(function () {    
    // 1. Quản lý Thương hiệu
    Route::get('/brands', [BrandController::class, 'index']);
    Route::post('/brands', [BrandController::class, 'store']);
    Route::get('/brands/{id}', [BrandController::class, 'edit']);
    Route::put('/brands/{id}', [BrandController::class, 'update']);
    Route::delete('/brands/{id}', [BrandController::class, 'destroy']);

    // 2. Quản lý Sản phẩm
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::post('/products/{id}/images', [ProductController::class, 'uploadImages']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // 3. Quản lý Loại sản phẩm
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // 4. Quản lý Đơn hàng
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::put('/orders/{id}', [OrderController::class, 'update']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);

    // 5. Quản lý Người dùng
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

});

require __DIR__ . '/auth.php';
require __DIR__ . '/cart.php';
require __DIR__ . '/order.php';