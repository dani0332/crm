<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\WhatsAppMessageStatusJob;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppHookService
{
    private const DELAY_SECONDS = 60;

    public function handleInbound(Request $request): JsonResponse
    {
        return $this->processWebhook(
            $request,
            'handleInbound',
            'inbound',
            static function (array $payload): array {
                return [
                    'messageId' => $payload['messageId'] ?? null,
                    'status' => self::nonBlankStringOrNull($payload['type'] ?? null)
                        ?? self::nonBlankStringOrNull($payload['status'] ?? null)
                        ?? 'inbound',
                ];
            },
            invalidFieldsMessage: 'Invalid payload: messageId and mobile are required.',
        );
    }

    public function handleOutbound(Request $request): JsonResponse
    {
        return $this->processWebhook(
            $request,
            'handleOutbound',
            'outbound',
            static function (array $payload): array {
                return [
                    'messageId' => $payload['id'] ?? null,
                    'status' => self::nonBlankStringOrNull($payload['status'] ?? null),
                ];
            },
            invalidFieldsMessage: 'Invalid payload: id, status and mobile are required.',
        );
    }

    public function handleInteraction(Request $request): JsonResponse
    {
        return $this->processWebhook(
            $request,
            'handleInteraction',
            'interaction',
            static function (array $payload): array {
                return [
                    'messageId' => $payload['messageId'] ?? null,
                    'status' => self::nonBlankStringOrNull($payload['type'] ?? null)
                        ?? self::nonBlankStringOrNull($payload['status'] ?? null),
                ];
            },
            invalidFieldsMessage: 'Invalid payload: messageId, status, and mobile are required.',
        );
    }

    /**
     * @param  callable(array<string, mixed>): array{messageId: ?string, status: ?string}  $extractMessageFields
     */
    private function processWebhook(
        Request $request,
        string $handlerLabel,
        string $channelDescriptor,
        callable $extractMessageFields,
        string $invalidFieldsMessage,
    ): JsonResponse {
        try {
            LoggerService::info(self::class." - {$handlerLabel}: WhatsApp {$channelDescriptor} webhook received", [
                'time' => now()->toIso8601String(),
            ]);

            $payload = $request->input('payload', []);
            if (empty($payload)) {
                LoggerService::warning(self::class." - {$handlerLabel}: Payload is empty");

                return apiResponse([], Response::HTTP_BAD_REQUEST, 'Webhook payload is empty.');
            }

            $extracted = $extractMessageFields($payload);
            $messageId = $extracted['messageId'] ?? null;
            $status = $extracted['status'] ?? null;
            $mobile = $this->extractMobile($payload);

            $statusMissingOrEmpty = ! is_string($status) || $status === '';
            $missingCore = ! $messageId || ! $mobile || $statusMissingOrEmpty;

            if ($missingCore) {
                LoggerService::warning(self::class." - {$handlerLabel}: Required fields missing", [
                    'messageId' => $messageId,
                    'status' => $status,
                    'hasMobile' => ! empty($mobile),
                ]);

                return apiResponse([], Response::HTTP_BAD_REQUEST, $invalidFieldsMessage);
            }

            $this->dispatchJob((string) $messageId, (string) $status, $mobile, $payload['reason'] ?? null);
            LoggerService::info(self::class." - {$handlerLabel}: Webhook processed successfully", [
                'messageId' => $messageId,
                'mobile' => $mobile,
            ]);

            return apiResponse([], Response::HTTP_OK, 'Webhook received successfully.');
        } catch (\Throwable $th) {
            LoggerService::error(self::class." - {$handlerLabel}: Exception occurred", [
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'file' => $th->getFile(),
                'trace' => $th->getTraceAsString(),
            ]);
            throw $th;
        }
    }

    /**
     * Treat null, non-strings, and blank (whitespace-only) strings as absent so ?? / validation behave correctly.
     */
    private static function nonBlankStringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function extractMobile(array $payload): ?string
    {
        $contacts = $payload['receiver']['contacts'] ?? [];
        $firstContact = is_array($contacts) ? reset($contacts) : null;

        if (! is_array($firstContact)) {
            return null;
        }

        $identifierValue = $firstContact['identifierValue'] ?? null;

        if (empty($identifierValue) || ! is_string($identifierValue)) {
            return null;
        }

        return formatMobileNoWithoutPlus($identifierValue);
    }

    private function dispatchJob(string $messageId, string $status, string $mobile, ?string $reason = null): void
    {
        $messageData = [
            'message_id' => $messageId,
            'status' => $status,
            'mobile' => $mobile,
            'reason' => $reason,
        ];

        WhatsAppMessageStatusJob::dispatch($messageData)->delay(Carbon::now()->addSeconds(self::DELAY_SECONDS));
        LoggerService::info(self::class.' - Job dispatched', [
            'messageId' => $messageId,
            'delay' => self::DELAY_SECONDS,
        ]);
    }
}
