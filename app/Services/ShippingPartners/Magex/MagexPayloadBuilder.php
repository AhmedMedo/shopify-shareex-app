<?php

namespace App\Services\ShippingPartners\Magex;

use App\Models\ShopifyOrder;
use App\Services\ShippingPartners\Contracts\ShipmentPayloadBuilderInterface;

class MagexPayloadBuilder implements ShipmentPayloadBuilderInterface
{
    public function buildPayload(ShopifyOrder $order, ?string $resolvedCity = null): array
    {
        $shippingAddress = $order->shipping_address ?? ($order->customer['default_address'] ?? []);

        return [
            'clientref' => $order->order_number,
            'name' => $shippingAddress['name'] ?? trim(($order->customer['first_name'] ?? '') . ' ' . ($order->customer['last_name'] ?? '')),
            'area' => $resolvedCity ?? ($shippingAddress['city'] ?? ''),
            'address' => trim(sprintf('%s %s', $shippingAddress['address1'] ?? '', $shippingAddress['address2'] ?? '')),
            'tel' => $shippingAddress['phone'] ?? $order->customer['phone'] ?? '0000000000',
            'amount' => (float) ($order->total_price ?? 0),
            'remarks' => $order->note ?? '',
            'pieces' => (float) collect($order->line_items ?? [])->sum(fn ($item) => $item['quantity'] ?? 0),
        ];
    }
}

