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
        Schema::create("area_mappings", function (Blueprint $table) {
            $table->id();
            $table->string("shopify_zone_name")->comment("Name of the Shopify shipping zone");
            $table->string("shopify_city_province")->nullable()->comment("Specific city or province within the Shopify zone, if applicable");
            $table->string("shareex_area_name")->comment("Corresponding area name/code used by Shareex API"); // Renamed column
            $table->foreignId("shop_id")->nullable()->constrained("users")->onDelete("cascade"); // If multi-shop support is needed
            $table->timestamps();

            // Renamed unique index to reflect column name change
            $table->unique(["shop_id", "shopify_zone_name", "shopify_city_province", "shareex_area_name"], "shop_zone_city_shareex_area_unique"); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("area_mappings");
    }
};

