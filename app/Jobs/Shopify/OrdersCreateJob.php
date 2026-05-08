<?php

namespace App\Jobs\Shopify;

use App\Models\ShopifyOrder;
use App\Models\User;
use App\Services\Shareex\ShippingService;
use App\Services\ShippingCityMapperService;
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
            Log::info("OrdersCreateJob: Handling webhook for shop {$this->shopDomain->toNative()} and order ID {$this->data->id}",[
                'data' => (array) $this->data
            ]);
            // Find the shop/user
            $shop = User::where('name', $this->shopDomain->toNative())
                ->orWhere('email', 'like', '%@'.$this->shopDomain->toNative())
                ->first();

            if (!$shop) {
                Log::error('Shop not found', ['domain' => $this->shopDomain]);
                return;
            }

            // Create or update the order
            $order = ShopifyOrder::updateOrCreate(
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

            if ($shop->is_active && $shop->ship_on_order_create) {
                Log::info('OrdersCreateJob: ship_on_order_create enabled, shipping order to Shareex now', [
                    'order_id' => $order->order_id,
                    'shop_id' => $shop->id,
                ]);
                try {
                    $this->autoShip($order);
                } catch (\Exception $e) {
                    Log::error('OrdersCreateJob: auto-ship failed', [
                        'error' => $e->getMessage(),
                        'order_id' => $order->order_id,
                        'shop_id' => $shop->id,
                    ]);
                }
            }


        } catch (\Exception $e) {
            Log::error('Failed to process order', [
                'error' => $e->getMessage(),
                'order_data' => (array)$this->data
            ]);
        }
    }

    protected function autoShip(ShopifyOrder $order): void
    {
        $order->update([
            'shipping_status' => \App\Enum\ShippingStatusEnum::READY_TO_SHIP->value,
            'processed_at' => now(),
        ]);

        $order->refresh();

        $shippingAddress = $order->shipping_address;
        if (empty($shippingAddress)) {
            $order->update([
                'shipping_status' => \App\Enum\ShippingStatusEnum::AWAINTING_FOR_SHIPPING_CITY->value,
            ]);
            Log::error('OrdersCreateJob: shipping address empty', [
                'shop_id' => $order->shop_id,
                'order_id' => $order->order_id,
            ]);
            return;
        }

        $shareexCity = (new ShippingCityMapperService())->getShareexCity($shippingAddress);
        if (!$shareexCity) {
            $order->update([
                'shipping_status' => \App\Enum\ShippingStatusEnum::AWAINTING_FOR_SHIPPING_CITY->value,
            ]);
            Log::error('OrdersCreateJob: Shareex city not found', [
                'shop_id' => $order->shop_id,
                'shipping_address' => $shippingAddress,
            ]);
            return;
        }

        $order->update(['shareex_shipping_city' => $shareexCity]);
        $order->refresh();

        $service = new ShippingService($order);
        if ($service->sendToShareex()) {
            $order->update([
                'shipping_status' => \App\Enum\ShippingStatusEnum::SHIPPED->value,
            ]);
        }
    }
}
