<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor;

use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Jobs\SendFailedNonMotorRenewalsJob;
use App\Models\PersonalQuote;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Services\CQF\NonMotor\Pipes\BaseValidationPipe;
use App\Services\CQF\NonMotor\Pipes\DuplicateCheckPipe;
use App\Services\CQF\NonMotor\Pipes\LOBValidationPipe;
use App\Services\CQF\NonMotor\Pipes\StoragePipe;
use App\Services\Logger\LoggerService;
use App\Services\RenewalsUploadService;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Sleep;

class NonMotorCQFRenewalExecutionService
{
    private int $totalQuotesProcessed = 0;
    private int $errorQuotes = 0;

    /** @var array<int, string> */
    private array $failedPolicyNumbers = [];

    public function __construct(
        protected NonMotorCQFRegistry $registry
    ) {}

    public function processNonMotorCQFRenewalLeads(?Carbon $startDate = null): void
    {
        $renewalDaysThreshold = (int) getAppStorageValueByKey(ApplicationStorageEnums::NON_MOTOR_CQF_RENEWALS_DAYS_THRESHOLD);
        $startDate = $startDate ?? Carbon::now()->addDays($renewalDaysThreshold);

        LoggerService::info(self::class." - Non-motor CQF renewal leads processing started with start date: {$startDate}");

        foreach (NonMotorCQFRegistry::supportedLOBs() as $quoteType) {
            $this->processLOB($quoteType, $startDate, $renewalDaysThreshold);
        }
    }

    protected function processLOB(QuoteTypes $quoteType, Carbon $startDate, int $renewalDaysThreshold): void
    {
        if (! $this->registry->hasLOB($quoteType)) {
            return;
        }

        $this->totalQuotesProcessed = 0;
        $this->errorQuotes = 0;
        $this->failedPolicyNumbers = [];

        $quoteTypeId = (int) $quoteType->id();
        $shortCode = str_replace('-', '', $quoteType->shortCode());

        $hasQuotes = PersonalQuote::whereDate('policy_expiry_date', $startDate)
            ->where('quote_type_id', $quoteTypeId)
            ->whereNotIn('quote_status_id', [
                QuoteStatusEnum::PolicyCancelled,
                QuoteStatusEnum::PolicyCancelledReissued,
                QuoteStatusEnum::CancellationPending,
            ])
            ->whereIn('payment_status_id', [
                PaymentStatusEnum::PAID,
                PaymentStatusEnum::PARTIALLY_PAID,
                PaymentStatusEnum::CAPTURED,
                PaymentStatusEnum::PARTIAL_CAPTURED,
            ])
            ->exists();

        if (! $hasQuotes) {
            LoggerService::info(self::class." - No {$shortCode} quotes found for start date: {$startDate}");

            return;
        }

        $renewalsUploadLeads = $this->createRenewalsUploadLeads($shortCode);

        PersonalQuote::whereDate('policy_expiry_date', $startDate)
            ->where('quote_type_id', $quoteTypeId)
            ->whereNotIn('quote_status_id', [
                QuoteStatusEnum::PolicyCancelled,
                QuoteStatusEnum::PolicyCancelledReissued,
                QuoteStatusEnum::CancellationPending,
            ])
            ->whereIn('payment_status_id', [
                PaymentStatusEnum::PAID,
                PaymentStatusEnum::PARTIALLY_PAID,
                PaymentStatusEnum::CAPTURED,
                PaymentStatusEnum::PARTIAL_CAPTURED,
            ])
            ->with($this->getEagerLoadRelationsForLOB($quoteType))
            ->chunkById(100, function ($quotes) use ($renewalsUploadLeads, $renewalDaysThreshold, $quoteType) {
                $quoteCount = $quotes->count();
                LoggerService::info(self::class." - Processing {$quoteCount} {$quoteType->value} CQF renewal quotes in chunk");

                $this->processQuotesChunk($quotes, $renewalsUploadLeads, $renewalDaysThreshold, $quoteType);
            });

        $this->finalizeProcessing($renewalsUploadLeads);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, PersonalQuote>  $quotes
     */
    protected function processQuotesChunk($quotes, RenewalsUploadLeads $renewalsUploadLeads, int $renewalDaysThreshold, QuoteTypes $quoteType): void
    {
        $validator = $this->registry->getValidator($quoteType);
        $mapper = $this->registry->getMapper($quoteType);
        $storage = $this->registry->getStorage($quoteType);

        $pipes = [
            app(BaseValidationPipe::class),
            app(LOBValidationPipe::class),
            app(DuplicateCheckPipe::class),
            app(StoragePipe::class),
        ];

        foreach ($quotes as $quote) {
            LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::NON_MOTOR_CQF_RENEWALS);

            try {
                $this->totalQuotesProcessed++;

                $context = new CQFRenewalContext(
                    quote: $quote,
                    renewalsUploadLeads: $renewalsUploadLeads,
                    quoteType: $quoteType,
                    renewalDaysThreshold: $renewalDaysThreshold,
                    validator: $validator,
                    mapper: $mapper,
                    storage: $storage
                );

                $context = app(Pipeline::class)
                    ->send($context)
                    ->through($pipes)
                    ->thenReturn();

                if ($context->hasErrors()) {
                    $this->markQuoteAsFailed($quote, $renewalsUploadLeads, $context->validationErrors, $mapper);

                    continue;
                }

                if ($context->newQuote !== null) {
                    $this->markQuoteAsCompleted($quote, $renewalsUploadLeads, true);
                } else {
                    $this->markQuoteAsFailed($quote, $renewalsUploadLeads, ['storage' => 'Failed to create renewal quote'], $mapper);
                }
            } catch (\Throwable $e) {
                LoggerService::error('Error processing non-motor CQF renewal quote', exception: $e);
                $this->markQuoteAsFailed($quote, $renewalsUploadLeads, ['exception' => $e->getMessage()], $mapper ?? null);
            }

            Sleep::for(3)->seconds();
        }
    }

