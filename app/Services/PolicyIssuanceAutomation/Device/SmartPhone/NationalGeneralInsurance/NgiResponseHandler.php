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
     * @param  mixed  $error
     * @param  mixed  $data
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
     */
    public function parseHttpResponse(Response $response, string $apiKey): array
    {
        $responseObject = $response->object();
        $result = [
            'status' => false,
            'error' => $apiKey.' '.self::API_FAILED,
            'message' => 'There is an Exception on NGI API call.',
            'data' => null,
            'completed_step' => null,
        ];

        if ($response->successful()) {
            if ($this->hasApiErrors($responseObject)) {
                $result = [
                    'status' => false,
                    'error' => $responseObject?->errorCode ?? $apiKey.' '.self::API_FAILED,
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
     * @param  mixed  $responseObject
     */
    private function hasApiErrors($responseObject): bool
    {
        $hasErrors = false;

        if ($responseObject === null) {
            $hasErrors = true;
        }

        if (isset($responseObject->isSuccess) && $responseObject->isSuccess === false) {
            $hasErrors = true;
        }

        if (isset($responseObject->errorCode) && ! empty($responseObject->errorCode)) {
            $hasErrors = true;
        }

        return $hasErrors;
    }

    /**
     * Extract error message from response
     *
     * @param  mixed  $responseObject
     */
    private function extractErrorMessage($responseObject, string $apiKey): string
    {
        $errorMessage = $apiKey.' '.self::API_FAILED;

        if (isset($responseObject?->statusMessage) && ! empty($responseObject->statusMessage)) {
            $errorMessage .= ' '.$responseObject->statusMessage;
        }

        if (isset($responseObject?->errorCode) && ! empty($responseObject->errorCode)) {
            $errorMessage .= ' '.$responseObject->errorCode;
        }

        if (isset($responseObject?->message)) {
            $errorMessage .= ' '.$responseObject->message;
        }

        return $errorMessage;
    }
}
