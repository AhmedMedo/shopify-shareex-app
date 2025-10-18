<?php

namespace App\DataTransferObjects\Shipping\Requests;

use App\Models\ShopifyOrder;

class ShipmentRequestData
{
    public function __construct(
        public readonly ShopifyOrder $order,
        public readonly array $payload
    ) {
    }
}

