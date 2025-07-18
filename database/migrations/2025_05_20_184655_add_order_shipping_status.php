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
        Schema::table('shopify_orders', function (Blueprint $table) {
            $table->string('shipping_status')->default(\App\Enum\ShippingStatusEnum::PENDING->value)->nullable();
            $table->string('shareex_shipping_city')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shopify_orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_status', 'shareex_shipping_city']);
        });
    }
};
