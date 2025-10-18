<?php

namespace App\Services\ShippingPartners\Contracts;

use App\DataTransferObjects\Shipping\Requests\ShipmentRequestData;

interface ShippingPartnerInterface
{
    public function buildShipmentRequest(\App\Models\ShopifyOrder $order): ShipmentRequestData;

    public function sendShipment(ShipmentRequestData $shipmentRequest): mixed;

    public function getLastStatus(string $trackingReference): mixed;

    public function getShipmentHistory(string $trackingReference): mixed;
}

