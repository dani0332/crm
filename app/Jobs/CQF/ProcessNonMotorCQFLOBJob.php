<?php

declare(strict_types=1);

namespace App\Jobs\CQF;

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
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

class ProcessNonMotorCQFLOBJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    /**
     * @param  array{quote_status: array<int>, payment_status: array<int>}  $paymentAndStatusFilter
     */
    private const PAYMENT_AND_STATUS_FILTER = [
        'quote_status' => [
            QuoteStatusEnum::PolicyCancelled,
            QuoteStatusEnum::PolicyCancelledReissued,
            QuoteStatusEnum::CancellationPending,
        ],
        'payment_status' => [
            PaymentStatusEnum::PAID,
            PaymentStatusEnum::PARTIALLY_PAID,
            PaymentStatusEnum::CAPTURED,
            PaymentStatusEnum::PARTIAL_CAPTURED,
        ],
    ];

    public function __construct(
        public int $renewalsUploadLeadsId,
        public QuoteTypes $quoteType,
        public string $startDate,
        public int $renewalDaysThreshold
    ) {}

    protected function lobBatchName(): string
    {
        return 'Non Motor CQF Renewal - '.$this->quoteType->value;
    }

    public function handle(): void
    {
        if (! app(NonMotorCQFRegistry::class)->hasLOB($this->quoteType)) {
            LoggerService::info(self::class.' - LOB not supported', ['quoteType' => $this->quoteType->value]);

            return;
        }

        $startDate = Carbon::parse($this->startDate);
        $quoteTypeId = (int) $this->quoteType->id();
        $filter = self::PAYMENT_AND_STATUS_FILTER;
        $quoteJobs = [];

        $personalQuery = PersonalQuote::whereDate('policy_expiry_date', $startDate)
            ->where('quote_type_id', $quoteTypeId)
            ->whereNotIn('quote_status_id', $filter['quote_status'])
            ->whereIn('payment_status_id', $filter['payment_status']);

        $personalQuery->pluck('id')->each(function ($quoteId) use (&$quoteJobs): void {
            $quoteJobs[] = new ProcessNonMotorCQFQuoteJob(
                $quoteId,
                QuoteTypes::PERSONAL->value,
                $this->quoteType,
                $this->renewalsUploadLeadsId,
                $this->renewalDaysThreshold
            );
        });

        if ($this->quoteType === QuoteTypes::BIKE) {
            CarQuote::whereDate('policy_expiry_date', $startDate)
                ->whereIn('vehicle_type_id', VehicleTypeEnum::ids())
                ->whereNotIn('quote_status_id', $filter['quote_status'])
                ->whereIn('payment_status_id', $filter['payment_status'])
                ->pluck('id')
                ->each(function ($quoteId) use (&$quoteJobs): void {
                    $quoteJobs[] = new ProcessNonMotorCQFQuoteJob(
                        $quoteId,
                        QuoteTypes::CAR->value,
                        $this->quoteType,
                        $this->renewalsUploadLeadsId,
                        $this->renewalDaysThreshold
                    );
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
        Bus::batch($quoteJobs)
            ->name($batchName)
            ->finally(function () use ($renewalsUploadLeadsId) {
                FinalizeNonMotorCQFLOBJob::dispatch($renewalsUploadLeadsId);
            })
            ->allowFailures()
            ->onQueue('default')
            ->dispatch();

        LoggerService::info(self::class.' - Dispatched quote batch for LOB', [
            'quoteType' => $this->quoteType->value,
            'renewalsUploadLeadsId' => $this->renewalsUploadLeadsId,
            'quoteJobCount' => count($quoteJobs),
        ]);
    }
}
