<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;

class AwnicResponseHandler
{
    private const API_FAILED = 'API Failed';

    /**
     * Build step response array
     */
    public function buildStepResponse(string $step, bool $status = false, ?string $message = null, $error = null, $data = null): array
    {
        return [
            'status' => $status,
            'completed_step' => $step,
            'message' => $message,
            'error' => $error,
            'data' => $data,
        ];
    }

    /**
     * Normalize HTTP response from AWNI into consistent structure
     */
    public function parseHttpResponse(Response $response, string $apiKey): array
    {
        $responseObject = $response->object();
        $result = [
            'status' => false,
            'error' => $apiKey.' '.self::API_FAILED,
            'message' => 'There is an Exception on AWNI API call.',
            'data' => null,
            'completed_step' => null,
        ];

        if ($response->successful()) {
            if ($this->hasApiErrors($responseObject)) {
                $result = [
                    'status' => false,
                    'error' => $responseObject?->errorList ?? $apiKey.' '.self::API_FAILED,
                    'message' => $this->extractErrorMessage($responseObject, $apiKey),
                    'data' => $responseObject == null ? null : '',
                    'completed_step' => null,
                ];
            } else {
                $result = [
                    'status' => true,
                    'message' => 'API call successfully executed.',
                    'error' => null,
                    'data' => $responseObject,
                    'completed_step' => null,
                ];
            }
        } elseif ($response->status() === JsonResponse::HTTP_NOT_FOUND) {
            $result = [
                'status' => false,
                'error' => '404 Not Found',
                'message' => '404 Not Found',
                'data' => null,
                'completed_step' => null,
            ];
        }

        return $result;
    }

    private function hasApiErrors($responseObject): bool
    {
        return $responseObject == null
            || isset($responseObject->errorList)
            || (isset($responseObject->isSuccess) && strtoupper((string) $responseObject->isSuccess) === 'N');
    }

    private function extractErrorMessage($responseObject, string $apiKey)
    {
        if (isset($responseObject?->errorList)) {
            return json_encode($responseObject->errorList);
        }

        if (isset($responseObject?->message)) {
            return $responseObject->message;
        }

        return $responseObject ?? $apiKey.' '.self::API_FAILED;
    }
}
