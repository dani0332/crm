<?php

declare(strict_types=1);

namespace App\Services\MetLife;

use App\Services\Logger\LoggerService;
use Illuminate\Http\Client\Response;

class MetLifeResponseService
{
    public function createResponse(bool $success, string $message, array $data = []): array
    {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ];
    }

    public function handleUploadResponse(array $response, string $policyNumber, ?string $quoteUuid = null): array
    {
        $fileReference = $response['file_reference'] ?? $response['data']['file_reference'] ?? null;

        if ($fileReference) {
            LoggerService::info('MetLife upload successful', [
                'policy_number' => $policyNumber,
                'quote_uuid' => $quoteUuid,
                'file_reference' => $fileReference,
            ]);

            return $this->createResponse(
                true,
                'Document uploaded successfully',
                ['file_reference' => $fileReference, 'policy_number' => $policyNumber]
            );
        }

        $errorMessage = $response['message'] ?? 'Upload failed';
        LoggerService::warning('MetLife upload failed', [
            'policy_number' => $policyNumber,
            'quote_uuid' => $quoteUuid,
            'error' => $errorMessage,
        ]);

        return $this->createResponse(
            false,
            'Document upload failed: '.$errorMessage,
            ['policy_number' => $policyNumber]
        );
    }

    public function handleHttpResponse(Response $httpResponse, string $endpoint): array
    {
        $statusCode = $httpResponse->status();
        $responseBody = $httpResponse->json() ?? [];

        if ($httpResponse->successful()) {
            return $this->createResponse(
                true,
                'Request successful',
                ['status_code' => $statusCode, 'data' => $responseBody]
            );
        }

        $errorMessage = $responseBody['message'] ?? $httpResponse->body() ?? 'Request failed';
        LoggerService::warning('MetLife HTTP request failed', [
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'error' => $errorMessage,
        ]);

        return $this->createResponse(
            false,
            "Request failed (Status: {$statusCode}): {$errorMessage}",
            ['status_code' => $statusCode, 'endpoint' => $endpoint]
        );
    }

    public function handleExceptionResponse(\Exception $exception): array
    {
        LoggerService::warning('MetLife exception occurred', [
            'error' => $exception->getMessage(),
            'file' => basename($exception->getFile()),
            'line' => $exception->getLine(),
        ]);

        return $this->createResponse(
            false,
            'MetLife operation failed: '.$exception->getMessage(),
            ['exception_code' => $exception->getCode()]
        );
    }
}
