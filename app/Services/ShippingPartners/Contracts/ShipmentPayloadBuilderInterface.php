<?php

namespace App\Services\ShippingPartners\Contracts;

use App\Models\ShopifyOrder;

interface ShipmentPayloadBuilderInterface
{
    public function buildPayload(ShopifyOrder $order, ?string $resolvedCity = null): array;
}

