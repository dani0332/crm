<?php

namespace App\Services\OCR;

use App\Services\Logger\LoggerService;
use Illuminate\Http\Client\Response;

trait Ocrable
{
    private function handleResponse(Response $response, string $endpoint): array
    {
        $responseBody = $response->object();
        $status = $response->status();

        LoggerService::info(self::class.'::handleResponse');

        $result = [
            'ok' => false,
            'object' => $responseBody,
            'message' => 'Success',
        ];

        if ($response->successful()) {
            $result['ok'] = true;
        } elseif ($response->clientError()) {
            $result['message'] = "Client error occurred while calling OCR API (Status: {$status}): {$response->body()}";
        } elseif ($response->serverError()) {
            $result['message'] = "Server error occurred while calling OCR API (Status: {$status}): {$response->body()}";
        } else {
            $result['message'] = "Unexpected response from OCR API (Status: {$status}): {$response->body()}";
        }

        return $result;
    }
}
