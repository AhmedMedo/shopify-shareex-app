<?php

namespace App\DataTransferObjects\Shipping\Responses;

class ShipmentSendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $trackingNumber = null,
        public readonly ?string $message = null,
        public readonly array $meta = []
    ) {
    }
}

