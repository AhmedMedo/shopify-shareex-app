<?php

namespace App\Services\ShippingPartners\Shareex;

use App\Models\ShopifyOrder;
use App\Services\ShippingPartners\Contracts\ShipmentPayloadBuilderInterface;

class ShareexPayloadBuilder implements ShipmentPayloadBuilderInterface
{
    public function buildPayload(ShopifyOrder $order, ?string $resolvedCity = null): array
    {
        $shippingAddress = $order->shipping_address ?? ($order->customer['default_address'] ?? []);

        return [
            'clientref' => $order->order_number,
            'area' => $resolvedCity ?? 'المقطم',
            'name' => $shippingAddress['name'] ?? trim(($order->customer['first_name'] ?? '') . ' ' . ($order->customer['last_name'] ?? '')),
            'tel' => $shippingAddress['phone'] ?? $order->customer['phone'] ?? '0000000000',
            'address' => trim(sprintf('%s %s', $shippingAddress['address1'] ?? '', $shippingAddress['address2'] ?? '')),
            'remarks' => $order->note ?? '',
            'pieces' => collect($order->line_items ?? [])->sum(fn ($item) => $item['quantity'] ?? 0),
            'amount' => $order->total_price ?? '0.00',
        ];
    }
}

