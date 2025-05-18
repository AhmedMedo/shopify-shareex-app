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
        Schema::create("shipment_logs", function (Blueprint $table) {
            $table->id();
            $table->foreignId("shop_id")->constrained("users")->onDelete("cascade"); // Assuming "users" table from kyon147/laravel-shopify stores shop info
            $table->string("shopify_order_id")->nullable()->index();
            $table->string("shareex_serial_number")->nullable()->index(); // Renamed column
            $table->string("action"); // e.g., "SendShipment", "GetShipmentLastStatus", "GetShipmentHistory"
            $table->text("request_payload")->nullable();
            $table->text("response_payload")->nullable();
            $table->string("status"); // e.g., "success", "failed"
            $table->text("error_message")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("shipment_logs");
    }
};

