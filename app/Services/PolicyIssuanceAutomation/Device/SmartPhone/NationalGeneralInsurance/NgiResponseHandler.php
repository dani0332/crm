<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance;

use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;

class NgiResponseHandler
{
    private const API_FAILED = 'API Failed';

    /**
     * Build step response array
     *
     * @param string $step
     * @param bool $status
     * @param string|null $message
     * @param mixed $error
     * @param mixed $data
     * @return array
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
     * Normalize HTTP response from NGI into consistent structure
     *
     * @param Response $response
     * @param string $apiKey
     * @return array
     */
    public function parseHttpResponse(Response $response, string $apiKey): array
    {
        $responseObject = $response->object();
        $result = [
            'status' => false,
            'error' => $apiKey . ' ' . self::API_FAILED,
            'message' => 'There is an Exception on NGI API call.',
            'data' => null,
            'completed_step' => null,
        ];

        if ($response->successful()) {
            if ($this->hasApiErrors($responseObject)) {
                $result = [
                    'status' => false,
                    'error' => $responseObject?->errorCode ?? $apiKey . ' ' . self::API_FAILED,
                    'message' => $this->extractErrorMessage($responseObject, $apiKey),
                    'data' => $responseObject == null ? null : '',
                    'completed_step' => null,
                ];
            } else {
                $result = [
                    'status' => true,
                    'message' => $responseObject?->statusMessage ?? 'API call successfully executed.',
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

    /**
     * Check if response contains API errors
     *
     * @param mixed $responseObject
     * @return bool
     */
    private function hasApiErrors($responseObject): bool
    {
        if ($responseObject === null) {
            return true;
        }

        // NGI uses isSuccess boolean field
        if (isset($responseObject->isSuccess) && $responseObject->isSuccess === false) {
            return true;
        }

        // Check for error code
        if (isset($responseObject->errorCode) && ! empty($responseObject->errorCode)) {
            return true;
        }

        return false;
    }

    /**
     * Extract error message from response
     *
     * @param mixed $responseObject
     * @param string $apiKey
     * @return string
     */
    private function extractErrorMessage($responseObject, string $apiKey): string
    {
        if (isset($responseObject?->statusMessage) && ! empty($responseObject->statusMessage)) {
            return $responseObject->statusMessage;
        }

        if (isset($responseObject?->errorCode) && ! empty($responseObject->errorCode)) {
            return $responseObject->errorCode;
        }

        if (isset($responseObject?->message)) {
            return $responseObject->message;
        }

        return $apiKey . ' ' . self::API_FAILED;
    }
}
