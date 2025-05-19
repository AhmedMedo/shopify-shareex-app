<?php

namespace App\Jobs\Shopify;

use App\Models\ShopifyOrder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Osiset\ShopifyApp\Exceptions\InvalidShopDomainException;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use stdClass;

class OrdersCreateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ShopDomain $shopDomain;
    public stdClass $data;

    /**
     * @throws InvalidShopDomainException
     */
    public function __construct(string $shopDomain, stdClass $data)
    {
        $this->shopDomain = new ShopDomain($shopDomain);
        $this->data = $data;
    }

    public function handle(): void
    {
        try {
            // Find the shop/user
            $shop = User::where('name', $this->shopDomain->toNative())
                ->orWhere('email', 'like', '%@'.$this->shopDomain->toNative())
                ->first();

            if (!$shop) {
                Log::error('Shop not found', ['domain' => $this->shopDomain]);
                return;
            }

            // Create or update the order
            ShopifyOrder::updateOrCreate(
                ['order_id' => $this->data->id],
                [
                    'shop_id' => $shop->id,
                    'order_number' => $this->data->order_number ?? $this->data->number,
                    'name' => $this->data->name,
                    'email' => $this->data->email,
                    'financial_status' => $this->data->financial_status,
                    'fulfillment_status' => $this->data->fulfillment_status ?? null,
                    'total_price' => $this->data->total_price,
                    'currency' => $this->data->currency,
                    'billing_address' => $this->data->billing_address ?? null,
                    'shipping_address' => $this->data->shipping_address ?? null,
                    'customer' => $this->data->customer ?? null,
                    'line_items' => $this->data->line_items,
                    'shipping_lines' => $this->data->shipping_lines ?? null,
                    'discount_codes' => $this->data->discount_codes ?? null,
                    'note_attributes' => $this->data->note_attributes ?? null,
                    'tags' => $this->data->tags ?? null,
                    'test' => $this->data->test ?? false,
                    'processed_at' => $this->data->processed_at,
                ]
            );

            Log::info('Order processed successfully', [
                'order_id' => $this->data->id,
                'shop_id' => $shop->id
            ]);


        } catch (\Exception $e) {
            Log::error('Failed to process order', [
                'error' => $e->getMessage(),
                'order_data' => (array)$this->data
            ]);
        }
    }
}
