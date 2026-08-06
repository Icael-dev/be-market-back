<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('payment_status');
            $table->index('status');
            $table->index('created_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('order_id');
            $table->index('status');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index('order_id');
            $table->index('product_id');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->index('cart_id');
            $table->index('product_id');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('wishlists', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('product_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('category_id');
            $table->index('price');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropIndex(['product_id']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex(['cart_id']);
            $table->dropIndex(['product_id']);
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('wishlists', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['product_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropIndex(['price']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });
    }
};
