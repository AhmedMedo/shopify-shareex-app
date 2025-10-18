<?php

namespace App\Services\ShippingPartners\Contracts;

interface TransportInterface
{
    public function request(string $endpoint, array $payload = [], string $method = 'POST'): mixed;
}

