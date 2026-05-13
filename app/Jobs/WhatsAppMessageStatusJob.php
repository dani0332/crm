<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\EmailStatusTypeEnum;
use App\Models\EmailStatus;
use App\Services\EmailStatusService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WhatsAppMessageStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 2;
    public int $timeout = 15;
    public int $backoff = 15;
    protected array $messageData;

    public function __construct(array $messageData)
    {
        $this->messageData = $messageData;
    }

    public function handle(EmailStatusService $emailStatusService): void
    {
        try {
            if (DB::getDefaultConnection() !== 'mysql') {
                DB::setDefaultConnection('mysql');
            }

            $messageId = $this->messageData['message_id'] ?? null;
            $status = $this->messageData['status'] ?? null;
            $mobile = $this->messageData['mobile'] ?? null;

            if (empty($messageId) || empty($status) || empty($mobile)) {
                LoggerService::info(self::class.' - Skipping: missing required fields', [
                    'message_id' => $messageId,
                    'status' => $status,
                    'mobile' => $mobile,
                ]);

                return;
            }

            $lockKey = "whatsapp_status:{$messageId}:{$mobile}:{$status}";

            Cache::lock($lockKey, 15)->block(5, function () use ($messageId, $status, $mobile, $emailStatusService): void {
                $existingRecord = EmailStatus::where('msg_id', $messageId)
                    ->where('type', EmailStatusTypeEnum::WhatsApp)
                    ->where('mobile_no', $mobile)
                    ->where('email_status', $status)
                    ->first();

                if ($existingRecord) {
                    LoggerService::info('WhatsAppMessageStatusJob - Record already exists', [
                        'msg_id' => $messageId,
                    ]);

                    return;
                }

                $baseRecord = EmailStatus::where('msg_id', $messageId)
                    ->where('type', EmailStatusTypeEnum::WhatsApp)
                    ->where('mobile_no', $mobile)
                    ->first();

                if ($baseRecord) {
                    $newRecord = new EmailStatus;
                    $newRecord->type = EmailStatusTypeEnum::WhatsApp;
                    $newRecord->mobile_no = $mobile;
                    $newRecord->msg_id = $messageId;
                    $newRecord->email_status = $status;
                    $newRecord->email_subject = $baseRecord->email_subject;
                    $newRecord->reason = $this->messageData['reason'] ?? $baseRecord->reason;
                    $newRecord->quote_type_id = $baseRecord->quote_type_id;
                    $newRecord->quote_id = $baseRecord->quote_id;
                    $newRecord->flow_type = $this->messageData['flow_type'] ?? $baseRecord->flow_type;

                    $newRecord->save();
                    $emailStatusService->forgetEmailStatusListCache((int) $newRecord->quote_type_id, (int) $newRecord->quote_id);

                    LoggerService::info('WhatsAppMessageStatusJob - Status update created', [
                        'msg_id' => $messageId,
                        'status' => $status,
                    ]);

                    return;
                }

                if ($this->attempts() < $this->tries) {
                    LoggerService::info('WhatsAppMessageStatusJob - Base EmailStatus not found, retrying', [
                        'msg_id' => $messageId,
                        'mobile' => $mobile,
                        'status' => $status,
                        'attempt' => $this->attempts(),
                    ]);

                    $this->release($this->backoff);

                    return;
                }

                LoggerService::warning('WhatsAppMessageStatusJob - Base EmailStatus missing after max retries, skipping', [
                    'msg_id' => $messageId,
                    'mobile' => $mobile,
                    'status' => $status,
                    'attempts' => $this->attempts(),
                ]);
            });
        } catch (\Exception $exception) {
            LoggerService::warning(
                'WhatsAppMessageStatusJob failed - Retrying...',
                [
                    'message_id' => $this->messageData['message_id'] ?? null,
                    'status' => $this->messageData['status'] ?? null,
                    'mobile' => $this->messageData['mobile'] ?? null,
                    'exception' => $exception->getMessage(),
                ],
            );
        }
    }
}
