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
        Schema::create('shopify_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('shop_id'); // References users table
            $table->unsignedBigInteger('order_id')->unique(); // Shopify order ID
            $table->string('order_number');
            $table->string('name'); // Order display name like #1001
            $table->string('email');
            $table->string('financial_status');
            $table->string('fulfillment_status')->nullable();
            $table->decimal('total_price', 10, 2);
            $table->string('currency');
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('customer')->nullable();
            $table->json('line_items');
            $table->json('shipping_lines')->nullable();
            $table->json('discount_codes')->nullable();
            $table->json('note_attributes')->nullable();
            $table->string('tags')->nullable();
            $table->boolean('test')->default(false);
            $table->timestamp('processed_at');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('shop_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_orders');
    }
};
