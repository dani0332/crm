<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EmailStatusTypeEnum;
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
        try {
            LoggerService::info(self::class.' - handleInbound: WhatsApp inbound webhook received', [
                'time' => now()->toIso8601String(),
            ]);

            $payload = $request->input('payload', []);
            if (empty($payload)) {
                LoggerService::warning(self::class.' - handleInbound: Payload is empty');

                return apiResponse([], Response::HTTP_BAD_REQUEST, 'Webhook payload is empty.');
            }

            $messageId = $payload['messageId'] ?? null;
            $status = $payload['type'] ?? $payload['status'] ?? 'inbound';
            $mobile = $this->extractMobile($payload);

            if (! $messageId || ! $mobile) {
                LoggerService::warning(self::class.' - handleInbound: Required fields missing', [
                    'messageId' => $messageId,
                    'hasMobile' => ! empty($mobile),
                ]);

                return apiResponse([], Response::HTTP_BAD_REQUEST, 'Invalid payload: messageId and mobile are required.');
            }

            $this->dispatchJob($messageId, $status, $mobile, $payload['reason'] ?? null);
            LoggerService::info(self::class.' - handleInbound: Webhook processed successfully', [
                'messageId' => $messageId,
                'mobile' => $mobile,
            ]);

            return apiResponse([], Response::HTTP_OK, 'Webhook received successfully.');
        } catch (\Throwable $th) {
            LoggerService::error(self::class.' - handleInbound: Exception occurred', [
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'file' => $th->getFile(),
                'trace' => $th->getTraceAsString(),
            ]);
            throw $th;
        }
    }

    public function handleOutbound(Request $request): JsonResponse
    {
        try {
            LoggerService::info(self::class.' - handleOutbound: WhatsApp outbound webhook received', [
                'time' => now()->toIso8601String(),
            ]);

            $payload = $request->input('payload', []);
            if (empty($payload)) {
                LoggerService::warning(self::class.' - handleOutbound: Payload is empty');

                return apiResponse([], Response::HTTP_BAD_REQUEST, 'Webhook payload is empty.');
            }

            $messageId = $payload['id'] ?? null;
            $status = $payload['status'] ?? null;
            $mobile = $this->extractMobile($payload);

            if (! $messageId || ! $status || ! $mobile) {
                LoggerService::warning(self::class.' - handleOutbound: Required fields missing', [
                    'messageId' => $messageId,
                    'status' => $status,
                    'hasMobile' => ! empty($mobile),
                ]);

                return apiResponse([], Response::HTTP_BAD_REQUEST, 'Invalid payload: id, status and mobile are required.');
            }

            $this->dispatchJob($messageId, $status, $mobile, $payload['reason'] ?? null);
            LoggerService::info(self::class.' - handleOutbound: Webhook processed successfully', [
                'messageId' => $messageId,
                'mobile' => $mobile,
            ]);

            return apiResponse([], Response::HTTP_OK, 'Webhook received successfully.');
        } catch (\Throwable $th) {
            LoggerService::error(self::class.' - handleOutbound: Exception occurred', [
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'file' => $th->getFile(),
                'trace' => $th->getTraceAsString(),
            ]);
            throw $th;
        }
    }

    public function handleInteraction(Request $request): JsonResponse
    {
        try {
            LoggerService::info(self::class.' - handleInteraction: WhatsApp interaction webhook received', [
                'time' => now()->toIso8601String(),
            ]);

            $payload = $request->input('payload', []);
            if (empty($payload)) {
                LoggerService::warning(self::class.' - handleInteraction: Payload is empty');

                return apiResponse([], Response::HTTP_BAD_REQUEST, 'Webhook payload is empty.');
            }

            $messageId = $payload['messageId'] ?? null;
            $status = $payload['type'] ?? $payload['status'] ?? null;
            $mobile = $this->extractMobile($payload);

            if (! $messageId || ! $status || ! $mobile) {
                LoggerService::warning(self::class.' - handleInteraction: Required fields missing', [
                    'messageId' => $messageId,
                    'status' => $status,
                    'hasMobile' => ! empty($mobile),
                ]);

                return apiResponse([], Response::HTTP_BAD_REQUEST, 'Invalid payload: messageId, status, and mobile are required.');
            }

            $this->dispatchJob($messageId, $status, $mobile, $payload['reason'] ?? null);
            LoggerService::info(self::class.' - handleInteraction: Webhook processed successfully', [
                'messageId' => $messageId,
                'mobile' => $mobile,
            ]);

            return apiResponse([], Response::HTTP_OK, 'Webhook received successfully.');
        } catch (\Throwable $th) {
            LoggerService::error(self::class.' - handleInteraction: Exception occurred', [
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'file' => $th->getFile(),
                'trace' => $th->getTraceAsString(),
            ]);
            throw $th;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
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
        $messageData = (object) [
            'message_id' => $messageId,
            'status' => $status,
            'mobile' => $mobile,
            'type' => EmailStatusTypeEnum::WhatsApp->value,
            'reason' => $reason,
        ];

        WhatsAppMessageStatusJob::dispatch($messageData)->delay(Carbon::now()->addSeconds(self::DELAY_SECONDS));
        LoggerService::info(self::class.' - Job dispatched', [
            'messageId' => $messageId,
            'delay' => self::DELAY_SECONDS,
        ]);
    }
}
