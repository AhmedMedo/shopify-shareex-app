<?php

namespace App\Services;

use App\Enum\ShippingStatusEnum;
use App\Models\ShipmentLog;
use App\Models\ShopifyOrder;
use App\Services\ShippingPartners\Contracts\ShippingPartnerInterface;
use App\Services\ShippingPartners\ShippingPartnerFactory;
use Illuminate\Support\Facades\Log;

class ShippingService
{
    public function __construct(
        private readonly ShippingPartnerFactory $partnerFactory
    ) {
    }

    public function dispatchShipment(ShopifyOrder $order): bool
    {
        $partner = $this->resolvePartner($order);
        $request = $partner->buildShipmentRequest($order);

        $result = $partner->sendShipment($request);

        if ($result->success) {
            $order->update([
                'shipping_status' => ShippingStatusEnum::SHIPPED->value,
                'shipping_serial' => $result->trackingNumber,
            ]);

            $this->logShipment($order, 'SendShipment', 'success', $request->payload, $result->meta, $result->message);
            return true;
        }

        $order->update([
            'shipping_status' => ShippingStatusEnum::FAILED->value,
        ]);

        $this->logShipment($order, 'SendShipment', 'failed', $request->payload, $result->meta, $result->message);

        return false;
    }

    public function fetchLastStatus(ShopifyOrder $order): array
    {
        $partner = $this->resolvePartner($order);
        return $partner->getLastStatus($order->shipping_serial);
    }

    public function fetchHistory(ShopifyOrder $order): array
    {
        $partner = $this->resolvePartner($order);
        return $partner->getShipmentHistory($order->shipping_serial);
    }

    private function resolvePartner(ShopifyOrder $order): ShippingPartnerInterface
    {
        return $this->partnerFactory->buildForShop($order->shop_id);
    }

    private function logShipment(
        ShopifyOrder $order,
        string $action,
        string $status,
        array $requestPayload,
        array $responsePayload,
        ?string $message
    ): void {
        ShipmentLog::create([
            'shop_id' => $order->shop_id,
            'shopify_order_id' => (string) $order->order_id,
            'shareex_serial_number' => $order->shipping_serial,
            'action' => $action,
            'status' => $status,
            'request_payload' => json_encode($requestPayload),
            'response_payload' => json_encode($responsePayload),
            'error_message' => $message,
        ]);

        Log::info('shipment_log', [
            'order_id' => $order->order_id,
            'status' => $status,
            'message' => $message,
        ]);
    }
}

