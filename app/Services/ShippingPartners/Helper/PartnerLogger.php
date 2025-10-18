<?php

namespace App\Services\ShippingPartners\Helper;

use Illuminate\Support\Facades\Log;

class PartnerLogger
{
    public function __construct(private readonly string $channel)
    {
    }

    public function info(string $method, array $context = []): void
    {
        Log::channel($this->channel)->info($method, $context);
    }

    public function error(string $method, array $context = []): void
    {
        Log::channel($this->channel)->error($method, $context);
    }

    public function debug(string $method, array $context = []): void
    {
        Log::channel($this->channel)->debug($method, $context);
    }
}

