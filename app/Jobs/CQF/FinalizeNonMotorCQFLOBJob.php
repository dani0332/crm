<?php

declare(strict_types=1);

namespace App\Jobs\CQF;

use App\Enums\ProcessStatusCode;
use App\Mail\NonCQF\SendFailedNonCQFRenewal;
use App\Models\RenewalsUploadLeads;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class FinalizeNonMotorCQFLOBJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 80;

    public function __construct(
        public int $renewalsUploadLeadsId
    ) {}

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        $lead = RenewalsUploadLeads::find($this->renewalsUploadLeadsId);
        if ($lead === null) {
            LoggerService::info(self::class.' - RenewalsUploadLeads not found', ['id' => $this->renewalsUploadLeadsId]);

            return;
        }

        $totalProcessed = (int) $lead->good + (int) $lead->cannot_upload;

        if ($totalProcessed > 0) {
            $lead->total_records = $totalProcessed;
            $lead->status = ProcessStatusCode::COMPLETED;
            $lead->save();
        } else {
            $lead->is_deleted = 1;
            $lead->save();
        }

        if ($lead->cannot_upload > 0) {
            $mail = new SendFailedNonCQFRenewal($this->renewalsUploadLeadsId);
            $mail->sendViaBird();
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
