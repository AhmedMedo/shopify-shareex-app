<?php

namespace App\Services\ShippingPartners;

use App\Models\ShippingPartnerCredential;
use App\Services\ShippingPartners\Contracts\ShippingPartnerInterface;
use App\Services\ShippingPartners\Helper\PartnerLogger;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class ShippingPartnerFactory
{
    public function __construct(private readonly Container $container)
    {
    }

    public function buildForShop(int $shopId): ShippingPartnerInterface
    {
        $credentials = ShippingPartnerCredential::where('shop_id', $shopId)->first();

        $partnerKey = Str::lower($credentials->partner ?? Config::get('shipping_partners.default_partner'));

        $partnerConfig = Arr::get(Config::get('shipping_partners.partners', []), $partnerKey);

        abort_if(!$partnerConfig, 422, sprintf('Shipping partner [%s] is not configured.', $partnerKey));

        $partnerClass = Arr::get($partnerConfig, 'class');

        abort_if(!class_exists($partnerClass), 422, sprintf('Shipping partner class [%s] missing.', $partnerClass));

        $loggerChannel = Arr::get($partnerConfig, 'logger_channel', 'stack');

        $logger = new PartnerLogger($loggerChannel);

        $partner = $this->container->make($partnerClass, [
            'credentials' => $credentials,
            'config' => $partnerConfig,
            'logger' => $logger,
        ]);

        if (!$partner instanceof ShippingPartnerInterface) {
            abort(500, sprintf('Resolved partner %s does not implement required interface.', $partnerClass));
        }

        return $partner;
    }
}

