<?php

namespace App\Services\ShippingPartners\Shareex;

use App\Models\ShopifyOrder;
use App\Services\ShippingCityMapperService;
use App\Services\ShippingPartners\Contracts\CityMapperInterface;

class ShareexCityMapper implements CityMapperInterface
{
    public function __construct(private readonly ShippingCityMapperService $cityMapperService)
    {
    }

    public function resolveCity(ShopifyOrder $order): ?string
    {
        return $this->cityMapperService->getShareexCity($order->shipping_address ?? []);
    }
}

