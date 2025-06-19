<?php

namespace App\Services\OCR;

use Illuminate\Http\Client\Response;

trait Ocrable
{
    private function handleResponse(Response $response, string $endpoint): array
    {
        $responseBody = $response->object();
        $status = $response->status();

        info(self::class.'::handleResponse', [
            'endpoint' => $endpoint,
            'status' => $status,
            'response' => $response->body(),
        ]);

        $result = [
            'ok' => false,
            'object' => $responseBody,
            'message' => 'Success',
        ];

        if ($response->successful()) {
            $result['ok'] = true;
        } elseif ($response->serverError()) {
            $result['message'] = "Server error occurred while calling OCR API: {$response->body()}";
        }

        return $result;
    }
}
