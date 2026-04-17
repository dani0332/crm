<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;

class AdnicResponseHandler
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
     * Normalize HTTP response from ADNIC into consistent structure
     */
    public function parseHttpResponse(Response $response, string $apiKey): array
    {
        $responseObject = $response->object();
        $result = [
            'status' => false,
            'error' => $apiKey.' '.self::API_FAILED,
            'message' => 'There is an Exception on ADNIC API call.',
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
            || (isset($responseObject->ErrorInfo) && count($responseObject->ErrorInfo) > 0)
            || (isset($responseObject->DocumentInfo->ErrorInfo) && count($responseObject->DocumentInfo->ErrorInfo) > 0);
    }

    private function extractErrorMessage($responseObject, string $apiKey): string
    {
        $message = $apiKey.' '.self::API_FAILED;

        if ($responseObject !== null && isset($responseObject->DocumentInfo->ErrorInfo) && count($responseObject->DocumentInfo->ErrorInfo) > 0) {
            $message = json_encode($responseObject->DocumentInfo->ErrorInfo[0]->ErrorMsg) ?: $message;
        } elseif ($responseObject !== null && isset($responseObject->ErrorInfo) && count($responseObject->ErrorInfo) > 0) {
            $message = json_encode($responseObject->ErrorInfo[0]->ErrorMsg) ?: $message;
        } elseif (is_string($responseObject)) {
            $message = $responseObject;
        }

        return $message;
    }

}
