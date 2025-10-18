<?php

namespace App\Services\ShippingPartners\Magex;

use App\DataTransferObjects\Shipping\Responses\ShipmentSendResult;
use App\Models\ShopifyOrder;
use App\Services\ShippingPartners\Contracts\ShipmentResponseParserInterface;

class MagexResponseParser implements ShipmentResponseParserInterface
{
    public function parseSendShipmentResponse(ShopifyOrder $order, array $payload, mixed $rawResponse): ShipmentSendResult
    {
        $result = $rawResponse['SendShipmentResult'] ?? null;

        if (!$result) {
            return new ShipmentSendResult(false, message: 'Magex response missing SendShipmentResult');
        }

        return new ShipmentSendResult(true, $result, meta: [
            'raw' => $rawResponse,
        ]);
    }

    public function parseTrackingResponse(mixed $rawResponse): array
    {
        return $rawResponse ?? [];
    }
}

