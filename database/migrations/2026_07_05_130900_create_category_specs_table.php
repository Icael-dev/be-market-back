<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_specs', function (Blueprint $table) {
            $table->id();
            $table->uuid('category_id');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->string('key');
            $table->string('placeholder')->nullable();
            $table->timestamps();

            $table->unique(['category_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_specs');
    }
};
