<?php

namespace App\Services;

use GuzzleHttp\Promise\RejectedPromise;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class LoggingMiddleware
{


    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            Log::info('Request', [
                'method' => $request->getMethod(),
                'url' => (string) $request->getUri(),
                'headers' => $request->getHeaders(),
                'body' => (string) $request->getBody()
            ]);

            return $handler($request, $options)->then(
                function (ResponseInterface $response) use ($request) {
                    Log::info('Response', [
                        'status' => $response->getStatusCode(),
                        'headers' => $response->getHeaders(),
                        'body' => (string) $response->getBody()
                    ]);
                    return $response;
                },
                function (Throwable $exception) use ($request) {
                    Log::error('Request failed', [
                        'error' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString()
                    ]);
                    return new RejectedPromise($exception);
                }
            );
        };
    }
}
