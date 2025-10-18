<?php

namespace App\Services\ShippingPartners\Magex;

use App\DataTransferObjects\Shipping\Requests\ShipmentRequestData;
use App\Models\ShippingPartnerCredential;
use App\Models\ShopifyOrder;
use App\Services\ShippingPartners\Contracts\CityMapperInterface;
use App\Services\ShippingPartners\Contracts\ShipmentPayloadBuilderInterface;
use App\Services\ShippingPartners\Contracts\ShipmentResponseParserInterface;
use App\Services\ShippingPartners\Contracts\ShippingPartnerInterface;
use App\Services\ShippingPartners\Helper\PartnerLogger;

class MagexShippingPartner implements ShippingPartnerInterface
{
    public function __construct(
        private readonly ShippingPartnerCredential $credentials,
        private readonly MagexTransport $transport,
        private readonly ShipmentPayloadBuilderInterface $payloadBuilder,
        private readonly ShipmentResponseParserInterface $responseParser,
        private readonly CityMapperInterface $cityMapper,
        private readonly PartnerLogger $logger
    ) {
    }

    public function buildShipmentRequest(ShopifyOrder $order): ShipmentRequestData
    {
        $city = $this->cityMapper->resolveCity($order);
        $payload = $this->payloadBuilder->buildPayload($order, $city);

        return new ShipmentRequestData($order, $payload);
    }

    public function sendShipment(ShipmentRequestData $shipmentRequest): mixed
    {
        $response = $this->transport->send('SendShipment', $shipmentRequest->payload);
        $result = $this->responseParser->parseSendShipmentResponse($shipmentRequest->order, $shipmentRequest->payload, $response ?? []);

        $this->logger->info('sendShipment', [
            'success' => $result->success,
            'tracking' => $result->trackingNumber,
            'message' => $result->message,
        ]);

        return $result;
    }

    public function getLastStatus(string $trackingReference): mixed
    {
        $response = $this->transport->send('GetShipmentLastStatus', ['id' => $trackingReference]);
        $parsed = $this->responseParser->parseTrackingResponse($response ?? []);

        $this->logger->info('getLastStatus', [
            'tracking' => $trackingReference,
            'response' => $parsed,
        ]);

        return $parsed;
    }

    public function getShipmentHistory(string $trackingReference): mixed
    {
        $response = $this->transport->send('GetShipmentHistory', ['id' => $trackingReference]);
        $parsed = $this->responseParser->parseTrackingResponse($response ?? []);

        $this->logger->info('getShipmentHistory', [
            'tracking' => $trackingReference,
            'response' => $parsed,
        ]);

        return $parsed;
    }
}

