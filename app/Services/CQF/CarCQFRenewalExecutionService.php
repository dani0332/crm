<?php

declare(strict_types=1);

namespace App\Services\CQF;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Jobs\SendFailedCarRenewalsJob;
use App\Models\CarQuote;
use App\Models\EmbeddedTransaction;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Services\Logger\LoggerService;
use App\Services\RenewalsUploadService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Sleep;

class CarCQFRenewalExecutionService
{
    private int $totalQuotesProcessed = 0;
    private int $errorQuotes = 0;
    private array $failedPolicyNumbers = [];
    private array $validationErrorsList = [];
    private array $epCodes = [];

    public function processCarCQFRenewalLeads($startDate = null): void
    {
        $renewalDaysThreshold = getAppStorageValueByKey(ApplicationStorageEnums::CAR_CQF_RENEWALS_DAYS_THRESHOLD);
        $startDate = $startDate ?: Carbon::now()->addDays((int) $renewalDaysThreshold);

        LoggerService::info(self::class." - Car CQF Renewal Leads processing started with Start Date: {$startDate}");

        $expiryRange = [$startDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];

        $isQuoteExists = CarQuote::whereBetween('policy_expiry_date', $expiryRange)
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
            ->first();

        if (empty($isQuoteExists)) {
            LoggerService::info(self::class." - No quotes found for the given start date: {$startDate}");

            return;
        }

        $renewalsUploadLeads = $this->createRenewalsUploadLeads();

        CarQuote::whereBetween('policy_expiry_date', $expiryRange)
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
            ->with(['plan', 'plan.insuranceProvider', 'carQuoteRequestDetail:id,car_quote_request_id,chassis_number', 'embeddedTransactions'])
            ->chunkById(100, function ($quotes) use ($renewalsUploadLeads, $renewalDaysThreshold) {
                $quoteCount = $quotes->count();
                LoggerService::info(self::class." - Total quotes in current chunk: {$quoteCount}");

                if ($quoteCount > 0) {
                    LoggerService::info(self::class." - processing cqf car renewals quotes in chunk: {$quoteCount}");
                    $this->createCarCQFRenewalLeads($quotes, $renewalsUploadLeads, (int) $renewalDaysThreshold);
                } else {
                    LoggerService::info(self::class.' - No quotes in chunk');
                }
            });

        $this->finalizeProcessing($renewalsUploadLeads);
    }

