<?php

use Illuminate\Support\Facades\Route;

// IMPORT CONTROLLERS
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ORAD;

use App\Http\Controllers\FavouriteController;

use App\Http\Controllers\UserCategoryController;
use App\Http\Controllers\UserBrandController;
use App\Http\Controllers\UserProductController;
use App\Http\Controllers\VnPayController;



// ==================== AUTH ====================
Route::post('/register', [AuthController::class, 'register']);
Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/change-password', [AuthController::class, 'changePassword']);


// ==================== CART ====================
Route::post('/cart/add', [CartController::class, 'add']);
Route::post('/cart/update', [CartController::class, 'update']);
Route::post('/cart/remove', [CartController::class, 'remove']);
Route::post('/cart/get', [CartController::class, 'getCart']);
Route::post('/cart/clear', [CartController::class, 'clear']);

// ==================== ORDER ====================
Route::post('/orders/check', [OrderController::class, 'placeOrder']);
Route::get('/orders/list/{userId}', [OrderController::class, 'listOrders']);
Route::get('/orders/detail/{id}', [OrderController::class, 'orderDetail']);
Route::put('/orders/cancel/{id}', [OrderController::class, 'cancelOrder']);
//===================== PAYMENT ====================
Route::post('/vnpay/create', [VnPayController::class, 'createPayment']);
Route::get('/vnpay/return', [VnPayController::class, 'vnpReturn']);
Route::post('/vnpay/ipn', [VnPayController::class, 'ipnHandler']);


//1.Quan li thuong hieu
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

// 4. Quản lý Đơn hàng (ADMIN)




Route::prefix('admin')->group(function () {
    Route::get('/orders', [ORAD::class, 'index']);
    Route::get('/orders/{id}', [ORAD::class, 'show']);
    Route::put('/orders/{id}', [ORAD::class, 'update']);
    Route::delete('/orders/{id}', [ORAD::class, 'destroy']);

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});


// 5. Quản lý Người dùng


//============= trang chu 
Route::get('/home/hotProducts', [UserProductController::class, 'getHotProducts']);
Route::get('/home/brands', [UserProductController::class, 'getBrands']);
Route::get('/home/makeupProducts', [UserProductController::class, 'getMakeupProducts']);
Route::get('/home/lipstickProducts', [UserProductController::class, 'getLipstickProducts']);
Route::get('/home/skincareProducts', [UserProductController::class, 'getSkincareProducts']);
Route::get('/products/{id}', [UserProductController::class, 'getProductDetail']);

Route::get('/user/products', [UserProductController::class, 'index']);
Route::get('/user/categories', [UserCategoryController::class, 'index']);
Route::get('/user/brands', [UserBrandController::class, 'index']);
Route::get('/favourites', [FavouriteController::class, 'index']);
Route::post('/favourites/add', [FavouriteController::class, 'store']);
Route::delete('/favourites/remove', [FavouriteController::class, 'destroy']);

Route::get('/db-test', function () {
    return DB::select('SELECT 1');
});
