<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\LoyaltyController;
use App\Http\Controllers\Api\TestimonialController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminBannerController;
use App\Http\Controllers\Admin\AdminBlogController;
use App\Http\Controllers\Admin\AdminCouponController;
use App\Http\Controllers\Admin\AdminFlashSaleController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\AdminStatisticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::get('user', [AuthController::class, 'user'])->middleware('auth:api');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
});

Route::get('categories', [CategoryController::class, 'index']);
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{slug}', [ProductController::class, 'show']);
Route::get('banners', [PublicController::class, 'banners']);
Route::get('blog-posts', [PublicController::class, 'blog']);
Route::get('blog-posts/{slug}', [PublicController::class, 'blogDetail']);
Route::get('faqs', [PublicController::class, 'faqs']);
Route::get('legal', [PublicController::class, 'legalPages']);
Route::get('legal/{slug}', [PublicController::class, 'legalPage']);
Route::get('flash-sales', [PublicController::class, 'flashSales']);
Route::get('settings', [SettingsController::class, 'index']);
Route::post('newsletter', [PublicController::class, 'newsletter']);
Route::post('stock-notifications', [PublicController::class, 'stockNotifications']);
Route::get('reviews', [ReviewController::class, 'index']);
Route::get('testimonials', [TestimonialController::class, 'index']);

Route::middleware('auth:api')->group(function () {
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);

    Route::get('addresses', [ProfileController::class, 'addresses']);
    Route::post('addresses', [ProfileController::class, 'storeAddress']);
    Route::put('addresses/{id}', [ProfileController::class, 'updateAddress']);
    Route::delete('addresses/{id}', [ProfileController::class, 'destroyAddress']);

    Route::get('cart', [CartController::class, 'show']);
    Route::post('cart/items', [CartController::class, 'addItem']);
    Route::put('cart/items/{id}', [CartController::class, 'updateItem']);
    Route::delete('cart/items/{id}', [CartController::class, 'removeItem']);
    Route::delete('cart', [CartController::class, 'clear']);

    Route::post('checkout', [OrderController::class, 'checkout']);
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{id}', [OrderController::class, 'show']);

    Route::get('wishlist', [WishlistController::class, 'index']);
    Route::post('wishlist', [WishlistController::class, 'store']);
    Route::delete('wishlist/{id}', [WishlistController::class, 'destroy']);

    Route::post('support/tickets', [SupportController::class, 'storeTicket']);
    Route::get('support/tickets', [SupportController::class, 'index']);
    Route::get('support/tickets/{id}', [SupportController::class, 'show']);
    Route::post('support/tickets/{id}/messages', [SupportController::class, 'storeMessage']);

    Route::post('reviews', [ReviewController::class, 'store']);

    Route::get('loyalty/points', [LoyaltyController::class, 'points']);
    Route::get('loyalty/transactions', [LoyaltyController::class, 'transactions']);
});

Route::middleware(['auth:api', 'admin'])->prefix('admin')->group(function () {
    Route::apiResource('products', AdminProductController::class);
    Route::apiResource('categories', AdminCategoryController::class);
    Route::apiResource('banners', AdminBannerController::class);
    Route::apiResource('blog-posts', AdminBlogController::class);
    Route::apiResource('coupons', AdminCouponController::class);
    Route::apiResource('flash-sales', AdminFlashSaleController::class);
    Route::get('settings', [AdminSettingController::class, 'index']);
    Route::put('settings', [AdminSettingController::class, 'update']);
    Route::get('orders', [AdminOrderController::class, 'index']);
    Route::put('orders/{id}', [AdminOrderController::class, 'update']);
    Route::get('customers', [AdminCustomerController::class, 'index']);
    Route::get('statistics', [AdminStatisticsController::class, 'index']);
    Route::get('export', [AdminStatisticsController::class, 'export']);
});
