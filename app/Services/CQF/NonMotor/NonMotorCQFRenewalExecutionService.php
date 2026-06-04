<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\QuoteTypes;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Enums\VehicleTypeEnum;
use App\Models\CarQuote;
use App\Models\EmbeddedTransaction;
use App\Models\PersonalQuote;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Services\CQF\Contracts\CQFQuoteMappingInterface;
use App\Services\CQF\NonMotor\Pipes\DuplicateCheckPipe;
use App\Services\CQF\NonMotor\Pipes\ForeignKeyValidationPipe;
use App\Services\CQF\NonMotor\Pipes\InslyCheckPipe;
use App\Services\CQF\NonMotor\Pipes\LOBValidationPipe;
use App\Services\CQF\NonMotor\Pipes\StoragePipe;
use App\Services\Logger\LoggerService;
use App\Services\RenewalsUploadService;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class NonMotorCQFRenewalExecutionService
{
    // Filesystem-safe datetime format — no matching key in config/constants.php, intentionally kept local.
    private const FILE_DATETIME_FORMAT = 'Y-m-d_H-i-s';

    public function __construct(
        protected NonMotorCQFRegistry $registry,
        protected LOBValidationPipe $lobValidationPipe,
        protected DuplicateCheckPipe $duplicateCheckPipe,
        protected InslyCheckPipe $inslyCheckPipe,
        protected ForeignKeyValidationPipe $foreignKeyValidationPipe,
        protected StoragePipe $storagePipe,
        protected RenewalsUploadService $renewalsUploadService,
        protected Pipeline $pipeline,
    ) {}

    /**
     * Count of eligible quotes for this LOB (personal + car for Bike). Used to set total_records on lead creation.
     */
    public function getEligibleQuoteCountForLOB(QuoteTypes $quoteType, Carbon $startDate): int
    {
        if (! $this->registry->hasLOB($quoteType)) {
            return 0;
        }
        $filter = NonMotorCQFRegistry::eligibilityFilter();
        $quoteTypeId = (int) $quoteType->id();

        $expiryRange = [$startDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];

        $baseQuery = PersonalQuote::query()
            ->whereBetween('policy_expiry_date', $expiryRange)
            ->where('quote_type_id', $quoteTypeId)
            ->whereNotIn('quote_status_id', $filter['excluded_quote_status']);

        $count = NonMotorCQFRegistry::applyPaymentStatusFilter($baseQuery, $quoteType, $filter)->count();

        if ($quoteType === QuoteTypes::BIKE) {
            $count += CarQuote::whereBetween('policy_expiry_date', $expiryRange)
                ->whereIn('vehicle_type_id', VehicleTypeEnum::ids())
                ->whereNotIn('quote_status_id', $filter['excluded_quote_status'])
                ->whereIn('payment_status_id', $filter['payment_status'])
                ->count();
        }

        return $count;
    }

    /**
     * Process a single quote (job path). Failures are recorded on RenewalQuoteProcess (status BAD_DATA).
     */
    public function processQuoteForJob(
        int $quoteId,
        string $source,
        QuoteTypes $quoteType,
        int $renewalsUploadLeadsId,
        int $renewalDaysThreshold
    ): void {
        $renewalsUploadLeads = RenewalsUploadLeads::findOrFail($renewalsUploadLeadsId);
        $eagerLoad = $this->getEagerLoadRelationsForLOB($quoteType, $source);

        $quote = $source === QuoteTypes::PERSONAL->value
            ? PersonalQuote::with($eagerLoad)->findOrFail($quoteId)
            : CarQuote::with($eagerLoad)->findOrFail($quoteId);

        $onFailure = fn () => null;
        $this->runPipelineForQuote($quote, $renewalsUploadLeads, $renewalDaysThreshold, $quoteType, $onFailure);
    }

    /**
     * @param  callable(string): void  $onFailure
     */
    protected function runPipelineForQuote(
        PersonalQuote|CarQuote $quote,
        RenewalsUploadLeads $renewalsUploadLeads,
        int $renewalDaysThreshold,
        QuoteTypes $quoteType,
        callable $onFailure
    ): void {
        $validator = $this->registry->getValidator($quoteType);
        $mapper = $this->registry->getMapper($quoteType);
        $storage = $this->registry->getStorage($quoteType);

        $pipes = [
            $this->lobValidationPipe,
            $this->duplicateCheckPipe,
            $this->inslyCheckPipe,
            $this->foreignKeyValidationPipe,
            $this->storagePipe,
        ];

        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::NON_MOTOR_CQF_RENEWALS);
        LoggerService::info('Processing non-motor CQF renewal quote', [
            'quoteId' => $quote->id,
            'quoteType' => $quoteType->value,
            'renewalsUploadLeadsId' => $renewalsUploadLeads->id,
            'renewalDaysThreshold' => $renewalDaysThreshold,
        ]);

        try {
            $context = new CQFRenewalContext(
                quote: $quote,
                renewalsUploadLeads: $renewalsUploadLeads,
                quoteType: $quoteType,
                renewalDaysThreshold: $renewalDaysThreshold,
                validator: $validator,
                mapper: $mapper,
                storage: $storage
            );

            $context = $this->pipeline
                ->send($context)
                ->through($pipes)
                ->thenReturn();

            if ($context->hasErrors()) {
                $this->markQuoteAsFailed($quote, $renewalsUploadLeads, $context->validationErrors, $mapper, $onFailure);

                return;
            }

            LoggerService::info(self::class.' - Pipeline result', [
                'newQuote' => $context->newQuote?->id,
                'epCodes' => $context->epCodes,
                'epCodesCount' => count($context->epCodes),
            ]);

            if ($context->newQuote !== null) {
                $this->updateEmbeddedTransactionIsSelectedForEpCodes($context->epCodes);
                $this->markQuoteAsCompleted($quote, $renewalsUploadLeads);
            } else {
                $this->markQuoteAsFailed($quote, $renewalsUploadLeads, ['storage' => 'Failed to create renewal quote'], $mapper, $onFailure);
            }
        } catch (\Throwable $e) {
            LoggerService::error('Error processing non-motor CQF renewal quote', exception: $e);
            $this->markQuoteAsFailed($quote, $renewalsUploadLeads, ['exception' => $e->getMessage()], $mapper ?? null, $onFailure);
        }
    }

    public function createRenewalUploadLeadsForLOB(QuoteTypes $quoteType, ?int $totalRecords = null): RenewalsUploadLeads
    {
        $shortCode = str_replace('-', '', $quoteType->shortCode());

        return $this->createRenewalsUploadLeads($shortCode, $totalRecords);
    }

    protected function createRenewalsUploadLeads(string $quoteTypeShortCode, ?int $totalRecords = null): RenewalsUploadLeads
    {
        $uploadLeadData = [
            'renewal_import_code' => $this->renewalsUploadService->generateRandomString(),
            'quote_type' => $quoteTypeShortCode,
            'file_name' => 'cqf_renewal_leads_'.$quoteTypeShortCode.'_'.uniqid().'_'.now()->format(self::FILE_DATETIME_FORMAT).'.xlsx',
            'file_path' => null,
            'status' => ProcessStatusCode::UPLOADED,
            'good' => 0,
            'cannot_upload' => 0,
            'total_records' => $totalRecords ?? 0,
            'is_sic' => 0,
            'created_by_id' => null,
            'renewal_import_type' => RenewalsUploadType::CREATE_LEADS,
        ];

        return RenewalsUploadLeads::create($uploadLeadData);
    }

    protected function markQuoteAsCompleted(PersonalQuote|CarQuote $quote, RenewalsUploadLeads $renewalsUploadLeads): void
    {
        RenewalsUploadLeads::where('id', $renewalsUploadLeads->id)->update(['good' => DB::raw('good+1')]);
        RenewalQuoteProcess::create([
            'renewals_upload_lead_id' => $renewalsUploadLeads->id,
            'quote_type' => $renewalsUploadLeads->quote_type,
            'quote_id' => $quote->id,
            'policy_number' => $quote->policy_number ?? null,
            'data' => $quote->toArray(),
            'batch' => null,
            'status' => RenewalProcessStatuses::PROCESSED,
            'type' => RenewalsUploadType::CREATE_LEADS,
        ]);
        LoggerService::info(self::class.' - Renewal quote process created for quote');
    }

    /**
     * @param  array<string, string>  $validationErrors
     * @param  callable(string): void|null  $recordFailure  When set (job path), called with policy number instead of updating instance state
     */
    protected function markQuoteAsFailed(PersonalQuote|CarQuote $quote, RenewalsUploadLeads $renewalsUploadLeads, array $validationErrors, ?CQFQuoteMappingInterface $mapper, ?callable $recordFailure = null): void
    {
        RenewalsUploadLeads::where('id', $renewalsUploadLeads->id)->update(['cannot_upload' => DB::raw('cannot_upload+1')]);
        RenewalQuoteProcess::create([
            'renewals_upload_lead_id' => $renewalsUploadLeads->id,
            'quote_type' => $renewalsUploadLeads->quote_type,
            'quote_id' => $quote->id,
            'policy_number' => $quote->policy_number ?? null,
            'data' => $mapper !== null ? $mapper->mapFailedQuoteData($quote) : $quote->toArray(),
            'batch' => null,
            'status' => RenewalProcessStatuses::BAD_DATA,
            'validation_errors' => $validationErrors,
            'type' => RenewalsUploadType::CREATE_LEADS,
        ]);

        if ($recordFailure !== null) {
            $recordFailure($quote->policy_number ?? 'unknown');
        }
        LoggerService::info(self::class.' - Renewal quote process not created for quote (validation failed)');
    }

    /**
     * Update EmbeddedTransaction::is_selected for collected ep codes during this quote's renewal creation. Same process as Car (MDX); for Bike storage we collect RDX and update immediately.
     *
     * @param  array<int, string>  $epCodes
     */
    protected function updateEmbeddedTransactionIsSelectedForEpCodes(array $epCodes): void
    {
        LoggerService::info(self::class.' - updateEmbeddedTransactionIsSelectedForEpCodes called', ['epCodes' => $epCodes]);

        if (empty($epCodes)) {
            LoggerService::info(self::class.' - epCodes is empty, skipping EmbeddedTransaction update');

            return;
        }

        EmbeddedTransaction::whereIn('code', $epCodes)->update(['is_selected' => 1]);
        LoggerService::info(self::class.' - Updated is_selected for embedded transaction EP codes', ['count' => count($epCodes)]);
    }

    /**
     * @return array<int, string>
     */
    protected function getEagerLoadRelationsForLOB(QuoteTypes $quoteType, string $source = ''): array
    {
        if ($source === QuoteTypes::CAR->value) {
            return ['insuranceProvider', 'advisor', 'payments', 'carMake', 'carModel', 'embeddedTransactions'];
        }

        return match ($quoteType) {
            QuoteTypes::BIKE => ['bikeQuote', 'bikeQuote.bikeQuoteRequestDetail', 'embeddedTransactions', 'insuranceProvider', 'currentlyInsuredWith', 'advisor', 'payments'],
            QuoteTypes::HOME => ['homeQuote', 'homeQuote.homeQuoteRequestDetail', 'insuranceProvider', 'currentlyInsuredWith', 'advisor', 'payments'],
            QuoteTypes::PET => ['petQuote', 'petQuote.petQuoteRequestDetail', 'insuranceProvider', 'currentlyInsuredWith', 'advisor', 'payments'],
            QuoteTypes::CYCLE => ['cycleQuote', 'insuranceProvider', 'currentlyInsuredWith', 'advisor', 'payments'],
            QuoteTypes::YACHT => ['yachtQuote', 'yachtQuote.yachtQuoteRequestDetail', 'insuranceProvider', 'currentlyInsuredWith', 'advisor', 'payments'],
            QuoteTypes::BUSINESS => ['businessQuote', 'businessQuote.businessQuoteRequestDetail', 'businessQuote.customerMembers', 'businessQuote.quoteRequestEntityMapping', 'businessQuote.payments', 'insuranceProvider', 'currentlyInsuredWith', 'advisor', 'payments'],
            default => ['insuranceProvider', 'currentlyInsuredWith', 'advisor', 'payments'],
        };
    }
}
