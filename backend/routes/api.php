<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AdminTrashController;
use App\Http\Controllers\Api\AdminOrderController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductVariantController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MenuController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('brands', BrandController::class)->only(['index', 'show']);
Route::apiResource('products', ProductController::class)->only(['index', 'show']);
Route::get('/posts', [PostController::class, 'publicIndex']);
Route::get('/posts/{slug}', [PostController::class, 'publicShow'])->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');
Route::get('/menus', [MenuController::class, 'publicIndex']);

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::get('/addresses/{address}', [AddressController::class, 'show']);
    Route::put('/addresses/{address}', [AddressController::class, 'update']);
    Route::patch('/addresses/{address}', [AddressController::class, 'update']);
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);
    Route::patch('/addresses/{address}/default', [AddressController::class, 'setDefault']);
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'store']);
    Route::patch('/cart/items/{cartItem}', [CartController::class, 'update']);
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroyItem']);
    Route::delete('/cart', [CartController::class, 'destroy']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

    Route::middleware('admin')->group(function () {
        Route::get('/admin/orders', [AdminOrderController::class, 'index']);
        Route::get('/admin/orders/{order}', [AdminOrderController::class, 'show'])->whereNumber('order');
        Route::patch('/admin/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->whereNumber('order');
        Route::get('/admin/menus', [MenuController::class, 'adminIndex']);
        Route::post('/admin/menus', [MenuController::class, 'adminStore']);
        Route::get('/admin/menus/{menu}', [MenuController::class, 'adminShow'])->whereNumber('menu');
        Route::patch('/admin/menus/{menu}', [MenuController::class, 'adminUpdate'])->whereNumber('menu');
        Route::delete('/admin/menus/{menu}', [MenuController::class, 'adminDestroy'])->whereNumber('menu');
        Route::patch('users/{user}/status', [UserController::class, 'status'])->whereNumber('user');
        Route::get('/admin/posts', [PostController::class, 'adminIndex']);
        Route::post('/admin/posts', [PostController::class, 'adminStore']);
        Route::get('/admin/posts/{post}', [PostController::class, 'adminShow']);
        Route::patch('/admin/posts/{post}', [PostController::class, 'adminUpdate']);
        Route::post('/admin/posts/{post}', [PostController::class, 'adminUpdate']);
        Route::delete('/admin/posts/{post}', [PostController::class, 'adminDestroy']);
        Route::apiResource('users', UserController::class);
        Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('brands', BrandController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('products', ProductController::class)->only(['store', 'update', 'destroy']);

        Route::prefix('admin')->group(function () {
            Route::get('/trash', [AdminTrashController::class, 'index']);
            Route::post('/trash/{type}/{id}/restore', [AdminTrashController::class, 'restore'])
                ->where('type', 'category|brand|product|post|menu')
                ->whereNumber('id');
            Route::delete('/trash/{type}/{id}', [AdminTrashController::class, 'destroy'])
                ->where('type', 'category|brand|product|post|menu')
                ->whereNumber('id');

            Route::apiResource('categories', CategoryController::class)
                ->only(['index', 'show'])
                ->names([
                    'index' => 'admin.catalog.categories.index',
                    'show' => 'admin.catalog.categories.show',
                ]);
            Route::apiResource('brands', BrandController::class)
                ->only(['index', 'show'])
                ->names([
                    'index' => 'admin.catalog.brands.index',
                    'show' => 'admin.catalog.brands.show',
                ]);
            Route::apiResource('products', ProductController::class)
                ->only(['index', 'show'])
                ->names([
                    'index' => 'admin.catalog.products.index',
                    'show' => 'admin.catalog.products.show',
                ]);

            Route::apiResource('products.variants', ProductVariantController::class)
                ->parameters([
                    'products' => 'product',
                    'variants' => 'variant',
                ])
                ->names([
                    'index' => 'admin.products.variants.index',
                    'store' => 'admin.products.variants.store',
                    'show' => 'admin.products.variants.show',
                    'update' => 'admin.products.variants.update',
                    'destroy' => 'admin.products.variants.destroy',
                ]);
        });
    });
});
