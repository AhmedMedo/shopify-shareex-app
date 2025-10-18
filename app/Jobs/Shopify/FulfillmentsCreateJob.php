<?php

namespace App\Jobs\Shopify;

use App\Models\AreaMapping;
use App\Models\ShipmentLog;
use App\Models\ShopifyOrder;
use App\Models\User as ShopifyStore;
use App\Services\Shareex\ShareexApiService;
use App\Services\ShippingService;
use App\Services\ShippingCityMapperService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Osiset\ShopifyApp\Exceptions\InvalidShopDomainException;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use stdClass;

class FulfillmentsCreateJob implements ShouldQueue
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
        Log::info("FulfillmentsCreateJob: Handling webhook for shop {$this->shopDomain->toNative()} and fulfillment ID {$this->data->id}");

        // 1. Find the shop
        $shop = $this->getShop();
        if (!$shop || !$shop->is_active) return;

        // 2. Initialize Shareex service


        // 3. Get order data
        $order = $this->getOrderData($shop);
        if (!$order) return;

        $order->update([
            'shipping_status' => \App\Enum\ShippingStatusEnum::READY_TO_SHIP->value,
            'processed_at' => now(),
        ]);

        $order->refresh();
        Log::debug('FulfillmentsCreateJob: order updated', ["order" => $order]);
        // 4. Prepare shipment data

        $cityMapper = new ShippingCityMapperService();
        $shareexCity =  $cityMapper->getShareexCity($order->shipping_address);
        if (!$shareexCity) {
            $order->update([
                'shipping_status' => \App\Enum\ShippingStatusEnum::AWAINTING_FOR_SHIPPING_CITY->value,
//                'processed_at' => now(),
            ]);
            Log::error('Shareex city found:',[
                'shop_id' => $order->shop_id,
                'shareex_city' => $shareexCity,
                'shipping_address' => $order->shipping_address
            ]);
            return;
        }


        $order->update([
            'shareex_shipping_city' => $shareexCity
        ]);


        try {
            $order = $order->refresh();
            $shippingService = app(ShippingService::class);
            $shippingService->dispatchShipment($order);

        }catch (Exception $e) {
            Log::error('Error updating shipping status: ' . $e->getMessage());
        }
