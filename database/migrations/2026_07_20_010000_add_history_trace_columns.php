<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add user_id (denormalized) on payments and order_id/reference on
     * loyalty_transactions so each history entry can be traced precisely
     * without extra joins.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->after('order_id');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index('user_id');
        });

        Schema::table('loyalty_transactions', function (Blueprint $table) {
            $table->uuid('order_id')->nullable()->after('user_id');
            $table->string('reference')->nullable()->after('description');
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_transactions', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropForeign(['order_id']);
            $table->dropColumn(['order_id', 'reference']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
