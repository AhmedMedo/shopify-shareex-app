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
        Schema::create('shipping_rate_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Standard Shipping - Zone A - 0-5kg"
            $table->string('destination_area_pattern'); // Could be a specific city, region, or a pattern like "ZONE_A*"
            $table->decimal('min_weight', 8, 3)->nullable(); // Minimum weight in kg, nullable if not applicable
            $table->decimal('max_weight', 8, 3)->nullable(); // Maximum weight in kg, nullable if not applicable
            $table->decimal('min_order_value', 10, 2)->nullable(); // Minimum order value, nullable
            $table->decimal('max_order_value', 10, 2)->nullable(); // Maximum order value, nullable
            $table->decimal('rate_amount', 10, 2); // The shipping rate
            $table->string('currency', 3)->default('SAR'); // Currency code, e.g., SAR
            $table->boolean('is_active')->default(true);
            $table->foreignId('shop_id')->nullable()->constrained('users')->onDelete('cascade'); // If multi-shop support is needed, link to the shop (users table from kyon147 package)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rate_rules');
    }
};

