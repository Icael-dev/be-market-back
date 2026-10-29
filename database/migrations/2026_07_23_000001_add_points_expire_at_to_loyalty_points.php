<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loyalty_points', function (Blueprint $table) {
            $table->dateTime('points_expire_at')->nullable()->after('points');
            $table->index('points_expire_at');
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_points', function (Blueprint $table) {
            $table->dropIndex(['points_expire_at']);
            $table->dropColumn('points_expire_at');
        });
    }
};
