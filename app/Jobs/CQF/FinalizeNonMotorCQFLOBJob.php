<?php

declare(strict_types=1);

namespace App\Jobs\CQF;

use App\Enums\ProcessStatusCode;
use App\Enums\RenewalProcessStatuses;
use App\Jobs\SendFailedNonMotorRenewalsJob;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FinalizeNonMotorCQFLOBJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public int $renewalsUploadLeadsId
    ) {}

    public function handle(): void
    {
        $lead = RenewalsUploadLeads::find($this->renewalsUploadLeadsId);
        if ($lead === null) {
            LoggerService::info(self::class . ' - RenewalsUploadLeads not found', ['id' => $this->renewalsUploadLeadsId]);

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

        $failedPolicyNumbers = RenewalQuoteProcess::where('renewals_upload_lead_id', $this->renewalsUploadLeadsId)
            ->where('status', RenewalProcessStatuses::BAD_DATA)
            ->pluck('policy_number')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (count($failedPolicyNumbers) > 0) {
            SendFailedNonMotorRenewalsJob::dispatch($failedPolicyNumbers, $this->renewalsUploadLeadsId);
            LoggerService::info(self::class . ' - Dispatched SendFailedNonMotorRenewalsJob', [
                'renewals_upload_lead_id' => $this->renewalsUploadLeadsId,
                'failed_count' => count($failedPolicyNumbers),
            ]);
        }
    }
}