    public function createRenewalsUploadLeads(): RenewalsUploadLeads
    {
        $uploadLeadData = [
            'renewal_import_code' => app(RenewalsUploadService::class)->generateRandomString(),
            'quote_type' => str_replace('-', '', QuoteTypes::CAR->shortCode()),
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

    public function createCarCQFRenewalLeads($quotes, RenewalsUploadLeads $renewalsUploadLeads, int $renewalDaysThreshold): void
    {
        $validationService = app(CarCQFValidationService::class);
        $quoteStorageService = app(CarCQFQuoteStorageService::class);
        $quoteMappingService = app(CarCQFQuoteMappingService::class);

        foreach ($quotes as $quote) {
            LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::CAR_CQF_RENEWALS);

            try {
                $this->totalQuotesProcessed++;
                $validationResult = $validationService->validateQuote($quote);

                if (! $validationResult['success']) {
                    $this->markQuoteAsCompleted($quote, $renewalsUploadLeads, false, $validationResult['errors']);

                    continue;
                }

                // Check if the quote is a duplicate
                if ($validationService->isDuplicateQuote($quote)) {
                    LoggerService::info(self::class.' - Duplicate quote detected. Skipping processing');
                    $validationErrors = ['policy_number' => "Duplicate quote detected for policy number: $quote->policy_number"];
                    $this->validationErrorsList[] = [
                        'policy_number' => $quote->policy_number,
                        'message' => "Duplicate quote detected for policy number: $quote->policy_number",
                    ];
                    $this->markQuoteAsCompleted($quote, $renewalsUploadLeads, false, $validationErrors);

                    continue;
                }

                // Check if the quote is an Insly renewal and if the renewal criteria is met
                if ($quote->source == LeadSourceEnum::INSLY) {
                    $isInslyRenewal = $validationService->checkInslyRenewal($quote);
                    if (! $isInslyRenewal) {
                        LoggerService::info(self::class.' - Insly renewal criteria not met for policy number', [
                            'policy_number' => $quote->policy_number,
                        ]);
                        $validationErrors = ['policy_number' => "Insly renewal criteria not met for policy number: $quote->policy_number"];
                        $this->validationErrorsList[] = [
                            'policy_number' => $quote->policy_number,
                            'message' => "Insly renewal criteria not met for policy number: $quote->policy_number",
                        ];
                        $this->markQuoteAsCompleted($quote, $renewalsUploadLeads, false, $validationErrors);

                        continue;
                    }
                }

                Sleep::for(3)->seconds();
                LoggerService::info(self::class.' - Processing quote');

                $newQuote = $quoteStorageService->storeCarCQFRenewalQuote(
                    $quote,
                    $renewalsUploadLeads,
                    $renewalDaysThreshold,
                    $this->epCodes
                );

                if ($newQuote) {
                    $this->markQuoteAsCompleted($quote, $renewalsUploadLeads, true);
                }

            } catch (\Exception $e) {
                LoggerService::error('Error processing quote', exception: $e);
                $this->markQuoteAsCompleted($quote, $renewalsUploadLeads, false);
            }
        }
    }

    public function markQuoteAsCompleted(CarQuote $quote, RenewalsUploadLeads $renewalsUploadLeads, bool $status, array $validationErrors = []): void
    {
        if ($status) {
            RenewalsUploadLeads::where('id', $renewalsUploadLeads->id)->update(['good' => DB::raw('good+1')]);
            $renewalQuoteProcess = $this->createRenewalQuoteProcess($quote, $renewalsUploadLeads);
            $renewalQuoteProcess->status = RenewalProcessStatuses::PROCESSED;
            $renewalQuoteProcess->save();
            LoggerService::info(self::class.' - Renewal Quote Process created for quote');
        } else {
            RenewalsUploadLeads::where('id', $renewalsUploadLeads->id)->update(['cannot_upload' => DB::raw('cannot_upload+1')]);
            $renewalQuoteProcess = $this->createRenewalQuoteProcess($quote, $renewalsUploadLeads);
            $renewalQuoteProcess->status = RenewalProcessStatuses::BAD_DATA;
            $renewalQuoteProcess->validation_errors = $validationErrors;
            $this->validationErrorsList[] = $validationErrors;

            $quoteMappingService = app(CarCQFQuoteMappingService::class);
            $renewalQuoteProcess->data = $quoteMappingService->mapFailedQuoteData($quote);
            $renewalQuoteProcess->save();

            $this->errorQuotes++;
            $this->failedPolicyNumbers[] = $quote->policy_number;
            LoggerService::info(self::class.' - Renewal Quote Process not created for quote');
        }
    }

    public function createRenewalQuoteProcess(CarQuote $quote, RenewalsUploadLeads $renewalsUploadLeads): RenewalQuoteProcess
    {
        return RenewalQuoteProcess::create([
            'renewals_upload_lead_id' => $renewalsUploadLeads->id,
            'quote_type' => str_replace('-', '', QuoteTypes::CAR->shortCode()),
            'policy_number' => $quote->policy_number ?? null,
            'data' => $quote ?? [],
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

        if (count($this->epCodes) > 0) {
            // Chunk-wise update of epCodes for performance and memory efficiency
            collect($this->epCodes)
                ->chunk(500)
                ->each(function ($epCodeChunk) {
                    EmbeddedTransaction::whereIn('code', $epCodeChunk->toArray())
                        ->update(['is_selected' => 1]);
                    LoggerService::info(self::class.' - Updated is_selected for EP codes chunk. Count: '.count($epCodeChunk));
                });
        }

        if ($this->errorQuotes > 0) {
            SendFailedCarRenewalsJob::dispatch($this->failedPolicyNumbers, $renewalsUploadLeads->id);
            LoggerService::info(self::class." - Car CQF Renewal Leads processing completed with errors: {$this->errorQuotes}");
        } else {
            LoggerService::info(self::class.' - Car CQF Renewal Leads processing completed');
        }
    }
}
