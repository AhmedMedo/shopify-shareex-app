<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shareex_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->unique()->constrained('users')->onDelete('cascade'); // kyon147/laravel-shopify uses 'users' table for shops
            $table->text('base_url'); // Encrypted
            $table->text('api_username'); // Encrypted
            $table->text('api_password'); // Encrypted
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shareex_credentials');
    }
};

