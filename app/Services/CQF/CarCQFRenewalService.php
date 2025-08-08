<?php

namespace App\Services\CQF;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CarRegistrationType;
use App\Enums\CustomerTypeEnum;
use App\Enums\EmbeddedProductEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Jobs\SendFailedCarRenewalsJob;
use App\Models\CarQuote;
use App\Models\EmbeddedTransaction;
use App\Models\Entity;
use App\Models\QuoteRequestEntityMapping;
use App\Models\RenewalBatch;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\LookupRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Services\CapiRequestService;
use App\Services\CRUDService;
use App\Services\InsuranceProviderService;
use App\Services\Logger\LoggerService;
use App\Services\RenewalsUploadService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Sleep;

class CarCQFRenewalService
{
    private $totalQuotesProcessed = 0;
    private $errorQuotes = 0;
    private $failedQuotes = [];
    private $epCodes = [];
    public function processCarCQFRenewalLeads()
    {
        $renewalDaysThreshold = getAppStorageValueByKey(ApplicationStorageEnums::CAR_CQF_RENEWALS_DAYS_THRESHOLD);

        $startDate = Carbon::now()->addDays((int) $renewalDaysThreshold);
        LoggerService::info(self::class." - Car CQF Renewal Leads processing started with Start Date: {$startDate}");
        $isQuoteExists = CarQuote::whereDate('policy_expiry_date', $startDate)
            ->whereNotIn('quote_status_id',
                [QuoteStatusEnum::PolicyCancelled,
                    QuoteStatusEnum::PolicyCancelledReissued,
                    QuoteStatusEnum::CancellationPending]
            )->whereIn('payment_status_id', [
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
        CarQuote::whereDate('policy_expiry_date', $startDate)
            ->whereNotIn('quote_status_id',
                [QuoteStatusEnum::PolicyCancelled,
                    QuoteStatusEnum::PolicyCancelledReissued,
                    QuoteStatusEnum::CancellationPending]
            )
            ->whereIn('payment_status_id', [
                PaymentStatusEnum::PAID,
                PaymentStatusEnum::PARTIALLY_PAID,
                PaymentStatusEnum::CAPTURED,
                PaymentStatusEnum::PARTIAL_CAPTURED,
            ])
            ->with(['plan', 'plan.insuranceProvider'])
            ->chunkById(100, function ($quotes) use ($renewalsUploadLeads, $renewalDaysThreshold) {
                $quoteCount = $quotes->count();
                LoggerService::info(self::class." - Total quotes in current chunk: {$quoteCount}");
                if ($quoteCount > 0) {
                    LoggerService::info(self::class." - processing cqf car renewals quotes in chunk: {$quoteCount}");
                    $this->createCarCQFRenewalLeads($quotes, $renewalsUploadLeads, $renewalDaysThreshold);
                } else {
                    LoggerService::info(self::class.' - No quotes in chunk');
                }
            });

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
            SendFailedCarRenewalsJob::dispatch($this->failedQuotes);
            LoggerService::info(self::class." - Car CQF Renewal Leads processing completed with errors: {$this->errorQuotes}");
        } else {
            LoggerService::info(self::class.' - Car CQF Renewal Leads processing completed');
        }
    }

    public function createRenewalsUploadLeads()
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

    public function createCarCQFRenewalLeads($quotes, $renewalsUploadLeads, $renewalDaysThreshold)
    {
        foreach ($quotes as $quote) {
            $validationErrors = [];
            LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::CAR_CQF_RENEWALS);

            try {
                $this->totalQuotesProcessed++;
                $validationErrors = $this->validateQuote($quote);
                if ($validationErrors['success'] == false) {
                    $this->markQuoteAsCompleted($quote, $renewalsUploadLeads, false, $validationErrors['errors']);

                    continue;
                }

                // Check if the quote is a duplicate
                if ($this->isDuplicateQuote($quote)) {
                    LoggerService::info(self::class.' - Duplicate quote detected. Skipping processing');
                    $validationErrors = ['policy_number' => "Duplicate quote detected for policy number: $quote->policy_number"];

                    $this->markQuoteAsCompleted($quote, $renewalsUploadLeads, false, $validationErrors);

                    continue; // Skip processing this quote
                }

                // Check if the quote is an Insly renewal and if the renewal criteria is met
                if ($quote->source == LeadSourceEnum::INSLY) {
                    $isInslyRenewal = $this->checkInslyRenewal($quote);
                    if (! $isInslyRenewal) {
                        LoggerService::info(self::class.' - Insly renewal criteria not met for policy number', ['policy_number' => $quote->policy_number]);
                        $validationErrors = ['policy_number' => "Insly renewal criteria not met for policy number: $quote->policy_number"];
                        $this->markQuoteAsCompleted($quote, $renewalsUploadLeads, false, $validationErrors);

                        continue;
                    }
                }

                Sleep::for(3)->seconds();
                LoggerService::info(self::class.' - Processing quote');
                $this->storeCarCQFRenewalQuote($quote, $renewalsUploadLeads, $renewalDaysThreshold);

            } catch (\Exception $e) {
                // Log the exception or handle it as needed
                LoggerService::error('Error processing quote', exception: $e);
                $this->markQuoteAsCompleted($quote, $renewalsUploadLeads, false);
            }

        }
    }
    public function checkInslyRenewal(CarQuote $quote): ?bool
    {

        // Check if the quote has at least one status of policy issued
        $hasPolicyIssuedStatus = app(CRUDService::class)->hasAtleastOneStatusPolicyIssued($quote);

        if ($hasPolicyIssuedStatus) {

            // Retrieve send update options and logs
            $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($quote->uuid);

            // If the lead source is 'Insly', check for send update type 'Endorsement financial' and subtype 'Policy period extension'
            $endorsementFinancial = false;
            $policyPeriodExtension = false;
            $isUpdateBooked = false;

            if ($quote->source === LeadSourceEnum::INSLY && ! empty($sendUpdateLogs)) {
                foreach ($sendUpdateLogs as $log) {

                    if ($log->isEndorsementFinancial()) {
                        $endorsementFinancial = true;
                        LoggerService::info(self::class.' - Endorsement financial found  ', ['policy_number' => $quote->policy_number]);
                    }

                    if ($log->isPolicyPeriodExtension()) {
                        LoggerService::info(self::class.' - Policy period extension found ', ['policy_number' => $quote->policy_number]);
                        $policyPeriodExtension = true;

                    }
                    if ($log->isUpdateBooked()) {
                        LoggerService::info(self::class.' - Update booked found ', ['policy_number' => $quote->policy_number]);
                        $isUpdateBooked = true;
                    }
                }
            }

        }

        return $endorsementFinancial && $policyPeriodExtension && $isUpdateBooked;
    }
    public function validateQuote($quote)
    {
        // Validate required fields for the quote and return error messages if missing

        $validator = Validator::make($quote->toArray(),
            [
                'policy_number' => ['required'],
                'policy_expiry_date' => ['required', 'date'],
                'first_name' => ['required'],
                'email' => ['required', 'email'],
                'mobile_no' => ['required'],
                'car_make_id' => ['required'],
                'car_model_id' => ['required'],
                'registration_type' => ['required', 'in:'.CarRegistrationType::PERSONAL.','.CarRegistrationType::COMPANY],
            ], $this->getValidationMessages());

        $errors = [];

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            foreach ($errors as $field => $message) {
                $errors[$field] = $message[0];
            }
        }

