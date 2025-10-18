<?php

namespace App\Services\ShippingPartners\Contracts;

use App\Models\ShopifyOrder;

interface CityMapperInterface
{
    public function resolveCity(ShopifyOrder $order): ?string;
}

