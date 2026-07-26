<?php

use App\Http\Controllers\Admin\AdminBannerController;
use App\Http\Controllers\Admin\AdminBlogController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminCouponController;
use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\AdminFlashSaleController;
use App\Http\Controllers\Admin\AdminImageController;
use App\Http\Controllers\Admin\AdminFAQController;
use App\Http\Controllers\Admin\AdminLegalPageController;
use App\Http\Controllers\Admin\AdminLoyaltyController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminStatisticsController;
use App\Http\Controllers\Admin\AdminSupportController;
use App\Http\Controllers\Admin\AdminTestimonialController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\LoyaltyController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\TestimonialController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
});

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth.api');
    Route::get('user', [AuthController::class, 'user'])->middleware('auth.api');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth.api');
    Route::post('forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:5,1');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
});

Route::get('images/{path}', [ImageController::class, 'show'])->where('path', '.*');

Route::get('categories', [CategoryController::class, 'index']);
Route::get('products', [ProductController::class, 'index']);
Route::get('products/price-bounds', [ProductController::class, 'priceBounds']);
Route::get('products/suggestions', [ProductController::class, 'suggestions']);
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

// Payment webhook callback — public, secured by X-Callback-Secret header
Route::post('payment/callback', [PaymentController::class, 'callback']);

Route::middleware('auth.api')->group(function () {
    Route::get('payment/history', [PaymentController::class, 'index']);
    Route::post('payment/initiate', [PaymentController::class, 'store']);
    Route::get('payment/verify/{id}', [PaymentController::class, 'verify']);
});

Route::middleware('auth.api')->group(function () {
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

    Route::post('coupons/validate', [CouponController::class, 'validate']);
    Route::get('loyalty/points', [LoyaltyController::class, 'points']);
    Route::get('loyalty/transactions', [LoyaltyController::class, 'transactions']);
});

Route::middleware(['auth.api', 'admin', 'throttle:120,1'])->prefix('admin')->group(function () {
    Route::apiResource('products', AdminProductController::class);
    Route::post('products/{id}/restore', [AdminProductController::class, 'restore']);
    Route::post('products/upload-image', [AdminProductController::class, 'uploadImage']);
    Route::post('products/{id}/add-stock', [AdminProductController::class, 'addStock']);
    Route::post('upload-image', [AdminImageController::class, 'upload']);
    Route::apiResource('categories', AdminCategoryController::class);
    Route::post('categories/{id}/restore', [AdminCategoryController::class, 'restore']);
    Route::apiResource('banners', AdminBannerController::class);
    Route::apiResource('blog-posts', AdminBlogController::class);
    Route::get('orders', [AdminOrderController::class, 'index']);
    Route::put('orders/{id}', [AdminOrderController::class, 'update']);
    Route::post('orders/{id}/restore', [AdminOrderController::class, 'restore']);
    Route::get('customers', [AdminCustomerController::class, 'index']);
    Route::get('customers/{id}', [AdminCustomerController::class, 'show']);
    Route::get('customers/{id}/orders', [AdminCustomerController::class, 'orders']);
    Route::get('customers/{id}/loyalty', [AdminCustomerController::class, 'loyalty']);
    Route::get('customers/{id}/payments', [AdminCustomerController::class, 'payments']);
    Route::get('payments', [AdminPaymentController::class, 'index']);
    Route::get('loyalty/transactions', [AdminLoyaltyController::class, 'transactions']);
    Route::get('loyalty/points', [AdminLoyaltyController::class, 'points']);
    Route::get('support/tickets', [AdminSupportController::class, 'index']);
    Route::get('support/tickets/{id}', [AdminSupportController::class, 'show']);
    Route::put('support/tickets/{id}', [AdminSupportController::class, 'update']);
    Route::post('support/tickets/{id}/messages', [AdminSupportController::class, 'storeMessage']);
    Route::apiResource('testimonials', AdminTestimonialController::class);
    Route::apiResource('faqs', AdminFAQController::class);
    Route::apiResource('legal-pages', AdminLegalPageController::class);
    Route::get('statistics', [AdminStatisticsController::class, 'index']);
    Route::get('statistics/top-products', [AdminStatisticsController::class, 'topProducts']);
    Route::get('statistics/low-stock', [AdminStatisticsController::class, 'lowStock']);
    Route::get('statistics/coupons', [AdminStatisticsController::class, 'couponStats']);
    Route::get('statistics/category-sales', [AdminStatisticsController::class, 'categorySales']);
    Route::get('export', [AdminStatisticsController::class, 'export']);
});

// Super-admin only: coupons, flash sales, settings and staff/user management.
Route::middleware(['auth.api', 'admin.super', 'throttle:60,1'])->prefix('admin')->group(function () {
    Route::apiResource('coupons', AdminCouponController::class);
    Route::apiResource('flash-sales', AdminFlashSaleController::class);
    Route::get('settings', [AdminSettingController::class, 'index']);
    Route::put('settings', [AdminSettingController::class, 'update']);
    Route::get('users', [AdminUserController::class, 'index']);
    Route::post('users', [AdminUserController::class, 'store']);
    Route::put('users/{id}', [AdminUserController::class, 'update']);
    Route::delete('users/{id}', [AdminUserController::class, 'destroy']);
    Route::post('users/{id}/restore', [AdminUserController::class, 'restore']);
});
