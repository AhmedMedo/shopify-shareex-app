<?php

namespace App\Jobs\Shopify;

use App\Models\ShippingPartnerCredential;
use App\Models\User as ShopifyStore;
use App\Models\ShippingRateRule;
use App\Models\AreaMapping;
use App\Models\ShipmentLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Osiset\ShopifyApp\Exceptions\InvalidShopDomainException;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use stdClass;

class AppUninstalledJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ShopDomain $shopDomain;
    public stdClass $data; // Webhook data, though for app/uninstalled it might be minimal

    /**
     * Create a new job instance.
     *
     * @param string $shopDomain The shop domain.
     * @param stdClass $data The webhook data (JSON decoded).
     * @throws InvalidShopDomainException
     */
    public function __construct(string $shopDomain, stdClass $data)
    {
        $this->shopDomain = new ShopDomain($shopDomain);
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("AppUninstalledJob: Handling webhook for shop " . $this->shopDomain->toNative());

        $shop = ShopifyStore::where("name", $this->shopDomain->toNative())->first();

        if (!$shop) {
            Log::warning("AppUninstalledJob: Shop not found with domain: " . $this->shopDomain->toNative() . ". No cleanup performed, but shop data might already be gone by Shopify package.");
            return;
        }

        // Perform cleanup tasks for the shop
        // This might include:
        // 1. Deleting associated ShippingRateRules
        // 2. Deleting associated AreaMappings
        // 3. Deleting associated MubasherCredentials
        // 4. Deleting associated ShipmentLogs
        // 5. The kyon147/laravel-shopify package might handle deleting the shop (User record) itself.
        //    If so, related records with foreign key constraints (onDelete("cascade")) would also be deleted.
        //    It's good practice to explicitly clean up if cascade is not set or to be sure.

        try {
            ShippingRateRule::where("shop_id", $shop->id)->delete();
            AreaMapping::where("shop_id", $shop->id)->delete();
            ShippingPartnerCredential::where("shop_id", $shop->id)->delete();
            ShipmentLog::where("shop_id", $shop->id)->delete();

            // The ShopifyApp package usually soft deletes the shop or fully deletes it.
            // If it doesn't, or you want to ensure it's hard deleted:
            // $shop->forceDelete(); // or $shop->delete(); if soft deletes are enabled and you want that.

            Log::info("AppUninstalledJob: Cleanup completed for shop " . $this->shopDomain->toNative());

        } catch (\Exception $e) {
            Log::error("AppUninstalledJob: Error during cleanup for shop " . $this->shopDomain->toNative(), [
                "message" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
        }
    }
}

