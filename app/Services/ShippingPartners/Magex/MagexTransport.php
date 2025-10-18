<?php

namespace App\Services\ShippingPartners\Magex;

use App\Models\ShippingPartnerCredential;
use App\Services\ShippingPartners\Contracts\SOAPTransportInterface;
use App\Services\ShippingPartners\Helper\PartnerLogger;
use Illuminate\Support\Facades\Http;

class MagexTransport implements SOAPTransportInterface
{
    public function __construct(
        private readonly ShippingPartnerCredential $credentials,
        private readonly PartnerLogger $logger,
        private readonly string $baseUrl
    ) {
    }

    public function request(string $endpoint, array $payload = [], string $method = 'POST'): mixed
    {
        return $this->send($endpoint, $payload);
    }

    public function send(string $action, array $parameters): mixed
    {
        $envelope = $this->buildEnvelope($action, $parameters);

        $this->logger->debug('soap_request', [
            'action' => $action,
            'body' => $envelope,
        ]);

        $response = Http::withHeaders([
            'Content-Type' => 'application/soap+xml; charset=utf-8',
        ])->send('POST', $this->baseUrl, [
            'body' => $envelope,
        ]);

        if ($response->failed()) {
            $this->logger->error('soap_error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $parsed = $this->parseResponse($response->body(), $action);

        $this->logger->debug('soap_response', [
            'action' => $action,
            'parsed' => $parsed,
        ]);

        return $parsed;
    }

    private function buildEnvelope(string $action, array $parameters): string
    {
        $xmlParams = '';
        foreach ($parameters as $key => $value) {
            $xmlParams .= sprintf('<%1$s>%2$s</%1$s>', $key, htmlspecialchars((string) $value));
        }

        $xmlParams .= sprintf('<uname>%s</uname>', htmlspecialchars((string) $this->credentials->api_username));
        $xmlParams .= sprintf('<upass>%s</upass>', htmlspecialchars((string) $this->credentials->api_password));

        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <{$action} xmlns="http://tempuri.org/">
      {$xmlParams}
    </{$action}>
  </soap12:Body>
</soap12:Envelope>
XML;
    }

    private function parseResponse(string $body, string $action): ?array
    {
        $xml = simplexml_load_string($body, null, LIBXML_NOCDATA);
        if (!$xml) {
            return null;
        }

        $namespaces = $xml->getNamespaces(true);
        $soapBody = $xml->children($namespaces['soap12'] ?? $namespaces['soap'] ?? null)->Body ?? null;
        if (!$soapBody) {
            return null;
        }

        $responseNode = $soapBody->children('http://tempuri.org/')->{$action . 'Response'} ?? null;
        if (!$responseNode) {
            return null;
        }

        $resultNode = $responseNode->{$action . 'Result'} ?? null;

        return $resultNode ? [
            $action . 'Result' => (string) $resultNode,
        ] : null;
    }
}