    protected function createRenewalsUploadLeads(string $quoteTypeShortCode): RenewalsUploadLeads
    {
        $uploadLeadData = [
            'renewal_import_code' => app(RenewalsUploadService::class)->generateRandomString(),
            'quote_type' => $quoteTypeShortCode,
            'file_name' => 'cqf_renewal_leads_'.uniqid().'_'.now()->format('Y-m-d_H-i-s').'.xlsx',
            'file_path' => null,
            'status' => ProcessStatusCode::UPLOADED,
            'good' => 0,
            'cannot_upload' => 0,
            'is_sic' => 0,
            'created_by_id' => null,
            'renewal_import_type' => RenewalsUploadType::CREATE_LEADS,
        ];

        return RenewalsUploadLeads::create($uploadLeadData);
    }

    protected function markQuoteAsCompleted(PersonalQuote $quote, RenewalsUploadLeads $renewalsUploadLeads, bool $status): void
    {
        RenewalsUploadLeads::where('id', $renewalsUploadLeads->id)->update(['good' => DB::raw('good+1')]);
        $renewalQuoteProcess = $this->createRenewalQuoteProcess($quote, $renewalsUploadLeads);
        $renewalQuoteProcess->status = RenewalProcessStatuses::PROCESSED;
        $renewalQuoteProcess->save();
        LoggerService::info(self::class.' - Renewal quote process created for quote');
    }

    /**
     * @param  array<string, string>  $validationErrors
     */
    protected function markQuoteAsFailed(PersonalQuote $quote, RenewalsUploadLeads $renewalsUploadLeads, array $validationErrors, $mapper): void
    {
        RenewalsUploadLeads::where('id', $renewalsUploadLeads->id)->update(['cannot_upload' => DB::raw('cannot_upload+1')]);
        $renewalQuoteProcess = $this->createRenewalQuoteProcess($quote, $renewalsUploadLeads);
        $renewalQuoteProcess->status = RenewalProcessStatuses::BAD_DATA;
        $renewalQuoteProcess->validation_errors = $validationErrors;
        if ($mapper !== null) {
            $renewalQuoteProcess->data = $mapper->mapFailedQuoteData($quote);
        } else {
            $renewalQuoteProcess->data = $quote->toArray();
        }
        $renewalQuoteProcess->save();

        $this->errorQuotes++;
        $this->failedPolicyNumbers[] = $quote->policy_number ?? 'unknown';
        LoggerService::info(self::class.' - Renewal quote process not created for quote (validation failed)');
    }

    protected function createRenewalQuoteProcess(PersonalQuote $quote, RenewalsUploadLeads $renewalsUploadLeads): RenewalQuoteProcess
    {
        return RenewalQuoteProcess::create([
            'renewals_upload_lead_id' => $renewalsUploadLeads->id,
            'quote_type' => $renewalsUploadLeads->quote_type,
            'quote_id' => $quote->id,
            'policy_number' => $quote->policy_number ?? null,
            'data' => $quote->toArray(),
            'batch' => null,
            'status' => RenewalProcessStatuses::NEW,
            'type' => RenewalsUploadType::CREATE_LEADS,
        ]);
    }

    private function finalizeProcessing(RenewalsUploadLeads $renewalsUploadLeads): void
    {
        if ($this->totalQuotesProcessed > 0) {
            $renewalsUploadLeads->status = ProcessStatusCode::COMPLETED;
            $renewalsUploadLeads->total_records = $this->totalQuotesProcessed;
            $renewalsUploadLeads->save();
        } else {
            $renewalsUploadLeads->is_deleted = 1;
            $renewalsUploadLeads->save();
        }

        if ($this->errorQuotes > 0) {
            SendFailedNonMotorRenewalsJob::dispatch($this->failedPolicyNumbers, $renewalsUploadLeads->id);
            LoggerService::info(self::class." - Non-motor CQF renewal leads processing completed with errors: {$this->errorQuotes}");
        } else {
            LoggerService::info(self::class.' - Non-motor CQF renewal leads processing completed');
        }
    }

    /**
     * @return array<int, string>
     */
    protected function getEagerLoadRelationsForLOB(QuoteTypes $quoteType): array
    {
        return match ($quoteType) {
            QuoteTypes::BIKE => ['bikeQuote', 'bikeQuote.bikeQuoteRequestDetail', 'insuranceProvider', 'currentlyInsuredWith', 'advisor'],
            default => ['insuranceProvider', 'currentlyInsuredWith', 'advisor'],
        };
    }
}
