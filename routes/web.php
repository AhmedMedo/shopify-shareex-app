<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Livewire\Shopify\Settings\ShareexCredentials;
use App\Livewire\Shopify\Settings\AreaMappings;
use App\Livewire\Shopify\Settings\ShippingRateRules;
use App\Livewire\Shopify\Shipments\ShipmentLogs;
use App\Livewire\Shopify\Settings\ApplicationLogs;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Services\ApiHelper;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('test', function () {
    $shop = \App\Models\User::latest()->first();
    $orderId  = '5973232648294';
    $response = Http::withHeaders([
        'X-Shopify-Access-Token' => $shop->password,
    ])->get("https://{$shop->name}/admin/api/2025-04/orders/{$orderId}.json");
    dd($response->json());
    $shop = \App\Models\User::first();
    $orderId  = '6468709941561';
    $response = Http::withHeaders([
        'X-Shopify-Access-Token' => $shop->password,
    ])->get("https://{$shop->name}/admin/api/2025-04/orders/{$orderId}.json");

    dd($response->json());
    // Step 1: List all webhooks (GraphQL)

});

// Default route for the application within Shopify after authentication
Route::middleware(['verify.shopify'])->group(function () {
    // The main page for the app, defaults to Shareex Credentials settings
    //    Route::get('/', ShareexCredentials::class)->name('home'); // Or use a dedicated view like below
     Route::get('/', function () {
         return view('shopify.index');
     })->name('home');

    // Explicit routes for each Livewire component page if direct navigation is needed
    Route::get('/settings/credentials', ShareexCredentials::class)->name('shopify.settings.credentials');
    Route::get('/settings/area-mappings', AreaMappings::class)->name('shopify.settings.area-mappings');
    Route::get('/settings/shipping-rules', ShippingRateRules::class)->name('shopify.settings.shipping-rules');
    Route::get('/shipments/logs', ShipmentLogs::class)->name('shopify.shipments.logs');
    Route::get('/settings/logs', ApplicationLogs::class)->name('shopify.settings.logs');

    // Example of how you might have linked them in the shopify.index.blade.php if you used a wrapper view
    // Route::get('/area-mappings', function() { return view('shopify.index', ['page' => 'area-mappings']); })->name('shopify.area-mappings');
    // Route::get('/shipping-rules', function() { return view('shopify.index', ['page' => 'shipping-rules']); })->name('shopify.shipping-rules');
    // Route::get('/shipment-logs', function() { return view('shopify.index', ['page' => 'shipment-logs']); })->name('shopify.shipment-logs');
});

// The osiset/laravel-shopify package handles the /authenticate route and its callback.
// No need to define them here unless you are overriding the package's behavior.

// Fallback or public welcome page (if accessed outside Shopify context, though not typical for embedded apps)
// Route::get('/welcome', function () {
//     return view('welcome');
// });

