<?php

namespace App\Services\ShippingPartners\Shareex;

use App\DataTransferObjects\Shipping\Responses\ShipmentSendResult;
use App\Models\ShopifyOrder;
use App\Services\ShippingPartners\Contracts\ShipmentResponseParserInterface;

class ShareexResponseParser implements ShipmentResponseParserInterface
{
    public function parseSendShipmentResponse(ShopifyOrder $order, array $payload, mixed $rawResponse): ShipmentSendResult
    {
        $data = $rawResponse['d'] ?? null;
        if (!$data) {
            return new ShipmentSendResult(false, message: 'Invalid Shareex response payload.');
        }

        $decoded = json_decode($data, true);
        $serial = $decoded[0]['serial'] ?? null;

        if (!$serial) {
            return new ShipmentSendResult(false, message: 'Shareex serial missing');
        }

        return new ShipmentSendResult(true, $serial, meta: [
            'raw' => $rawResponse,
        ]);
    }

    public function parseTrackingResponse(mixed $rawResponse): array
    {
        $data = $rawResponse['d'] ?? null;
        if (!$data) {
            return [];
        }

        return json_decode($data, true) ?? [];
    }
}

