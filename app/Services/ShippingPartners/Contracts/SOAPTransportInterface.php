<?php

namespace App\Services\ShippingPartners\Contracts;

interface SOAPTransportInterface extends TransportInterface
{
    public function send(string $action, array $parameters): mixed;
}

