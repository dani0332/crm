<?php

namespace App\Services\Traits;

use App\Models\EmbeddedTransaction;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;

trait Macrmable
{
    private static function retryLogic(Exception $exception): bool
    {
        info(self::class.'::retryLogic ', [
            'message' => 'API failed',
            'error' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'line' => $exception->getLine(),
        ]);

        $shouldRetry = $exception->getCode() !== 422;

        if ($shouldRetry) {
            info(self::class.'::retryLogic - Going to Retry Request...');
        }

        return $shouldRetry;
    }

    private static function handleResponse(Response $response, string $endpoint): array
    {
        $responseBody = $response->json();
        $responseMessage = $responseBody['message'] ?? 'Something Went Wrong.';
        $status = $response->status();

        // Log response details
        info(self::class.'::handleResponse - ', [
            'endpoint' => $endpoint,
            'status' => $status,
            'response' => $response->body(),
        ]);

        if ($response->successful()) {
            return [
                'ok' => true,
                'object' => $responseBody,
                'message' => $responseMessage,
            ];
        }

        if ($response->serverError()) {
            return [
                'ok' => false,
                'object' => $responseBody,
                'message' => 'Server error: '.$responseMessage,
            ];
        }

        if ($status === 404 && $responseMessage === 'Courier not found.') {
            return [
                'ok' => false,
                'object' => [
                    'success' => true,
                    'message' => $responseMessage,
                    'data' => ['status' => 'Pending'],
                ],
                'message' => $responseMessage,
            ];
        }

        return [
            'ok' => false,
            'object' => $responseBody,
            'message' => $responseMessage,
        ];
    }

    private static function verifySyncPreChecks($lead, $quoteTypeId, $leadData)
    {
        if (! $leadData) {
            info("No lead data found for UUID: {$lead->uuid} and QuoteTypeId: {$quoteTypeId}. Aborted.");

            return false;
        }

        if (empty($leadData['payment']['captured_at'])) {
            info("Payment Not captured for courier, for UUID: {$lead->uuid} and QuoteTypeId: {$quoteTypeId}. Aborted.");

            return false;
        }

        return true;
    }

    private static function getRefId(?array $leadData = null): ?string
    {
        if (! $leadData) {
            return null;
        }

        $leadData = Arr::dot($leadData);

        return $leadData['payment.ref_id'] ?? null;
    }

    private static function saveSyncResponse(string $refId, bool $ok, array $response, ?string $message = 'Unknown Error')
    {
        $embeddedTransaction = EmbeddedTransaction::where('code', $refId)->first();

        if (! $embeddedTransaction) {
            info(self::class."::saveSyncResponse - Embedded Transaction not found for RefId: {$refId}");

            return;
        }

        $embeddedTransaction->update([
            'courier_synced_at' => $ok ? now() : null,
            'courier_sync_failed_at' => $ok ? null : now(),
            'courier_sync_message' => $message,
            'courier_sync_response' => $response,
        ]);
    }
}
