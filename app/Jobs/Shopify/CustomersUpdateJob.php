<?php

namespace App\Jobs\Shopify;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Osiset\ShopifyApp\Exceptions\InvalidShopDomainException;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use stdClass;

class CustomersUpdateJob implements ShouldQueue
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
        Log::info("CustomersUpdateJob: Handling webhook for shop " . $this->shopDomain->toNative(),[
            'data' => (array) $this->data
        ]);
    }

}
