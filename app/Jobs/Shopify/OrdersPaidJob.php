<?php

namespace App\Jobs\Shopify;

use App\Models\User as ShopifyStore; // Kyon147 package uses User model as Shop by default
use App\Models\ShipmentLog;
use App\Models\AreaMapping;
use App\Services\Shareex\ShareexApiService; // Corrected Service
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use stdClass;

class OrdersPaidJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ShopDomain $shopDomain;
    public stdClass $data;

    public function __construct(ShopDomain $shopDomain, stdClass $data)
    {
        $this->shopDomain = $shopDomain;
        $this->data = $data;
    }

    public function handle(): void
    {
        Log::info("OrdersPaidJob: Handling webhook for shop " . $this->shopDomain->toNative() . " and order ID " . $this->data->id);

        $shop = ShopifyStore::where("name", $this->shopDomain->toNative())->first();

        if (!$shop) {
            Log::error("OrdersPaidJob: Shop not found with domain: " . $this->shopDomain->toNative());
            return;
        }
        
        $shareexApiService = app(ShareexApiService::class, ["shop" => $shop]);
        $shareexApiService->refreshCredentials(); 

        $orderData = $this->data;
        $shippingAddress = $orderData->shipping_address ?? ($orderData->customer->default_address ?? null);

        if (!$shippingAddress) {
            Log::error("OrdersPaidJob: Shipping address not found for order ID: " . $orderData->id);
            ShipmentLog::create([
                "shop_id" => $shop->id,
                "shopify_order_id" => (string) $orderData->id,
                "action" => "SendShipmentPreCheck",
                "status" => "failed",
                "error_message" => "Shipping address not found in order data."
            ]);
            return;
        }

        $shareexArea = $shippingAddress->city; 
        $areaMapping = AreaMapping::where("shop_id", $shop->id)
                                ->where(function($query) use ($shippingAddress) {
                                    $query->whereRaw("LOWER(shopify_city_province) = ?", [strtolower($shippingAddress->city)])
                                          ->orWhereRaw("LOWER(shopify_city_province) = ?", [strtolower($shippingAddress->province)])
                                          ->orWhereRaw("LOWER(shopify_zone_name) = ?", [strtolower($shippingAddress->country_code)]);
                                })
                                ->first();
        if ($areaMapping) {
            $shareexArea = $areaMapping->shareex_area_name; // Corrected column name
        } else {
            Log::warning("OrdersPaidJob: No specific Shareex area mapping found for order ID: " . $orderData->id . ". Defaulting to city: " . $shareexArea);
        }
        
        $customerName = $shippingAddress->name ?? ($orderData->customer->first_name . " " . $orderData->customer->last_name);
        if (empty(trim($customerName))) {
            $customerName = "N/A";
        }

        $payload = [
            "area" => $shareexArea,
            "name" => $customerName,
            "mobile" => $shippingAddress->phone ?? $orderData->customer->phone ?? "0000000000", 
            "address" => trim(($shippingAddress->address1 ?? "") . " " . ($shippingAddress->address2 ?? "")) ?: "N/A",
            "notes" => $orderData->note ?? "",
            "orderid" => (string) $orderData->id,
            "cash" => ($orderData->financial_status === "pending" || strtolower($orderData->gateway ?? "") === "cash_on_delivery" || strtolower($orderData->gateway ?? "") === "cod") ? $orderData->total_price : "0",
        ];

        Log::info("OrdersPaidJob: Attempting to send shipment to Shareex for order ID: " . $orderData->id, ["payload" => $payload]);
        $response = $shareexApiService->sendShipment($payload);

        $logEntry = [
            "shop_id" => $shop->id,
            "shopify_order_id" => (string) $orderData->id,
            "action" => "SendShipment",
            "request_payload" => json_encode($payload),
            "response_payload" => json_encode($response), 
        ];

        if ($response && isset($response["raw_response"])) {
            $rawResponse = trim($response["raw_response"]);
            if (is_numeric($rawResponse)) {
                $logEntry["status"] = "success";
                $logEntry["shareex_serial_number"] = $rawResponse; // Corrected column name
                Log::info("OrdersPaidJob: Shipment created successfully for order ID: " . $orderData->id . ". Shareex Serial: " . $rawResponse);
            } else {
                $logEntry["status"] = "failed";
                $logEntry["error_message"] = "Shareex API Error: " . $rawResponse;
                Log::error("OrdersPaidJob: Failed to create shipment for order ID: " . $orderData->id . ". Error: " . $rawResponse);
            }
        } elseif ($response) { 
            $logEntry["status"] = "failed";
            $logEntry["error_message"] = "Shareex API returned unexpected response format.";
            Log::error("OrdersPaidJob: Failed to create shipment for order ID: " . $orderData->id . ". Unexpected response format.", ["response" => $response]);
        } else {
            $logEntry["status"] = "failed";
            $logEntry["error_message"] = "Shareex API request failed or returned null.";
            Log::error("OrdersPaidJob: Shareex API request failed or returned null for order ID: " . $orderData->id);
        }

        ShipmentLog::create($logEntry);
    }
}

