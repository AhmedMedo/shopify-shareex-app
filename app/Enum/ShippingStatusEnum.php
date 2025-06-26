<?php

namespace App\Enum;

enum ShippingStatusEnum: string
{
    case PENDING = 'pending';

    case READY_TO_SHIP = 'ready_to_ship';

    case AWAINTING_FOR_SHIPPING_CITY = 'awaiting_for_shipping_city';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';

    case FAILED = 'failed';
}
