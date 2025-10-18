<?php

namespace App\Providers;

use App\Services\Shareex\ShareexApiService;
use App\Services\ShippingPartners\Helper\PartnerLogger;
use App\Services\ShippingPartners\Magex\MagexCityMapper;
use App\Services\ShippingPartners\Magex\MagexPayloadBuilder;
use App\Services\ShippingPartners\Magex\MagexResponseParser;
use App\Services\ShippingPartners\Magex\MagexShippingPartner;
use App\Services\ShippingPartners\Magex\MagexTransport;
use App\Services\ShippingPartners\Shareex\ShareexCityMapper;
use App\Services\ShippingPartners\Shareex\ShareexPayloadBuilder;
use App\Services\ShippingPartners\Shareex\ShareexResponseParser;
use App\Services\ShippingPartners\Shareex\ShareexShippingPartner;
use App\Services\ShippingPartners\Shareex\ShareexTransport;
use App\Services\ShippingPartners\ShippingPartnerFactory;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

// Corrected Namespace and Class

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        $this->app->singleton(ShippingPartnerFactory::class, fn ($app) => new ShippingPartnerFactory($app));

        $this->app->bind(ShareexShippingPartner::class, function ($app, array $params = []) {
            $credentials = $params['credentials'];
            $config = $params['config'];
            $logger = $params['logger'];

            return new ShareexShippingPartner(
                credentials: $credentials,
                transport: new ShareexTransport($credentials, $logger, $config['base_url']),
                payloadBuilder: new ShareexPayloadBuilder(),
                responseParser: new ShareexResponseParser(),
                cityMapper: new ShareexCityMapper($app->make(\App\Services\ShippingCityMapperService::class)),
                logger: $logger
            );
        });

        $this->app->bind(MagexShippingPartner::class, function ($app, array $params = []) {
            $credentials = $params['credentials'];
            $config = $params['config'];
            $logger = $params['logger'];

            return new MagexShippingPartner(
                credentials: $credentials,
                transport: new MagexTransport($credentials, $logger, $config['base_url']),
                payloadBuilder: new MagexPayloadBuilder(),
                responseParser: new MagexResponseParser(),
                cityMapper: new MagexCityMapper($app->make(\App\Services\ShippingCityMapperService::class)),
                logger: $logger
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

    }
}

