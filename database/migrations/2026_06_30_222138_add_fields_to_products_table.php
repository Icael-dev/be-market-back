<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('short_description')->nullable()->after('description');
            $table->decimal('cost_price', 12, 2)->nullable()->after('weight');
            $table->json('dimensions')->nullable()->after('cost_price');
            $table->unsignedInteger('min_stock_alert')->nullable()->after('dimensions');
            $table->string('meta_title')->nullable()->after('min_stock_alert');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('meta_keywords')->nullable()->after('meta_description');
            $table->json('variants')->nullable()->after('meta_keywords');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'short_description',
                'cost_price',
                'dimensions',
                'min_stock_alert',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'variants',
            ]);
        });
    }
};
