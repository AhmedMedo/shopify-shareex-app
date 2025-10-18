<?php

namespace App\Services\ShippingPartners\Shareex;

use App\Models\ShippingPartnerCredential;
use App\Services\ShippingPartners\Contracts\TransportInterface;
use App\Services\ShippingPartners\Helper\PartnerLogger;
use Illuminate\Support\Facades\Http;

class ShareexTransport implements TransportInterface
{
    public function __construct(
        private readonly ShippingPartnerCredential $credentials,
        private readonly PartnerLogger $logger,
        private readonly string $baseUrl
    ) {
    }

    public function request(string $endpoint, array $payload = [], string $method = 'POST'): mixed
    {
        $baseUrl = rtrim($this->credentials->base_url ?: $this->baseUrl, '/');
        $url = sprintf('%s/api/shipments.asmx/%s', $baseUrl, ltrim($endpoint, '/'));

        $requestData = array_merge($payload, [
            'uname' => $this->credentials->api_username,
            'upass' => $this->credentials->api_password,
        ]);

        $this->logger->debug('request', [
            'url' => $url,
            'payload' => $requestData,
            'method' => $method,
        ]);

        $response = match (strtolower($method)) {
            'get' => Http::acceptJson()->get($url, $requestData),
            default => Http::acceptJson()->post($url, $requestData),
        };

        if ($response->failed()) {
            $this->logger->error('request_failed', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }
}

