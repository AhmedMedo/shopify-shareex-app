<?php

namespace App\Enum;

enum ShippingStatusEnum: string
{
    case PENDING = 'pending';

    case READY_TO_SHIP = 'ready_to_ship';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
}
