<?php

use App\Livewire\Shopify\Settings\ApplicationLogs;
use App\Livewire\Shopify\Settings\AreaMappings;
use App\Livewire\Shopify\Settings\ShareexCredentials;
use App\Livewire\Shopify\Settings\ShippingRateRules;
use App\Livewire\Shopify\Shipments\ShipmentLogs;
use App\Services\ShippingCityMapperService;
use Illuminate\Support\Facades\Route;

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

Route::get('/test', function () {
    $order = \App\Models\ShopifyOrder::query()->latest()->first();
    $cityMapper = new ShippingCityMapperService();
    $shareexCity =  $cityMapper->getShareexCity($order->shipping_address);
    dd($shareexCity);

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

Route::get('admin/login', function () {
    return view('admin.auth.login');
});

Route::post('admin/login',[\App\Http\Controllers\Admin\AdminController::class, 'login'])->name('admin.login');
Route::group([
    'prefix' => 'admin',
    'middleware' => ['web', 'auth:admin'],
    'as' => 'admin.'
], function () {
    Route::get('/', [\App\Http\Controllers\Admin\AdminController::class, 'index'])->name('home');

    Route::post('/orders/{order}/update-city', [\App\Http\Controllers\Admin\AdminController::class, 'updateShippingCity'])
        ->name('orders.update-city');

    Route::post('/orders/{order}/update-status', [\App\Http\Controllers\Admin\AdminController::class, 'updateShippingStatus'])
        ->name('orders.update-status');

    //logout
    Route::post('/logout', [\App\Http\Controllers\Admin\AdminController::class, 'logout'])->name('logout');

});
