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
use Osiset\ShopifyApp\Exceptions\InvalidShopDomainException;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use stdClass;

class OrdersPaidJob implements ShouldQueue
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

    public function handle(): void
    {
        Log::debug('order data', [
            'data' => $this->data,
            'shopDomain' => $this->shopDomain
        ]);
        Log::info("OrdersPaidJob: Handling webhook for shop " . $this->shopDomain->toNative() . " and order ID " . $this->data->id);

        $shop = ShopifyStore::where("name", $this->shopDomain->toNative())->first();

        if (!$shop) {
            Log::error("OrdersPaidJob: Shop not found with domain: " . $this->shopDomain->toNative());
            return;
        }

        $shareexApiService = new ShareexApiService($shop);
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

        $pieces = 0;
        foreach ($orderData->line_items as $item) {
            $pieces += $item->quantity;
        }

        $amount = $orderData->total_price ?? '0.00';

        $payload = [
            "clientref" => "cr02",
            "area" => 'المقطم',
            "name" => $customerName,
            "tel" => $shippingAddress->phone ?? $orderData->customer->phone ?? "0000000000",
            "address" => trim(($shippingAddress->address1 ?? "") . " " . ($shippingAddress->address2 ?? "")) ?: "N/A",
            "remarks" => $orderData->note ?? "",
            "pieces" => $pieces,
            "amount" => $amount,
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

       if ($response && isset($response["d"])) {
            $decoded = json_decode($response['d'], true);
            $serial = $decoded[0]['serial'] ?? null;

            $logEntry["status"] = "success";
            $logEntry["mubasher_serial_number"] =$serial; // Corrected column name
            Log::info('OrdersPaidJob: Successfully sent shipment to Shareex for order ID: ' . $orderData->id . ' with serial: ' . $serial);
        } else {
            $logEntry["status"] = "failed";
            $logEntry["error_message"] = "Shareex API request failed or returned null.";
            Log::error("OrdersPaidJob: Shareex API request failed or returned null for order ID: " . $orderData->id);
        }

        ShipmentLog::create($logEntry);
    }
}

