<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Logger\LoggerService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Instant Writer AI API Service
 *
 * Service for handling Instant Writer AI API requests
 * Manages AI content generation, text analysis, and AI-powered writing operations
 */
class InstantWriterAIAPIService
{
    private $client = null;
    private $baseUrl = null;

    private const INSTANT_WRITER_AI_API_EXCEPTION_MESSAGE = 'Instant Writer AI API Service Exception';

    /**
     * Setup HTTP client with Instant Writer AI API credentials
     */
    public function __construct()
    {
        $this->baseUrl = config('constants.INSTANT_WRITER_AI_API_ENDPOINT');

        $this->client = Http::withBasicAuth(
            config('constants.INSTANT_WRITER_AI_API_USER'),
            config('constants.INSTANT_WRITER_AI_API_PWD')
        )
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'x-api-token' => config('constants.INSTANT_WRITER_AI_API_TOKEN'),
            ])
            ->timeout((int) config('constants.INSTANT_WRITER_AI_API_TIMEOUT'));
    }

    /**
     * Send request to Instant Writer AI API
     *
     * @param  string  $path  API endpoint path
     * @param  string  $method  HTTP method (GET, POST, PUT, DELETE)
     * @param  array  $data  Request payload
     * @param  bool  $isContentGeneration  Whether this is a content generation operation
     *
     * @throws \Exception
     */
    public function request(string $path, string $method = 'post', array $data = [], bool $isContentGeneration = false): object
    {
        $url = $this->baseUrl.$path;

        LoggerService::info('Instant Writer AI API Request initiated', extra: [
            'url' => $url,
            'method' => strtoupper($method),
            'payload_size' => count($data),
            'is_content_generation' => $isContentGeneration,
        ]);

        $response = $this->client
            ->withBody(json_encode($data), 'application/json')
            ->send($method, $url)
            ->onError(function (Response $response) use ($data, $url, $isContentGeneration) {
                $errorMessage = $response->json()['msg'] ??
                               $response->json()['message'] ??
                               $response->json()['error'] ??
                               self::INSTANT_WRITER_AI_API_EXCEPTION_MESSAGE;

                // Log all 4XX and 5XX errors, and content generation operations
                if ($response->status() >= 400 || $isContentGeneration) {
                    LoggerService::error(self::INSTANT_WRITER_AI_API_EXCEPTION_MESSAGE, extra: [
                        'data' => $data,
                        'url' => $url,
                        'response_status' => $response->getStatusCode(),
                        'response_message' => $errorMessage,
                        'is_content_generation' => $isContentGeneration,
                        'response_body' => $response->body(),
                        'jsonResponse' => $response->json(),
                    ]);

                    if ($errorMessage) {
                        vAbort($errorMessage);
                    } else {
                        vAbort(self::INSTANT_WRITER_AI_API_EXCEPTION_MESSAGE);
                    }
                }
            });

        LoggerService::info('Instant Writer AI API Request completed successfully', extra: [
            'url' => $url,
            'response_status' => $response->status(),
            'response_size' => strlen($response->body()),
        ]);

        return (object) $response->json();
    }

}
