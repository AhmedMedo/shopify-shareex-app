<?php

use App\Enum\ShippingPartnerEnum;

return [
    'default_partner' => ShippingPartnerEnum::SHAREEX->value,

    'partners' => [
        ShippingPartnerEnum::SHAREEX->value => [
            'class' => App\Services\ShippingPartners\Shareex\ShareexShippingPartner::class,
            'logger_channel' => 'shareex',
            'transport' => 'rest',
            'base_url' => env('SHAREEX_BASE_URL', 'https://shareex.co'),
        ],
        ShippingPartnerEnum::MAGEX->value => [
            'class' => App\Services\ShippingPartners\Magex\MagexShippingPartner::class,
            'logger_channel' => 'magex',
            'transport' => 'soap',
            'base_url' => env('MAGEX_BASE_URL', 'https://system.magexeg.com/webservice/shipments.asmx'),
        ],
    ],
];

