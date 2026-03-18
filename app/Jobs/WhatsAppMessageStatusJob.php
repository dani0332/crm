<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\EmailStatusTypeEnum;
use App\Models\EmailStatus;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WhatsAppMessageStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 15;
    public int $backoff = 300;

    /** @var object{message_id?: string, status?: string, mobile?: string, type?: string, reason?: string|null} */
    private object $messageData;

    /** @param  object{message_id: string, status: string, mobile: string, type: string, reason?: string|null}  $messageData */
    public function __construct(object $messageData)
    {
        $this->messageData = $messageData;
    }

    public function handle(): void
    {
        try {
            if (DB::getDefaultConnection() !== 'mysql') {
                DB::setDefaultConnection('mysql');
            }

            $messageId = $this->messageData->message_id ?? null;
            $status = $this->messageData->status ?? null;
            $mobile = $this->messageData->mobile ?? null;

            if (empty($messageId) || empty($status) || empty($mobile)) {
                LoggerService::info(self::class.' - Skipping: missing required fields', [
                    'message_id' => $messageId,
                    'status' => $status,
                    'mobile' => $mobile,
                ]);

                return;
            }

            $lockKey = "whatsapp_status:{$messageId}:{$mobile}:{$status}";

            $whatsAppType = EmailStatusTypeEnum::WhatsApp->value;

            Cache::lock($lockKey, 15)->block(5, function () use ($messageId, $status, $mobile, $whatsAppType): void {
                $existingRecord = EmailStatus::where('msg_id', $messageId)
                    ->where('type', $whatsAppType)
                    ->where('mobile', $mobile)
                    ->where('email_status', $status)
                    ->first();

                if ($existingRecord) {
                    LoggerService::info('WhatsAppMessageStatusJob - Record already exists', [
                        'msg_id' => $messageId,
                    ]);

                    return;
                }

                $baseRecord = EmailStatus::where('msg_id', $messageId)
                    ->where('type', $whatsAppType)
                    ->where('mobile', $mobile)
                    ->first();

                if ($baseRecord) {
                    $newRecord = new EmailStatus;
                    $newRecord->type = $whatsAppType;
                    $newRecord->mobile = $mobile;
                    $newRecord->msg_id = $messageId;
                    $newRecord->email_status = $status;
                    $newRecord->reason = $this->messageData->reason ?? $baseRecord->reason;
                    $newRecord->quote_type_id = $baseRecord->quote_type_id;
                    $newRecord->quote_id = $baseRecord->quote_id;
                    $newRecord->save();

                    Cache::forget("email_statuses_{$newRecord->quote_type_id}_{$newRecord->quote_id}");

                    LoggerService::info('WhatsAppMessageStatusJob - Status update created', [
                        'msg_id' => $messageId,
                        'status' => $status,
                    ]);

                    return;
                }

                throw new \RuntimeException(
                    "WhatsAppMessageStatusJob: No base EmailStatus for msg_id={$messageId} mobile={$mobile}. Will retry."
                );
            });
        } catch (\Throwable $th) {
            LoggerService::error(
                'WhatsAppMessageStatusJob failed',
                [
                    'message_id' => $this->messageData->message_id ?? null,
                    'status' => $this->messageData->status ?? null,
                    'mobile' => $this->messageData->mobile ?? null,
                ],
                $th
            );
            throw $th;
        }
    }
}
