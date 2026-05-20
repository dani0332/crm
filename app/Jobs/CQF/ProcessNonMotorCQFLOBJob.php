<?php

declare(strict_types=1);

namespace App\Jobs\CQF;

use App\Enums\QuoteTypes;
use App\Enums\VehicleTypeEnum;
use App\Models\CarQuote;
use App\Models\PersonalQuote;
use App\Services\CQF\NonMotor\NonMotorCQFRegistry;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Throwable;

class ProcessNonMotorCQFLOBJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public const BATCH_NAME_PREFIX = 'Non Motor CQF Renewal';

    public function __construct(
        public int $renewalsUploadLeadsId,
        public QuoteTypes $quoteType,
        public string $startDate,
        public int $renewalDaysThreshold
    ) {
        $this->onQueue('default');
    }

    protected function lobBatchName(): string
    {
        return self::BATCH_NAME_PREFIX.' - '.$this->quoteType->value.' - '.now()->format(config('constants.DATE_FORMAT_ONLY'));
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        if (! app(NonMotorCQFRegistry::class)->hasLOB($this->quoteType)) {
            LoggerService::info(self::class.' - LOB not supported', ['quoteType' => $this->quoteType->value]);

            return;
        }

        $quoteTypeId = (int) $this->quoteType->id();
        $filter = NonMotorCQFRegistry::eligibilityFilter();
        $startDate = Carbon::parse($this->startDate);
        $quoteJobs = [];

        $personalQuery = NonMotorCQFRegistry::applyPaymentStatusFilter(
            PersonalQuote::query()
                ->whereDate('policy_expiry_date', $startDate)
                ->where('quote_type_id', $quoteTypeId)
                ->whereNotIn('quote_status_id', $filter['quote_status']),
            $this->quoteType,
            $filter
        );

        $personalQuery->chunkById(500, function ($quotes) use (&$quoteJobs): void {
            foreach ($quotes as $quote) {
                $quoteJobs[] = new ProcessNonMotorCQFQuoteJob(
                    $quote->id,
                    QuoteTypes::PERSONAL->value,
                    $this->quoteType,
                    $this->renewalsUploadLeadsId,
                    $this->renewalDaysThreshold
                );
            }
        });

        if ($this->quoteType === QuoteTypes::BIKE) {
            CarQuote::whereDate('policy_expiry_date', $startDate)
                ->whereIn('vehicle_type_id', VehicleTypeEnum::ids())
                ->whereNotIn('quote_status_id', $filter['quote_status'])
                ->whereIn('payment_status_id', $filter['payment_status'])
                ->chunkById(500, function ($quotes) use (&$quoteJobs): void {
                    foreach ($quotes as $quote) {
                        $quoteJobs[] = new ProcessNonMotorCQFQuoteJob(
                            $quote->id,
                            QuoteTypes::CAR->value,
                            $this->quoteType,
                            $this->renewalsUploadLeadsId,
                            $this->renewalDaysThreshold
                        );
                    }
                });
        }

        if (count($quoteJobs) === 0) {
            LoggerService::info(self::class.' - No quotes for LOB', [
                'quoteType' => $this->quoteType->value,
                'renewalsUploadLeadsId' => $this->renewalsUploadLeadsId,
            ]);
            FinalizeNonMotorCQFLOBJob::dispatch($this->renewalsUploadLeadsId);

            return;
        }

        $renewalsUploadLeadsId = $this->renewalsUploadLeadsId;
        $batchName = $this->lobBatchName();

        LoggerService::info(self::class.' - Dispatched quote batch for LOB', [
            'quoteType' => $this->quoteType->value,
            'renewalsUploadLeadsId' => $this->renewalsUploadLeadsId,
            'quoteJobCount' => count($quoteJobs),
        ]);

        Bus::batch($quoteJobs)
            ->name($batchName)
            ->finally(function () use ($renewalsUploadLeadsId) {
                FinalizeNonMotorCQFLOBJob::dispatch($renewalsUploadLeadsId);
            })
            ->allowFailures()
            ->dispatch();
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::error(self::class.' - Job failed', [
            'quoteType' => $this->quoteType->value,
            'renewalsUploadLeadsId' => $this->renewalsUploadLeadsId,
            'error' => $exception->getMessage(),
        ]);
    }
}
