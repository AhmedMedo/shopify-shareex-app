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
            // Serves the tab filter, the paginate() count, and the tab-count badges.
            $table->index(['shop_id', 'shipping_status'], 'shopify_orders_shop_status_idx');
            // Serves the default created_at DESC sort within a shop.
            $table->index(['shop_id', 'created_at'], 'shopify_orders_shop_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shopify_orders', function (Blueprint $table) {
            $table->dropIndex('shopify_orders_shop_status_idx');
            $table->dropIndex('shopify_orders_shop_created_idx');
        });
    }
};
