<?php

namespace App\Services\ShippingPartners\Contracts;

use App\DataTransferObjects\Shipping\Responses\ShipmentSendResult;
use App\Models\ShopifyOrder;

interface ShipmentResponseParserInterface
{
    public function parseSendShipmentResponse(ShopifyOrder $order, array $payload, mixed $rawResponse): ShipmentSendResult;

    public function parseTrackingResponse(mixed $rawResponse): array;
}

