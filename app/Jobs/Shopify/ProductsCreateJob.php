<?php

namespace App\Jobs\Shopify;

use App\Models\AreaMapping;
use App\Models\ShippingPartnerCredential;
use App\Models\ShipmentLog;
use App\Models\ShippingRateRule;
use App\Models\User as ShopifyStore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use stdClass;

class ProductsCreateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ShopDomain $shopDomain;
    public stdClass $data; // Webhook data, though for app/uninstalled it might be minimal

    /**
     * Create a new job instance.
     *
     * @param ShopDomain $shopDomain The shop domain.
     * @param stdClass $data The webhook data (JSON decoded).
     */
    public function __construct(ShopDomain $shopDomain, stdClass $data)
    {
        $this->shopDomain = $shopDomain;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Products create webhook",[
            'data' => $this->data,
            'shopDomain' => $this->shopDomain
        ]);

    }
}