//        $shareexApiService = new ShareexApiService($shop);
//        $shareexApiService->refreshCredentials();
//
//        $shipmentData = $this->prepareShipmentData($order);
//        if (!$shipmentData) return;
//
//        // 5. Send to Shareex
//        $this->sendToShareex($shareexApiService, $order, $shipmentData);
    }

    protected function getShop(): ?ShopifyStore
    {
        $shop = ShopifyStore::where("name", $this->shopDomain->toNative())->first();

        if (!$shop) {
            Log::error("Shop not found with domain: {$this->shopDomain}");
            return null;
        }

        return $shop;
    }

    protected function getOrderData(ShopifyStore $shop): ?ShopifyOrder
    {
        $order = ShopifyOrder::where("shop_id", $shop->id)
            ->where("order_id", $this->data->order_id)
            ->first();

        if (!$order) {
            Log::error("Order not found with ID: {$this->data->order_id}");
            ShipmentLog::create([
                "shop_id" => $shop->id,
                "shopify_order_id" => (string) $this->data->order_id,
                "action" => "OrderLookup",
                "status" => "failed",
                "error_message" => "Order not found in database."
            ]);
            return null;
        }

        // Update order with fulfillment data if needed
//        $this->updateOrderWithFulfillment($order);

        return $order;
    }

    protected function updateOrderWithFulfillment(ShopifyOrder $order): void
    {
        $fulfillments = $order->fulfillments ?? [];
        $newFulfillment = [
            'id' => $this->data->id,
            'status' => $this->data->status,
            'created_at' => $this->data->created_at,
            'line_items' => $this->data->line_items,
            'tracking_info' => $this->data->tracking_info ?? null
        ];

        $fulfillments[] = $newFulfillment;
        $order->update(['fulfillments' => $fulfillments]);
    }

    protected function prepareShipmentData(ShopifyOrder $order): ?array
    {
        $shippingAddress = $this->getShippingAddress($order);
        if (!$shippingAddress) return null;

        $areaMapping = $this->getAreaMapping($order->shop_id, $shippingAddress);
        $shareexArea = $areaMapping ? $areaMapping->shareex_area_name : $shippingAddress['city'];

        return [
            "clientref" => $order->order_number,
            "area" => $shareexArea ?? 'المقطم', // Default area if none found
            "name" => $this->getCustomerName($order, $shippingAddress),
            "tel" => $this->getCustomerPhone($shippingAddress, $order),
            "address" => $this->getCustomerAddress($shippingAddress),
            "remarks" => $order->note ?? "",
            "pieces" => $this->calculateTotalPieces(),
            "amount" => $order->total_price ?? '0.00',
        ];
    }

    protected function getShippingAddress(ShopifyOrder $order): ?array
    {
        $shippingAddress = $order->shipping_address ?? ($order->customer['default_address'] ?? null);

        if (!$shippingAddress) {
            Log::error("Shipping address not found for order ID: {$order->order_id}");
            ShipmentLog::create([
                "shop_id" => $order->shop_id,
                "shopify_order_id" => (string) $order->order_id,
                "action" => "AddressLookup",
                "status" => "failed",
                "error_message" => "Shipping address not found in order data."
            ]);
            return null;
        }

        return $shippingAddress;
    }

    protected function getAreaMapping(int $shopId, array $shippingAddress): ?AreaMapping
    {
        return AreaMapping::where("shop_id", $shopId)
            ->where(function ($query) use ($shippingAddress) {
                $query->whereRaw("LOWER(shopify_city_province) = ?", [strtolower($shippingAddress['city'] ?? '')])
                    ->orWhereRaw("LOWER(shopify_city_province) = ?", [strtolower($shippingAddress['province'] ?? '')])
                    ->orWhereRaw("LOWER(shopify_zone_name) = ?", [strtolower($shippingAddress['country_code'] ?? '')]);
            })
            ->first();
    }

    protected function getCustomerName(ShopifyOrder $order, array $shippingAddress): string
    {
        $name = $shippingAddress['name'] ??
            ($order->customer['first_name'] . ' ' . $order->customer['last_name'] ?? '');

        return trim($name) ?: 'N/A';
    }

    protected function getCustomerPhone(array $shippingAddress, ShopifyOrder $order): string
    {
        return $shippingAddress['phone'] ??
            $order->customer['phone'] ??
            '0000000000';
    }

    protected function getCustomerAddress(array $shippingAddress): string
    {
        $address = trim(
            ($shippingAddress['address1'] ?? '') . ' ' .
            ($shippingAddress['address2'] ?? '')
        );

        return $address ?: 'N/A';
    }

    protected function calculateTotalPieces(): int
    {
        $pieces = 0;
        foreach ($this->data->line_items as $item) {
            $pieces += $item->quantity;
        }
        return $pieces;
    }

    protected function sendToShareex(ShareexApiService $service, ShopifyOrder $order, array $payload): void
    {
        Log::info("Attempting to send shipment to Shareex for order {$order->order_id}", $payload);

        $response = $service->sendShipment($payload);

        $this->logShipmentResult($order, $payload, $response);
    }

    protected function logShipmentResult(ShopifyOrder $order, array $payload, $response): void
    {
        $logData = [
            "shop_id" => $order->shop_id,
            "shopify_order_id" => (string) $order->order_id,
            "action" => "SendShipment",
            "request_payload" => json_encode($payload),
            "response_payload" => json_encode($response),
        ];

        if ($response && isset($response["d"])) {
            $decoded = json_decode($response['d'], true);
            $serial = $decoded[0]['serial'] ?? null;

            $logData["status"] = "success";
            $logData["shareex_serial_number"] = $serial;

//            // Update order with tracking info if available
//            if ($serial) {
//                $this->updateOrderTracking($order, $serial);
//            }
            $order->update(['shipping_serial' => $serial]);

            Log::info("Successfully sent shipment for order {$order->order_id}, serial: {$serial}");
        } else {
            $logData["status"] = "failed";
            $logData["error_message"] = $response['error'] ?? "Shareex API request failed";
            Log::error("Failed to send shipment for order {$order->order_id}");
        }

        ShipmentLog::create($logData);
    }

    protected function updateOrderTracking(ShopifyOrder $order, string $trackingNumber): void
    {
        $fulfillments = $order->fulfillments ?? [];

        // Update the most recent fulfillment with tracking info
        if (!empty($fulfillments)) {
            $lastIndex = count($fulfillments) - 1;
            $fulfillments[$lastIndex]['tracking_info'] = [
                'number' => $trackingNumber,
                'url' => null, // You might generate a tracking URL here
                'company' => 'Shareex'
            ];

            $order->update(['fulfillments' => $fulfillments]);
        }
    }
}
