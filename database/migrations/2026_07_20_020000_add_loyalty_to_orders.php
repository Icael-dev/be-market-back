<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('loyalty_points_redeemed')->default(0)->after('notes');
            $table->decimal('loyalty_discount_amount', 12, 2)->default(0)->after('loyalty_points_redeemed');
            $table->unsignedInteger('loyalty_points_earned')->default(0)->after('loyalty_discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['loyalty_points_redeemed', 'loyalty_discount_amount', 'loyalty_points_earned']);
        });
    }
};
