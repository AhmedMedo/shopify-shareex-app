<?php

namespace App\Services\Shareex;

use App\Models\AreaMapping;
use App\Models\ShipmentLog;
use App\Models\ShopifyOrder;
use Illuminate\Support\Facades\Log;

class ShippingService
{

    public function __construct(private readonly ShopifyOrder $order)
    {

    }


    public function sendToShareex(): bool
    {
        $shop = $this->order->shop;
        $shareexApiService = new ShareexApiService($shop);
        $shareexApiService->refreshCredentials();

        $shipmentData = $this->prepareShipmentData($this->order);
        if (!$shipmentData) return false;

        // 5. Send to Shareex
        return $this->processSending($shareexApiService, $this->order, $shipmentData);


    }

    protected function prepareShipmentData(ShopifyOrder $order): ?array
    {
        $shippingAddress = $this->getShippingAddress($order);
        if (!$shippingAddress) return null;

//        $areaMapping = $this->getAreaMapping($order->shop_id, $shippingAddress);
        $shareexArea = $order->shareex_shipping_city;
        if (!$shareexArea) {
            Log::error('Shareex area found: ' . $shareexArea);
        }

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
        foreach ($this->order->line_items as $item) {
            $pieces += $item['quantity'];
        }
        return $pieces;
    }

    protected function processSending(ShareexApiService $service, ShopifyOrder $order, array $payload): bool
    {
        Log::info("Attempting to send shipment to Shareex for order {$order->order_id}", $payload);

        $response = $service->sendShipment($payload);

       return $this->logShipmentResult($order, $payload, $response);
    }

    protected function logShipmentResult(ShopifyOrder $order, array $payload, $response): bool
    {
        $success = false;
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
            if (is_null($serial)) {
                return false;
            }

            $logData["status"] = "success";
            $logData["shareex_serial_number"] = $serial;

//            // Update order with tracking info if available
//            if ($serial) {
//                $this->updateOrderTracking($order, $serial);
//            }

            $order->update(['shipping_serial' => $serial]);


            $success = true;
            Log::info("Successfully sent shipment for order {$order->order_id}, serial: {$serial}");
        } else {
            $logData["status"] = "failed";
            $logData["error_message"] = $response['error'] ?? "Shareex API request failed";
            Log::error("Failed to send shipment for order {$order->order_id}");
        }

        ShipmentLog::create($logData);

        return $success;
    }


}
