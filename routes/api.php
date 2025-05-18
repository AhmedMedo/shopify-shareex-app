<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Shopify\CarrierServiceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware("auth:sanctum")->get("/user", function (Request $request) {
    return $request->user();
});

// Shopify Carrier Service Endpoint
// Shopify will POST to this endpoint to get shipping rates.
// This route needs to be publicly accessible but must verify the HMAC signature from Shopify.
Route::post("/shopify/carrier-service", [CarrierServiceController::class, "getRates"])->name("shopify.carrier_service.rates");

// Placeholder for Shopify Webhook routes if not handled by kyon147/laravel-shopify package directly
 Route::post("/shopify/webhooks/orders-paid", [\App\Http\Controllers\Shopify\WebhookController::class, "handleOrdersPaid"])->middleware("webhook.shopify");
 Route::post("/shopify/webhooks/app-uninstalled", [\App\Http\Controllers\Shopify\WebhookController::class, "handleAppUninstalled"])->middleware("webhook.shopify");


