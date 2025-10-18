<?php

namespace App\Services\ShippingPartners\Magex;

use App\Models\ShopifyOrder;
use App\Services\ShippingCityMapperService;
use App\Services\ShippingPartners\Contracts\CityMapperInterface;

class MagexCityMapper implements CityMapperInterface
{
    public function __construct(private readonly ShippingCityMapperService $cityMapperService)
    {
    }

    public function resolveCity(ShopifyOrder $order): ?string
    {
        $shippingAddress = $order->shipping_address ?? [];

        return $this->cityMapperService->getShareexCity($shippingAddress);
    }
}

