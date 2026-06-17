<?php

declare(strict_types=1);

namespace App\Jobs\CQF;

use App\Enums\ProcessStatusCode;
use App\Mail\NonCQF\SendFailedNonCQFRenewal;
use App\Models\RenewalsUploadLeads;
use App\Services\CQF\NonMotor\NonCQFRenewalBrevoMailService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class FinalizeNonMotorCQFLOBJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    // 360s = tries × (timeout + max_backoff) = 3 × (60 + 60), ensuring retries aren't deduplicated.
    public int $uniqueFor = 360;

    public function __construct(
        public int $renewalsUploadLeadsId
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->renewalsUploadLeadsId;
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(NonCQFRenewalBrevoMailService $brevoMailService): void
    {
        $lead = RenewalsUploadLeads::find($this->renewalsUploadLeadsId);
        if ($lead === null) {
            LoggerService::info(self::class.' - RenewalsUploadLeads not found', ['id' => $this->renewalsUploadLeadsId]);

            return;
        }

        $totalProcessed = (int) $lead->good + (int) $lead->cannot_upload;

        // Only write DB state on the first attempt; on retries the lead is
        // already COMPLETED/deleted, so we skip straight to the Bird call.
        if ($lead->status !== ProcessStatusCode::COMPLETED && ! $lead->is_deleted) {
            if ($totalProcessed > 0) {
                $lead->total_records = $totalProcessed;
                $lead->status = ProcessStatusCode::COMPLETED;
                $lead->save();
            } else {
                $lead->is_deleted = true;
                $lead->save();
            }
        }

        if ($lead->cannot_upload > 0 && $lead->status === ProcessStatusCode::COMPLETED) {
            $mail = new SendFailedNonCQFRenewal($this->renewalsUploadLeadsId);
            $mail->sendViaBrevo($brevoMailService);
        }
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::error(self::class.' - Finalize job failed permanently', [
            'renewalsUploadLeadsId' => $this->renewalsUploadLeadsId,
            'error' => $exception->getMessage(),
        ]);
    }
}