        if (! empty($errors)) {
            LoggerService::error(self::class.' - Quote validation failed', ['errors' => $errors, 'quote_uuid' => $quote->uuid ?? null]);

            return [
                'success' => false,
                'errors' => $errors,
            ];
        }

        return [
            'success' => true,
            'errors' => [],
        ];
    }
    public function isDuplicateQuote($quote)
    {

        return CarQuote::where('previous_quote_id', $quote->id)
            ->where('previous_quote_policy_number', $quote->policy_number)
            ->where('previous_policy_expiry_date', $quote->policy_expiry_date)
            ->where('source', '=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->exists();
    }

    public function markQuoteAsCompleted($quote, $renewalsUploadLeads, $status, $validationErrors = [])
    {

        if ($status) {

            RenewalsUploadLeads::where('id', $renewalsUploadLeads->id)->update(['good' => DB::raw('good+1')]);
            $renewalQuoteProcess = $this->createRenewalQuoteProcess($quote, renewalsUploadLeads: $renewalsUploadLeads);
            $renewalQuoteProcess->status = RenewalProcessStatuses::PROCESSED;
            $renewalQuoteProcess->save();
            LoggerService::info(self::class.' - Renewal Quote Process created for quote');
        } else {
            RenewalsUploadLeads::where('id', $renewalsUploadLeads->id)->update(['cannot_upload' => DB::raw('cannot_upload+1')]);
            $renewalQuoteProcess = $this->createRenewalQuoteProcess($quote, $renewalsUploadLeads);
            $renewalQuoteProcess->status = RenewalProcessStatuses::BAD_DATA;
            $renewalQuoteProcess->validation_errors = $validationErrors;
            $renewalQuoteProcess->data = $this->mapFailedQuoteData($quote);
            $renewalQuoteProcess->save();
            $this->errorQuotes++;
            $this->failedQuotes[] = $quote->policy_number;
            LoggerService::info(self::class.' - Renewal Quote Process not created for quote');
        }

    }
    public function mapFailedQuoteData($quote)
    {
        $policyExpiryDate = Carbon::parse($quote->policy_expiry_date);

        // Calculate the policy expiry date based on the start date + 365 days
        $policyStartDate = $policyExpiryDate->copy()->addDays(1);
        $newPolicyExpiryDate = $policyStartDate->copy()->addDays(120);

        LoggerService::info(self::class.' - Policy Details', [
            'policyExpiryDate' => $policyExpiryDate,
            'policyStartDate' => $policyStartDate,
            'newPolicyExpiryDate' => $newPolicyExpiryDate,
        ]);

        // Get insurance provider safely to avoid null pointer exception
        $insuranceProvider = app(InsuranceProviderService::class)->getProviderByCode($quote->currently_insured_with);

        return [
            'customer_name' => $quote->first_name.' '.$quote->last_name ?? null,
            'email' => $quote->email ?? null,
            'mobile_no' => $quote->mobile_no,
            'quote_type' => str_replace('-', '', QuoteTypes::CAR->shortCode()),
            'insurer' => $insuranceProvider?->text ?? null,
            'product' => $quote->product ?? null,
            'product_type' => $quote->car_type_insurance_id()->text ?? null,
            'advisor' => $quote->advisor()->email ?? null,
            'policy_number' => $quote->policy_number,
            'start_date' => $quote->policy_start_date,
            'end_date' => $quote->policy_expiry_date,
            'batch' => null,
            'make' => $quote->carMake()->text ?? null,
            'model' => $quote->carModel()->text ?? null,
            'year' => $quote->year_of_manufacture ?? null,
            'previous_advisor' => $quote->previousAdvisor()->email ?? null,
            'premium' => $quote->premium ?? null,
            'source' => $quote->source ?? null,
            'notes' => $quote->additional_notes ?? null,
            'plan_name' => $quote->plan()->name ?? null,
        ];
    }

    public function createRenewalQuoteProcess($quote, $renewalsUploadLeads)
    {

        return RenewalQuoteProcess::create(attributes: [
            'renewals_upload_lead_id' => $renewalsUploadLeads->id,
            'quote_type' => str_replace('-', '', QuoteTypes::CAR->shortCode()),
            'policy_number' => $quote->policy_number ?? null,
            'data' => $quote ?? [],
            'batch' => null,
            'status' => RenewalProcessStatuses::NEW,
            'type' => RenewalsUploadType::CREATE_LEADS,
        ]);
    }

    public function storeCarCQFRenewalQuote($quote, $renewalsUploadLeads, $renewalDaysThreshold)
    {
        LoggerService::info(self::class.' - Storing car cqf renewal quote');
        $policyExpiryDate = Carbon::parse($quote->policy_expiry_date);

        // Calculate the policy expiry date based on the start date + 120 days
        $policyStartDate = $policyExpiryDate->copy()->addDays(1);
        $newPolicyExpiryDate = $policyStartDate->copy()->addDays((int) $renewalDaysThreshold);

        LoggerService::info(self::class.' - Policy Details', [
            'policyExpiryDate' => $policyExpiryDate,
            'policyStartDate' => $policyStartDate,
            'newPolicyExpiryDate' => $newPolicyExpiryDate,
        ]);

        $quoteData = $this->mapCarCQFRenewalQuote($quote, $renewalsUploadLeads);
        $newQuote = CarQuote::create($quoteData);

        if ($newQuote) {
            $this->markQuoteAsCompleted($quote, $renewalsUploadLeads, true);
            $this->getCustomerEntity($newQuote, $quote);
            app(EmbeddedProductRepository::class)->saveEmbeddedTransaction($newQuote, QuoteTypeId::Car);
            $this->epCodes[] = EmbeddedProductEnum::MDX.'-'.$newQuote->code;

            LoggerService::info(sprintf('%s - Car CQF Renewal Quote created successfully', self::class), [
                'previous_quote_uuid' => $quote->uuid,
                'new_quote_uuid' => $newQuote->uuid,
                'previous_quote_id' => $quote->id,
                'new_quote_id' => $newQuote->id,
            ]);
        }

        return $newQuote;
    }
    public function getRenewalBatch($newPolicyExpiryDate)
    {
        return RenewalBatch::where('start_date', '<=', $newPolicyExpiryDate)
            ->where('end_date', '>=', $newPolicyExpiryDate)
            ->where('quote_type_id', QuoteTypeId::Car)
            ->first();
    }
    public function generateUUID()
    {

        if (checkPersonalQuotes(QuoteTypes::CAR)) {
            $response = app(CapiRequestService::class)->getPersonalQuoteUUID(QuoteTypes::CAR->id());
        } else {
            $response = app(CapiRequestService::class)->getUUID(QuoteTypes::CAR->id());
        }

        if ($response) {
            return $response->uuid;
        }
    }
    public function mapCarCQFRenewalQuote($quote, $renewalsUploadLeads)
    {

        $quoteUuid = $this->generateUUID();
        $quoteData = [
            'customer_id' => $quote->customer_id,
            'first_name' => $quote->first_name,
            'last_name' => $quote->last_name,
            'email' => $quote->email,
            'mobile_no' => $quote->mobile_no,
            'uuid' => $quoteUuid,
            'code' => sprintf('%s%s', strtoupper(QuoteTypes::CAR->shortCode()), $quoteUuid),
            'source' => LeadSourceEnum::RENEWAL_UPLOAD,
            'dob' => $quote->dob,
            'advisor_id' => null,
            'assignment_type' => null,
            'renewal_batch' => null,
            'registration_type' => $quote->registration_type ?? CarRegistrationType::PERSONAL,
            'renewal_batch_id' => null,
            'quote_status_id' => QuoteStatusEnum::NewLead,
            'renewal_import_code' => $renewalsUploadLeads->renewal_import_code,
            'previous_quote_policy_number' => $quote->policy_number,
            'previous_policy_start_date' => $quote->policy_start_date,
            'previous_policy_expiry_date' => $quote->policy_expiry_date,
            'previous_quote_policy_premium' => $quote->premium,
            'previous_advisor_id' => $quote->advisor_id,
            'previous_quote_id' => $quote->id,
            'car_make_id' => $quote->car_make_id,
            'car_model_id' => $quote->car_model_id,
            'vehicle_type_id' => $quote->vehicle_type_id,
            'cylinder' => $quote->cylinder,
            'year_of_manufacture' => $quote->year_of_manufacture,
            'currently_insured_with' => $quote?->plan?->insuranceProvider?->text ?? null,
            'vehicle_category' => $quote->vehicle_category,
            'car_type_insurance_id' => $quote->car_type_insurance_id,
            'seat_capacity' => $quote->seat_capacity,
            'tier_id' => $quote->tier_id,
            'vehicle_use' => $quote->vehicle_use,
            'emirate_of_registration_id' => $quote->emirate_of_registration_id,
            'car_value' => $quote->car_value,
        ];

        $lookup = LookupRepository::where('key', LookupsEnum::TRANSACTION_TYPES)->where('code', LookupsEnum::EXT_CUSTOMER_RENWAL)->first();
        if ($lookup) {
            $quoteData['transaction_type_id'] = $lookup->id;
        }

        return $quoteData;
    }

    public function getCustomerEntity($newquote, $oldquote)
    {
        $entityMapping = QuoteRequestEntityMapping::with('entity')
            ->where('quote_type_id', QuoteTypeId::Car)
            ->where('quote_request_id', $newquote->id)
            ->first();

        if (isset($oldquote->registration_type) && $oldquote->registration_type == CarRegistrationType::COMPANY) {

            if (! $entityMapping) {

                $entity = Entity::create([
                    'company_name' => $oldquote->first_name.' '.$oldquote->last_name ?? null,
                ]);
                $entityId = $entity->id;
                $entity->update(['code' => CustomerTypeEnum::EntityShort.'-'.$entityId]);

                QuoteRequestEntityMapping::updateOrCreate([
                    'quote_type_id' => QuoteTypeId::Car,
                    'quote_request_id' => $newquote->id,
                ], ['entity_id' => $entityId]);
            }

        } else {
            if ($entityMapping) {
                $entityMappingCount = $entityMapping->entity->quoteRequestEntityMapping->count();
                $entityRecord = $entityMapping->entity;
                $entityMapping->delete();
                if ($entityMappingCount == 1) {
                    $entityRecord->delete();
                }
            }
        }
    }

    public function getValidationMessages()
    {
        return [
            'policy_number.required' => 'Policy number is required.',
            'source.required' => 'Source is required.',
            'policy_expiry_date.required' => 'Policy expiry date is required.',
            'policy_expiry_date.date' => 'Policy expiry date must be a valid date.',
            'first_name.required' => 'Customer name is required.',
            'email.required' => 'Customer email is required.',
            'email.email' => 'Customer email must be a valid email address.',
            'mobile_no.required' => 'Customer mobile is required.',
            'car_make_id.required' => 'Car make is required.',
            'car_model_id.required' => 'Car model is required.',
            'registration_type.required' => 'Registration type is required.',
        ];
    }

}
