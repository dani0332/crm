<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CarPlanAddonsCode;
use App\Enums\CarPlanType;
use App\Enums\carTypeInsuranceCode;
use App\Enums\FetchPlansStatuses;
use App\Enums\GenericRequestEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\QuoteSegmentEnum;
use App\Enums\quoteStatusCode;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\QuoteTypeShortCode;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Enums\ThirdPartyTagEnum;
use App\Enums\TiersEnum;
use App\Enums\TravelQuoteEnum;
use App\Exports\RenewalQuotesExport;
use App\Facades\Ken;
use App\Imports\TravelUploadAndCreateImport;
use App\Imports\UploadAndCreateImport;
use App\Imports\UploadAndUpdateImport;
use App\Jobs\OCB\SendCarOCBIntroEmailJob;
use App\Jobs\Renewals\CreateRenewalQuotesJob;
use App\Jobs\Renewals\CreateRenewalsWorkflowJob;
use App\Jobs\Renewals\CreateTravelRenewalQuotesJob;
use App\Jobs\Renewals\FetchPlansForRenewalsQuoteJob;
use App\Jobs\Renewals\ProcessRenewalsUploadCreate;
use App\Jobs\Renewals\ProcessRenewalsUploadUpdate;
use App\Jobs\Renewals\ProcessTravelRenewalsUploadCreate;
use App\Jobs\Renewals\RenewalBatchEmailJob;
use App\Jobs\Renewals\UpdateRenewalQuotesJob;
use App\Models\ApplicationStorage;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarModelDetail;
use App\Models\CarPlan;
use App\Models\CarQuote;
use App\Models\CarQuoteValuation;
use App\Models\ClaimHistory;
use App\Models\CurrentlyLocatedIn;
use App\Models\Customer;
use App\Models\Emirate;
use App\Models\HealthPlan;
use App\Models\InsuranceProvider;
use App\Models\Nationality;
use App\Models\PaymentStatus;
use App\Models\QuoteAdditionalDetail;
use App\Models\QuoteStatus;
use App\Models\QuoteTag;
use App\Models\QuoteType;
use App\Models\RenewalBatch;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsBatchEmails;
use App\Models\RenewalStatusProcess;
use App\Models\RenewalsUploadLeads;
use App\Models\Tier;
use App\Models\TravelQuote;
use App\Models\UAELicenseHeldFor;
use App\Models\User;
use App\Models\VehicleType;
use App\Repositories\BusinessQuoteRepository;
use App\Repositories\LookupRepository;
use App\Services\EmailServices\CarEmailService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\PersonalQuoteSyncTrait;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Sammyjo20\LaravelHaystack\Models\Haystack;

class RenewalsUploadService
{
    use GenericQueriesAllLobs, PersonalQuoteSyncTrait;

    protected $renewalsAddonService;
    protected $capiRequestService;
    protected $insuranceProviderService;
    protected $carQuoteService;
    protected $crudService;
    protected $lookupService;
    protected $sendEmailCustomerService;
    protected $userService;
    protected $healthQuoteService;

    public function __construct(
        RenewalsAddonServices $renewalsAddonService,
        CapiRequestService $capiRequestService,
        InsuranceProviderService $insuranceProviderService,
        CarQuoteService $carQuoteService,
        CRUDService $crudService,
        LookupService $lookupService,
        SendEmailCustomerService $sendEmailCustomerService,
        UserService $userService,
        HealthQuoteService $healthQuoteService
    ) {
        $this->renewalsAddonService = $renewalsAddonService;
        $this->capiRequestService = $capiRequestService;
        $this->insuranceProviderService = $insuranceProviderService;
        $this->carQuoteService = $carQuoteService;
        $this->crudService = $crudService;
        $this->lookupService = $lookupService;
        $this->sendEmailCustomerService = $sendEmailCustomerService;
        $this->userService = $userService;
        $this->healthQuoteService = $healthQuoteService;
    }

    /*
     * @name generateUUID()
     * @returns a 16 character UUIDv4 string
     */
    public function generateUUID($quoteType, $quoteTypeId)
    {
        info('UAT FN: generateUUID QuoteTypeId: '.$quoteTypeId);
        if (checkPersonalQuotes($quoteType)) {
            $response = $this->capiRequestService->getPersonalQuoteUUID($quoteTypeId);
        } else {
            $response = $this->capiRequestService->getUUID($quoteTypeId);
        }

        if ($response) {
            return $response->uuid;
        }
    }

    /**
     * upload renewal file to azure.
     *
     * @return array
     */
    public function uploadRenewalsFile($isTravel = false)
    {
        $path = 'renewals';
        // Getting original file name
        $fileName = request()->file('file_name')->getClientOriginalName();

        // Generating name for file for azure usage
        $azureFileName = get_guid().'_'.$fileName;

        // Uploading file to Azure
        if ($isTravel) {
            $path .= '/travel';
        }
        $azureFilePath = request()->file('file_name')->storeAs($path, $azureFileName, 'azureIM');

        return [
            'file_name' => $fileName,
            'azure_file_path' => $azureFilePath,
        ];
    }

    /**
     * @return RenewalsUploadLeads
     */
    public function createRenewalsLead($uploadedFile, $renewalImportType, $data)
    {
        $uploadLeadData = [
            'renewal_import_code' => $this->generateRandomString(),
            'file_name' => $uploadedFile['file_name'],
            'file_path' => $uploadedFile['azure_file_path'],
            'status' => ProcessStatusCode::UPLOADED,
            'good' => 0,
            'cannot_upload' => 0,
            'is_sic' => array_key_exists('is_sic', $data) && $data['is_sic'] == 'true' ? 1 : 0,
            'created_by_id' => auth()->user()->id,
            'renewal_import_type' => $renewalImportType,
        ];

        if (! empty($data['skip_plans'])) {
            $uploadLeadData['skip_plans'] = $data['skip_plans'];
        }

        return RenewalsUploadLeads::create($uploadLeadData);
    }

    /**
     * renewals upload and create.
     *
     * @return mixed
     */
    public function renewalsUploadCreate($data)
    {
        // upload renewal file to azure
        $uploadedFile = $this->uploadRenewalsFile();

        // create lead record
        $renewalsUploadLead = $this->createRenewalsLead($uploadedFile, RenewalsUploadType::CREATE_LEADS, $data);
        info('UAT FN: renewalsUploadCreate File uploaded and renewals lead created');

        // start import process
        ProcessRenewalsUploadCreate::dispatch($renewalsUploadLead);

        return true;
    }

    /**
     * this will be triggered by job to start import process for upload and create.
     *
     * @return void
     */
    public function processUploadCreate(RenewalsUploadLeads $renewalsUploadLead)
    {
        $logPrefix = 'UAC FN: processUploadCreate RenewalLeadId: '.$renewalsUploadLead->id.' FileName: '.$renewalsUploadLead->file_name;

        try {
            $renewalsUploadLead->update(['status' => ProcessStatusCode::IN_PROGRESS]);

            info($logPrefix.' In Progress Now');

            $renewalsUploadLead = DB::transaction(function () use ($renewalsUploadLead) {
                // start file import
                $renewalsUpload = new UploadAndCreateImport($renewalsUploadLead);
                $renewalsUpload->import($renewalsUploadLead->file_path, 'azureIM');

                // update counts
                $validRows = $renewalsUpload->getValidCount();
                $failedRows = $renewalsUpload->getFailedCount();

                $renewalsUploadLead->update([
                    'cannot_upload' => $failedRows,
                    'good' => 0,
                    'total_records' => ($validRows + $failedRows),
                ]);

                return $renewalsUploadLead;
            });

            info($logPrefix.' excel data stored in DB');

            $validationResult = $this->uploadedLeadsValidation($renewalsUploadLead);
            if ($validationResult) {
                $this->createQuotes($renewalsUploadLead);
            }

            info($logPrefix.' validation and quote creation is completed');

            return true;
        } catch (\Exception $exception) {
            $renewalsUploadLead->update(['status' => ProcessStatusCode::FAILED]);
            Log::error($logPrefix.'Process Failed. Error: '.$exception->getMessage());

            return false;
        }
    }

    /**
     * @return void
     *
     * @throws \Throwable
     */
    public function createQuotes(RenewalsUploadLeads $renewalsUploadLead)
    {
        $logPrefix = 'UAC fn: createQuotes ';
        info($logPrefix.' QuoteCreation started');

        try {
            $jobs = null;

            RenewalQuoteProcess::where([
                'renewals_upload_lead_id' => $renewalsUploadLead->id,
                'status' => RenewalProcessStatuses::VALIDATED,
            ])->chunkById(50, function ($leads) use (&$jobs) {
                foreach ($leads as $lead) {
                    $jobs[] = new CreateRenewalQuotesJob($lead);
                }
            });

            if ($jobs != null && count($jobs)) {
                Haystack::build()
                    ->onQueue('renewals')
                    ->addJobs($jobs)
                    ->then(function () use ($logPrefix, $renewalsUploadLead) {
                        info($logPrefix.' all jobs completed successfully');
                        $renewalsUploadLead->update(['status' => ProcessStatusCode::COMPLETED]);
                    })
                    ->catch(function () use ($logPrefix, $renewalsUploadLead) {
                        info($logPrefix.' one of batch is failed. ');
                        $renewalsUploadLead->update(['status' => ProcessStatusCode::FAILED]);
                    })
                    ->finally(function () use ($logPrefix) {
                        info($logPrefix.' everything done');
                    })
                    ->allowFailures()
                    ->withDelay(2)
                    ->dispatch();
            } else {
                info($logPrefix.' No jobs to create quotes');
                $renewalsUploadLead->update(['status' => ProcessStatusCode::COMPLETED]);
            }
        } catch (\Exception $exception) {
            info('BATCH: one of batch is failed. Exception : '.$exception->getMessage());
            $renewalsUploadLead->update(['status' => ProcessStatusCode::FAILED]);
        }
    }

    public function updateQuotes(RenewalsUploadLeads $renewalsUploadLead)
    {
        $logPrefix = 'UAU fn: updateQuotes ';
        info($logPrefix.' Quote update started');

        try {
            $jobs = null;

            RenewalQuoteProcess::where([
                'renewals_upload_lead_id' => $renewalsUploadLead->id,
                'status' => RenewalProcessStatuses::VALIDATED,
            ])->chunkById(50, function ($leads) use (&$jobs) {
                foreach ($leads as $lead) {
                    $jobs[] = new UpdateRenewalQuotesJob($lead);
                }
            });

            if ($jobs != null && count($jobs)) {
                Haystack::build()
                    ->onQueue('renewals')
                    ->addJobs($jobs)
                    ->then(function () use ($logPrefix, $renewalsUploadLead) {
                        info($logPrefix.' all jobs completed successfully');
                        $renewalsUploadLead->update(['status' => ProcessStatusCode::COMPLETED]);
                    })
                    ->catch(function () use ($logPrefix, $renewalsUploadLead) {
                        // Haystack failed
                        info($logPrefix.' one of batch is failed. ');
                        $renewalsUploadLead->update(['status' => ProcessStatusCode::FAILED]);
                    })
                    ->finally(function () use ($logPrefix) {
                        info($logPrefix.' everything done');
                    })
                    ->allowFailures()
                    ->withDelay(2)
                    ->dispatch();

                info($logPrefix.' jobs dispatched');
            } else {
                info($logPrefix.' no jobs to create quotes');
                $renewalsUploadLead->update(['status' => ProcessStatusCode::COMPLETED]);
            }
        } catch (\Exception $exception) {
            info('BATCH: one of batch is failed. Exception : '.$exception->getMessage());
            $renewalsUploadLead->update(['status' => ProcessStatusCode::FAILED]);
        }
    }

    /**
     * call get plans
     * todo: refine later.
     *
     * @return mixed|string|null
     */
    public function getPlans($id)
    {
        $quotePlans = $this->carQuoteService->getQuotePlans($id, false, true, false, true);

        if (isset($quotePlans->quotes)) {
            return true;
        }

        if (! empty($quotePlans->message)) {
            return $quotePlans->message;
        }

        return $quotePlans;
    }

    /**
     * @return void
     */
    public function fetchRenewalPlans(RenewalStatusProcess $renewalStatusProcess, $batch)
    {
        $logPrefix = 'FetchPlans FN: fetchRenewalPlans Batch: '.$batch;
        info($logPrefix.'  Fetch plans started');

        try {
            $jobs = null;
            $totalSkipped = 0;

            $query = RenewalQuoteProcess::where([
                'status' => RenewalProcessStatuses::PROCESSED,
                'quote_type' => QuoteTypeShortCode::CAR,
                'batch' => $batch,
                'type' => RenewalsUploadType::UPDATE_LEADS,
                'fetch_plans_status' => FetchPlansStatuses::PENDING,
            ])->with(['renewalUploadLead', 'carQuote']);

            $query->chunkById(50, function ($leads) use ($renewalStatusProcess, &$jobs, $logPrefix, &$totalSkipped) {
                foreach ($leads as $lead) {
                    if (! $lead->renewalUploadLead->skip_plans) {
                        $jobs[] = new FetchPlansForRenewalsQuoteJob($lead, $renewalStatusProcess);
                    } else {
                        info($logPrefix.' skipping fetch plans for uuid : '.$lead->carQuote->uuid);
                        $lead->update(['status' => RenewalProcessStatuses::PLANS_FETCHED, 'fetch_plans_status' => FetchPlansStatuses::FETCHED]);
                        $totalSkipped++;
                    }
                }
            });

            if ($totalSkipped > 0) {
                $renewalStatusProcess->update(['total_completed' => $totalSkipped]);
                info($logPrefix.' total leads for skipped plans ('.$totalSkipped.')');
            }

            if ($jobs != null && count($jobs)) {
                info($logPrefix.' '.count($jobs).' found to schedule for fetch plans');

                Haystack::build()
                    ->onQueue('renewals')
                    ->addJobs($jobs)
                    ->then(function () use ($logPrefix, $renewalStatusProcess) {
                        info($logPrefix.' all jobs completed successfully');
                        $renewalStatusProcess->update(['status' => ProcessStatusCode::COMPLETED]);
                    })
                    ->catch(function () use ($logPrefix, $renewalStatusProcess) {
                        // Haystack failed
                        info($logPrefix.' one of batch is failed. ');
                        $renewalStatusProcess->update(['status' => ProcessStatusCode::FAILED]);
                    })
                    ->finally(function () use ($logPrefix) {
                        info($logPrefix.' everything done');
                    })
                    ->allowFailures()
                    ->withDelay(10)
                    ->dispatch();

                info($logPrefix.' all jobs are scheduled');
            } else {
                info($logPrefix.' no leads available for fetch plans, about to mark status as completed');
                $renewalStatusProcess->update(['status' => ProcessStatusCode::COMPLETED]);
                info($logPrefix.' fetch plans is completed');
            }

            return true;
        } catch (\Exception $exception) {
            Log::error($logPrefix.'Fetch plans failed.  Error: '.$exception->getMessage());
            $renewalStatusProcess->update(['status' => ProcessStatusCode::FAILED]);
        }
    }

    /**
     * fetch plans for individual quote.
     *
     * @return false|void
     */
    public function fetchQuotePlans(RenewalQuoteProcess $renewalQuoteProcess, RenewalStatusProcess $renewalStatusProcess)
    {
        $leadData = (object) $renewalQuoteProcess->data;

        $quoteType = $this->getQuoteTypeByShortCode($renewalQuoteProcess->quote_type);
        $quoteObject = $this->createQuoteObject($quoteType->code);

        if ($quoteObject && ($quote = $quoteObject->where('id', $renewalQuoteProcess->quote_id)->first())) {
            if (! empty($quote->payment_status_id) && $quote->payment_status_id != PaymentStatusEnum::DRAFT) {
                info('FetchPlans FN: fetchRenewalPlans'.' can not proceed with quote as payment is already in process. ');
                RenewalStatusProcess::where('id', $renewalStatusProcess->id)->update(['total_failed' => DB::raw('total_failed+1')]);

                return false;
            }

            if (! empty($leadData->provider_name) && ! empty($leadData->plan_name) && ! empty($leadData->plan_type)) {
                $planResponse = $this->createPlan($renewalQuoteProcess->data, $quote, $renewalStatusProcess->user_id);

                if (is_int($planResponse) && $planResponse == 200) {
                    info('FetchPlans FN: fetchRenewalPlans'.' plan created successfully for UUID: '.$quote->uuid);
                } else {
                    $error = (is_string($planResponse)) ? ('Error: '.$planResponse) : '';

                    if (isset($planResponse->message)) {
                        $error = 'Error: '.$planResponse->message;
                    }

                    info('FetchPlans FN: fetchRenewalPlans'.' plan creation failed. API Response ('.$error.') UUID: '.$quote->uuid.' . fetch plans skipped');
                    RenewalStatusProcess::where('id', $renewalStatusProcess->id)->update(['total_failed' => DB::raw('total_failed+1')]);

                    return false;
                }
            }

            $plansResponse = $this->getPlans($quote->uuid);
            if ($plansResponse === true) {
                info('FetchPlans FN: fetchRenewalPlans'.' Plans Fetched for quoteType: '.$renewalQuoteProcess->quote_type.' UUID: '.$quote->uuid);
                // update status to plans fetched
                $renewalQuoteProcess->update(['status' => RenewalProcessStatuses::PLANS_FETCHED, 'fetch_plans_status' => FetchPlansStatuses::FETCHED]);
                RenewalStatusProcess::where('id', $renewalStatusProcess->id)->update(['total_completed' => DB::raw('total_completed+1')]);
            } else {
                info('FetchPlans FN: fetchRenewalPlans'.' Failed to fetch plans for quoteType: '.$renewalQuoteProcess->quote_type.' UUID: '.$quote->uuid.' Error: '.(is_string($plansResponse)) ? $plansResponse : json_encode($plansResponse));
                RenewalStatusProcess::where('id', $renewalStatusProcess->id)->update(['total_failed' => DB::raw('total_failed+1')]);
            }
        } else {
            info('FetchPlans FN: fetchRenewalPlans QuoteId not found for leadId: '.$renewalQuoteProcess->id.' PolicyNumber: '.$renewalQuoteProcess->policy_number);
            RenewalStatusProcess::where('id', $renewalStatusProcess->id)->update(['total_failed' => DB::raw('total_failed+1')]);
        }
    }

    /**
     * @return bool
     */
    public function renewalsUploadUpdate($data)
    {
        // upload renewal file to azure
        $uploadedFile = $this->uploadRenewalsFile();

        // create lead record
        $renewalsUploadLead = $this->createRenewalsLead($uploadedFile, RenewalsUploadType::UPDATE_LEADS, $data);
        info('UAU FN: renewalsUploadUpdate File uploaded and renewals lead created');

        ProcessRenewalsUploadUpdate::dispatch($renewalsUploadLead);

        return true;
    }

    /**
     * @return bool
     */
    public function processUploadUpdate(RenewalsUploadLeads $renewalsUploadLead)
    {
        $logPrefix = 'UAU FN: processUploadUpdate RenewalLeadId: '.$renewalsUploadLead->id.' FileName: '.$renewalsUploadLead->file_name;

        try {
            info($logPrefix.' In Progress Now');

            $renewalsUploadLead->update(['status' => ProcessStatusCode::IN_PROGRESS]);

            $renewalsUploadLead = DB::transaction(function () use ($renewalsUploadLead) {
                // start file import
                $renewalsUpload = new UploadAndUpdateImport($this, $renewalsUploadLead);
                $renewalsUpload->import($renewalsUploadLead->file_path, 'azureIM');

                // todo: correct these values
                $validRows = $renewalsUpload->getValidCount();
                $failedRows = $renewalsUpload->getFailedCount();

                $renewalsUploadLead->update([
                    'cannot_upload' => $failedRows,
                    'good' => 0,
                    'total_records' => ($validRows + $failedRows),
                ]);

                return $renewalsUploadLead;
            });

            info($logPrefix.' excel data stored in DB.');

            $validationResult = $this->uploadedLeadsValidation($renewalsUploadLead);

            if ($validationResult) {
                $this->updateQuotes($renewalsUploadLead);
            }

            info($logPrefix.' validation and quote update is completed');

            return true;
        } catch (\Exception $exception) {
            Log::error($logPrefix.'Process Failed. Error: '.$exception->getMessage());
            $renewalsUploadLead->update(['status' => ProcessStatusCode::FAILED]);

            return false;
        }
    }

    /**
     * create quote object.
     *
     * @return false|mixed
     */
    public function createQuoteObject($quoteType)
    {
        info('fn: createQuoteObject QuoteType: '.$quoteType);
        $nameSpace = '\\App\\Models\\';

        if (checkPersonalQuotes($quoteType)) {
            $quoteType = QuoteTypes::PERSONAL->value;
        }

        $model = $nameSpace.ucfirst(strtolower($quoteType)).'Quote';

        return (class_exists($model)) ? $model::query() : false;
    }

    /**
     * get quote request detail class.
     *
     * @return string
     */
    public function getQuoteRequestDetailClass($quoteType)
    {
        return ucwords($quoteType).'QuoteRequestDetail';
    }

    /**
     * clean input.
     *
     * @return array|string|string[]
     */
    public function cleanValue($value)
    {
        $value = preg_replace('/\s/', '', strtolower(trim($value)));

        return str_replace([',', ':', '/', ';'], ',', $value);
    }

    /**
     * build customer data.
     *
     * @return array
     */
    public function buildCustomerData($data)
    {
        $data['customer_name'] = trim($data['customer_name']);

        // default values
        $customerData = [
            'first_name' => $data['customer_name'],
            'last_name' => '',
            'notes' => '',
        ];

        // check if name have last name
        if (strpos($data['customer_name'], ' ')) {
            $nameParts = explode(' ', $data['customer_name'], 2);
            $customerData['first_name'] = $nameParts[0];
            $customerData['last_name'] = $nameParts[1];
        }

        $emails = explode(',', $this->cleanValue($data['email']));
        $customerData['email'] = $emails[0];

        // check in case of additional emails, and add in notes
        if (count($emails) > 1) {
            unset($emails[0]);
            $customerData['additional_emails'] = $emails;
        }

        $mobileNos = explode(',', $this->cleanValue($data['mobile_no']));
        $customerData['mobile_no'] = strtok($mobileNos[0], ',');

        // check in case of additional mobile nos, and add in notes
        if (count($mobileNos) > 1) {
            unset($mobileNos[0]);
            $customerData['additional_mobiles'] = $mobileNos;
        }

        return $customerData;
    }

    /**
     * create new customer with additional mobiles and email if customer doesn't exist.
     *
     * @return mixed
     */
    public function getCustomer($customerData, $searchByName = false)
    {
        if ($searchByName) {
            return CustomerService::getCustomerByName($customerData['first_name'], $customerData['last_name']);
        }

        $customer = CustomerService::getCustomerByEmail($customerData['email']);

        // create new customer if not exists
        if (! isset($customer->id)) {
            $customer = Customer::create(Arr::only($customerData, ['first_name', 'last_name', 'email', 'mobile_no']));

            // create additional emails
            if (isset($customerData['additional_emails']) && count($customerData['additional_emails'])) {
                foreach ($customerData['additional_emails'] as $additionalEmail) {
                    $customer->additionalContactInfo()->create(['key' => 'email', 'value' => $additionalEmail]);
                }
            }

            // create additional mobile nos
            if (isset($customerData['additional_mobiles']) && count($customerData['additional_mobiles'])) {
                foreach ($customerData['additional_mobiles'] as $additionalMobile) {
                    $customer->additionalContactInfo()->create(['key' => 'mobile_no', 'value' => $additionalMobile]);
                }
            }
        }

        return $customer;
    }

    private function updateCustomer($quote, $customerData)
    {
        $customer = CustomerService::getCustomerById($quote->customer_id);

        // update primary email if changed
        if (! empty($customerData['email']) && $customer->email != $customerData['email']) {
            app(CustomerService::class)->makeAdditionalContactPrimary($quote, GenericRequestEnum::EMAIL, $customerData['email']);
        }

        // update primary mobile no if changed
        if (! empty($customerData['mobile_no']) && $customer->mobile_no != $customerData['mobile_no']) {
            app(CustomerService::class)->makeAdditionalContactPrimary($quote->refresh(), GenericRequestEnum::MOBILE_NO, $customerData['mobile_no']);
        }
    }

    /**
     * @return mixed
     */
    public function getQuoteTypeByShortCode($shortCode)
    {
        return QuoteType::where('short_code', $shortCode)->first();
    }

    /**
     * @return mixed
     */
    public function getClaimHistory($claimHistory)
    {
        return ClaimHistory::where('text', $claimHistory)->first();
    }

    /**
     * create quote for all businesses.
     *
     * @return void
     */
    public function createQuote(RenewalQuoteProcess $renewalQuoteProcess)
    {
        $data = $renewalQuoteProcess->data;
        $quoteType = $this->getQuoteTypeByShortCode($data['quote_type']);
        $renewalUploadLead = $renewalQuoteProcess->renewalUploadLead;

        $logPrefix = 'UAC FN: createQuote Policy NO: '.$data['policy_number'].' EndDate: '.$data['end_date'];

        $quote = DB::transaction(function () use ($renewalQuoteProcess, $logPrefix, $data, $quoteType, $renewalUploadLead) {
            $detailData = [];

            $quoteObject = $this->createQuoteObject($quoteType->code);
            if ($this->checkForExistingQuote($data, $renewalQuoteProcess, $renewalUploadLead, $quoteObject, $quoteType)) {
                return false;
            }
            $transApprovedId = $quoteType->short_code === QuoteTypeShortCode::CAR ? $this->getquoteStatusIdbyCode(quoteStatusCode::NEW_LEAD) : $this->getquoteStatusIdbyCode(quoteStatusCode::ALLOCATED);

            // advisor and previous advisors will be ignored when not exists
            $advisorId = $this->renewalsAddonService->getUserInfo($data['advisor']);
            $previousAdvisor = $this->renewalsAddonService->getUser($data['previous_advisor']);

            $quoteUuid = $this->generateUUID($quoteType->code, $quoteType->id);
            $isQuotePersonal = checkPersonalQuotes($quoteType->code);

            $customerData = $this->buildCustomerData($data);

            $customer = $this->getCustomer($customerData);

            $renewalBatchId = $quoteType->id !== QuoteTypeId::Car && isset($data['renewal_batch_id']) && $data['renewal_batch_id'] != null ? $data['renewal_batch_id'] ?? null : null;

            $transApprovedId = $this->isFakeEmail($customerData['email']) ? $this->getquoteStatusIdbyCode(quoteStatusCode::FAKE) : $transApprovedId;

            $quoteData = [
                'customer_id' => $customer->id,
                'first_name' => $customerData['first_name'],
                'last_name' => $customerData['last_name'],
                'email' => $customerData['email'],
                'mobile_no' => $customerData['mobile_no'],
                'uuid' => $quoteUuid,
                'code' => strtoupper($renewalQuoteProcess->quote_type).'-'.$quoteUuid,
                'source' => LeadSourceEnum::RENEWAL_UPLOAD,
                'advisor_id' => $advisorId,
                'renewal_batch' => $data['batch'],
                'renewal_batch_id' => $renewalBatchId ?? null,
                'quote_status_id' => $transApprovedId,
                'renewal_import_code' => $renewalUploadLead->renewal_import_code,
                'previous_quote_policy_number' => $data['policy_number'],
                'previous_policy_expiry_date' => $this->formatDate($data['end_date']),
                'previous_quote_policy_premium' => $data['premium'],
            ];

            if ($isQuotePersonal) {
                $detailData['additional_notes'] = $data['notes'].$customerData['notes'];
                if ($previousAdvisor) {
                    $detailData['previous_advisor_id'] = $previousAdvisor->id;
                }
                $quoteData['quote_type_id'] = $quoteType->id;
            } else {
                $quoteData['additional_notes'] = $data['notes'].$customerData['notes'];
                if ($previousAdvisor) {
                    $quoteData['previous_advisor_id'] = $previousAdvisor->id;
                }
            }

            if (! empty($data['insly_id'])) {
                $detailData['insly_id'] = $data['insly_id'];
            }

            if (! empty($data['insly_advisor_name'])) {
                $detailData['insly_advisor_name'] = $data['insly_advisor_name'];
            }

            $lookup = LookupRepository::where('key', LookupsEnum::TRANSACTION_TYPES)->where('code', LookupsEnum::EXT_CUSTOMER_RENWAL)->first();
            if ($lookup) {
                $quoteData['transaction_type_id'] = $lookup->id;
            }
            if ($quoteType->code == quoteTypeCode::Car) {
                $model = null;
                $make = CarMake::where('text', $data['make'])->first();
                if ($make) {
                    $model = CarModel::where('text', $data['model'])->where('car_make_code', $make->code)->first();
                }

                if ($model) {
                    $vehicleType = $this->renewalsAddonService->getVehicleType($model->vehicle_type_id);
                }

                $quoteData['is_quote_locked'] = true;
                $quoteData['car_make_id'] = $make->id ?? null;
                $quoteData['car_model_id'] = $model->id ?? null;
                $quoteData['year_of_manufacture'] = $data['year'];
                $quoteData['year_of_first_registration'] = $data['year'];
                $quoteData['vehicle_category'] = $vehicleType->category ?? null;

                if (! empty($data['product_type']) && ($carTypeOfInsuranceInstance = $this->renewalsAddonService->getCarTypeOfInsurance($data['product_type']))) {
                    $quoteData['car_type_insurance_id'] = $carTypeOfInsuranceInstance->id;
                }

                if (! empty($quoteData['car_model_id'])) {
                    if ($carModelDetail = CarModelDetail::active()
                        ->where('is_default', 1)
                        ->where('car_model_id', $quoteData['car_model_id'])
                        ->first()
                    ) {
                        $quoteData['cylinder'] = $carModelDetail->cylinder;
                        $quoteData['seat_capacity'] = $carModelDetail->seating_capacity;
                        $quoteData['vehicle_type_id'] = $carModelDetail->vehicle_type_id;
                    }
                }

                if ($tier = Tier::where('name', TiersEnum::TIER_R)->first()) {
                    $quoteData['tier_id'] = $tier->id;
                }
            }

            if (in_array($quoteType->code, [quoteTypeCode::Car])) {
                $quoteData['currently_insured_with'] = $this->insuranceProviderService->getProviderByCode($data['insurer'])->text;
            }

            if ($quoteType->code == quoteTypeCode::Health || $isQuotePersonal) {
                $quoteData['currently_insured_with_id'] = $this->insuranceProviderService->getProviderByCode($data['insurer'])->id;
            }

            // set business type insurance id
            if (! empty($data['product_type'] && $quoteType->code == quoteTypeCode::Business)) {
                if ($businessSubline = $this->renewalsAddonService->getBusinessSublineInsurance($data['product_type'])) {
                    $quoteData['business_type_of_insurance_id'] = $businessSubline->id;
                }
            }

            $quote = $quoteObject->create($quoteData);
            if (! $isQuotePersonal) {
                $this->syncQuote($quote, $quoteData);
            }

            if ($isQuotePersonal) {
                $quote->quoteDetail()->create($detailData);
            } else {
                $quotType = strtolower($quoteType->code);
                $class = $quotType.'QuoteRequestDetail';
                $quote->{$class}()->create($detailData);
            }
            if ($quoteType->code == quoteTypeCode::Car) {

                $quoteDetail = new QuoteAdditionalDetail;
                $quoteDetail->quote_uuid = $quote->uuid;
                $quoteDetail->quote_type_id = QuoteTypeId::Car;
                $quoteDetail->flags = (object) ['whatsapp_consent' => true];
                $quoteDetail->save();

                info($logPrefix.'-insertion in mongo db for : UUID: '.$quote->uuid);
            }

            // update advisor assign date/time
            if (! empty($advisorId)) {
                $this->updateAdvisorAssignedDateTime($quoteType->code, $quote->id, $renewalUploadLead->created_by_id, $advisorId);
            }

            $renewalQuoteProcess->update(['status' => RenewalProcessStatuses::PROCESSED, 'quote_id' => $quote->id]);

            RenewalsUploadLeads::where('id', $renewalUploadLead->id)->update(['good' => DB::raw('good+1')]);

            info($logPrefix.' Quote created. QuoteType: '.$data['quote_type'].' UUID: '.$quote->uuid);

            return $quote;
        });

        /*
         * create manual plan for health
         */
        if ($quote && $renewalQuoteProcess->quote_type == QuoteTypeShortCode::HEA && ! empty($data['plan_name'])) {
            $planResponse = $this->createHealthPlan($data, $quote);

            if (is_int($planResponse) && $planResponse == 200) {
                info($logPrefix.' manual plan for health created successfully for UUID: '.$quote->uuid);
            } else {
                info($logPrefix.' manual plan for health failed for UUID: '.$quote->uuid);
            }
        }

        return $quote;
    }

    /**
     * ignore fields having empty/null.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getNonEmptyValues($values)
    {
        return collect($values)->filter(function ($value) {
            return $value ?? null;
        })->toArray();
    }

    /**
     * Below logic is used to check if the quote is already created for the same policy number and expiry date within the same excel file.
     *
     * @param [type] $data
     * @param [type] $renewalQuoteProcess
     * @param [type] $renewalUploadLead
     * @param [type] $quoteObject
     * @return bool
     */
    private function checkForExistingQuote($data, $renewalQuoteProcess, $renewalUploadLead, $quoteObject, $quoteType)
    {
        $leadValidationErrors = collect($renewalQuoteProcess->validation_errors);
        $isQuotePersonal = checkPersonalQuotes($quoteType->code);

        $existingQuote = $isQuotePersonal ?
            $quoteObject->where('quote_type_id', $quoteType->id)->where('previous_quote_policy_number', $renewalQuoteProcess->policy_number)
                ->where('previous_policy_expiry_date', $this->formatDate($data['end_date']))
                ->where('source', '=', LeadSourceEnum::RENEWAL_UPLOAD)
                ->first() :
            $quoteObject->where('previous_quote_policy_number', $renewalQuoteProcess->policy_number)
                ->where('previous_policy_expiry_date', $this->formatDate($data['end_date']))
                ->where('source', '=', LeadSourceEnum::RENEWAL_UPLOAD)
                ->first();

        if ($existingQuote && $existingQuote != null) {
            $leadValidationErrors->push('Quote already created for this policy number, use upload and update');
            $renewalQuoteProcess->validation_errors = $leadValidationErrors;
            $renewalQuoteProcess->status = RenewalProcessStatuses::BAD_DATA;
            $renewalQuoteProcess->save();
            $renewalUploadLead->cannot_upload += 1;
            $renewalUploadLead->save();

            return true;
        }

        return false;
    }

    /**
     * convert date from d/m/Y to Y-m-d.
     *
     * @return string
     */
    public function formatDate($date)
    {
        return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
    }

    /**
     * @return mixed
     */
    public function updateQuote(RenewalQuoteProcess $renewalQuoteProcess)
    {
        $logPrefix = 'UAU FN: updateQuote';
        $data = $renewalQuoteProcess->data;
        $quoteType = $this->getQuoteTypeByShortCode($data['quote_type']);

        $isNameChanged = false;

        $quote = DB::transaction(function () use ($renewalQuoteProcess, $data, $logPrefix, &$isNameChanged) {
            throw_if($data['quote_type'] != QuoteTypeShortCode::CAR, 'Only Insurance Type Car is allowed to update lead');

            $renewalUploadLead = RenewalsUploadLeads::where('id', $renewalQuoteProcess->renewals_upload_lead_id)->first();

            info($logPrefix.' update quote started for PolicyNo: '.$data['policy_number'].' ID: '.$renewalQuoteProcess->id.' UploadLeadId: '.$renewalUploadLead->id);

            $quoteType = $this->getQuoteTypeByShortCode($data['quote_type']);
            // Previous Car Lead
            $quoteObject = $this->createQuoteObject(ucfirst($quoteType->code));

            $quote = $quoteObject->where('previous_quote_policy_number', $data['policy_number'])
                ->where('source', '=', LeadSourceEnum::RENEWAL_UPLOAD)
                ->where('previous_policy_expiry_date', $this->formatDate($data['end_date']))->first();

            throw_unless($quote, ('Quote not found for PolicyNumber: '.$data['policy_number'].' EndDate: '.$data['end_date'].' Batch: '.$renewalQuoteProcess->batch));

            $carMake = $this->renewalsAddonService->getCarMake($data['make']);
            $carModel = $this->renewalsAddonService->getCarModel($data['model'], $carMake);
            $newAdvisorId = $this->renewalsAddonService->getUserInfo($data['advisor']);
            $advisorId = $quote->advisor_id == null ? $newAdvisorId : $quote->advisor_id;
            $previousAdvisor = $this->renewalsAddonService->getUser($data['previous_advisor']);
            $claimHistory = $this->getClaimHistory($data['claim_history']);
            $nationality = Nationality::where('text', $data['nationality'])->first();
            $emirate = Emirate::where('text', $data['registration_location'])->first();
            $uaeLicenseHeldFor = UAELicenseHeldFor::where('text', $data['driving_experience'])->first();

            info($logPrefix.' fetched options from DB');

            if ($carModel) {
                $vehicleType = $this->renewalsAddonService->getVehicleType($carModel->vehicle_type_id);
            }

            if ($data['product_type'] != null) {
                $carTypeOfInsurance = $this->renewalsAddonService->getCarTypeOfInsurance($data['product_type']);
            }

            info($logPrefix.' quote found to update with UUID: '.$quote->uuid);

            $customerData = $this->buildCustomerData($data);

            // check if name is changed , then run AML again
            if ($quote->first_name != $customerData['first_name'] || $quote->last_name != $customerData['last_name']) {
                $isNameChanged = true;
            }

            $this->updateCustomer($quote, $customerData);
            $quoteData = $this->getNonEmptyValues([
                'first_name' => $customerData['first_name'],
                'last_name' => $customerData['last_name'],
                'email' => $customerData['email'],
                'mobile_no' => $customerData['mobile_no'],
                'dob' => (! empty($data['dob'])) ? $this->formatDate($data['dob']) : null,
                'car_type_insurance_id' => $carTypeOfInsurance->id ?? null,
                'claim_history_id' => $claimHistory->id ?? null,
                'nationality_id' => $nationality->id ?? null,
                'emirate_of_registration_id' => $emirate->id ?? null,
                'uae_license_held_for_id' => $uaeLicenseHeldFor->id ?? null,
                'car_value' => $data['car_value'],
                'car_value_tier' => $data['car_value'],
                'previous_policy_expiry_date' => (! empty($data['end_date'])) ? $this->formatDate($data['end_date']) : null,
                'advisor_id' => $advisorId,
                'renewal_batch' => $data['batch'],
                'renewal_batch_id' => null,
                'additional_notes' => $data['notes'],
                'car_make_id' => $carMake->id ?? null,
                'car_model_id' => $carModel->id ?? null,
                'vehicle_category' => $vehicleType->category ?? null,
                'year_of_manufacture' => $data['year'] ?? null,
                'previous_advisor_id' => ! empty($previousAdvisor) ? $previousAdvisor->name : '',
                'has_ncd_supporting_documents' => $data['nc_letter'],
            ]);

            $quoteData['is_gcc_standard'] = $data['is_gcc'] == 'Yes' ? 1 : 0;

            /*
             * API refresh plans when quote_updated_at have latest date
             */
            if (! $renewalUploadLead->skip_plans) {
                $quoteData['quote_updated_at'] = Carbon::now();
            }

            if (! empty($carModel) && ($carModelDetail = CarModelDetail::active()
                ->where('is_default', 1)
                ->where('car_model_id', $carModel->id)
                ->first())) {
                $quoteData['cylinder'] = $carModelDetail->cylinder;
                $quoteData['seat_capacity'] = $carModelDetail->seating_capacity;
                $quoteData['vehicle_type_id'] = $carModelDetail->vehicle_type_id;
            }

            if ($renewalUploadLead->skip_plans == 2 && $data['make'] == GenericRequestEnum::MOTOR_BIKE) {
                $quoteData['vehicle_type_id'] = VehicleType::where('text', GenericRequestEnum::BIKE)->first()->id ?? null;
            }

            $quoteData['vehicle_type_id'] = ! empty($data['vehicle_type_id'] ?? '') ? $data['vehicle_type_id'] : ($quoteData['vehicle_type_id'] ?? null);

            if ($quoteType->code == quoteTypeCode::Car && ! empty($data['year_of_first_registration'])) {
                $quoteData['year_of_first_registration'] = $data['year_of_first_registration'];
            } elseif ($quoteType->code == quoteTypeCode::Car && ! empty($data['year'])) {
                $quoteData['year_of_first_registration'] = $data['year'];
            }

            if (! empty($data['plan_type']) && in_array($data['plan_type'], [CarPlanType::TPL, CarPlanType::COMP])) {
                $quoteData['current_insurance_status'] = 'ACTIVE_'.$data['plan_type'];
            }

            if (in_array($quoteType->code, [quoteTypeCode::Car, quoteTypeCode::Bike]) && ($insurer = $this->insuranceProviderService->getProviderByCode($data['insurer']))) {
                $quoteData['currently_insured_with'] = $insurer->text;
            }

            info($logPrefix.' quote data setup to update for UUID: '.$quote->uuid);

            $quote->update($quoteData);
            if (! checkPersonalQuotes($quoteType)) {
                $this->syncQuote($quote, $quoteData);
            }

            info($logPrefix.' quote updated UUID: '.$quote->uuid);

            if (! empty($advisorId) && $quote->advisor_id != $advisorId) {
                $this->updateAdvisorAssignedDateTime($quoteType->code, $quote->id, $renewalUploadLead->created_by_id, $advisorId);
                info($logPrefix.' quote advisor assigned datetime updated UUID: '.$quote->uuid);
            } else {
                if ($renewalUploadLead->is_sic == 1) {
                    // add entry to quote tag as SIC
                    $quoteTagPayload = [
                        'name' => QuoteSegmentEnum::SIC->tag(),
                        'quote_type_id' => QuoteTypeId::Car,
                        'value' => 1,
                        'quote_uuid' => $quote->uuid,
                    ];

                    $checkExisted = QuoteTag::where('quote_uuid', $quote->uuid)->where('name', QuoteSegmentEnum::SIC->tag())->first();
                    ! $checkExisted && QuoteTag::create($quoteTagPayload);
                    // processing the SIC workflow trigger only and don't send OCB email
                    SendCarOCBIntroEmailJob::dispatch($quote->uuid, $previousAdvisor, true, true);
                    info($logPrefix.' Quote Tag created. : '.QuoteSegmentEnum::SIC->tag().' for UUID: '.$quote->uuid);
                }
            }

            // mark all other fetch plans pending records as outdated, it will help to target unique records during fetch plans process
            RenewalQuoteProcess::where([
                'quote_id' => $quote->id,
                'status' => RenewalProcessStatuses::PROCESSED,
                'type' => RenewalsUploadType::UPDATE_LEADS,
                'fetch_plans_status' => FetchPlansStatuses::PENDING,
            ])->update(['fetch_plans_status' => FetchPlansStatuses::OUTDATED]);

            // mark renewal quote process as processed and assign quote id
            $renewalQuoteProcess->update([
                'status' => RenewalProcessStatuses::PROCESSED,
                'quote_id' => $quote->id,
                'fetch_plans_status' => FetchPlansStatuses::PENDING,
            ]);

            RenewalsUploadLeads::where('id', $renewalUploadLead->id)->update(['good' => DB::raw('good+1')]);
            info($logPrefix.' quoted updated completed for UUID: '.$quote->uuid);

            return $quote;
        });

        return $quote;
    }

    /**
     * @return void
     */
    public function createHealthPlan($data, $quote)
    {
        $logPrefix = 'CreatePlan FN: createPlan UUID: '.$quote->uuid;
        info($logPrefix.' Create Health Plan Started');

        $provider = InsuranceProvider::where('code', $data['insurer'])->first();

        $healthPlan = HealthPlan::where([
            'provider_id' => $provider->id,
            'text' => $data['plan_name'],
        ])->first();

        $planData = [
            'quoteUID' => $quote->uuid,
            'update' => false,
        ];

        $planData['plans'][] = [
            'planId' => $healthPlan->id,
            'actualPremium' => $data['premium'],
            'discountPremium' => 0,
            'isManualUpdate' => false,
            'isManualPremium' => true,
        ];

        info($logPrefix.' setup create plan data is completed.');

        info($logPrefix.' PlanData: '.json_encode($planData));

        return $this->healthQuoteService->renewalCreatePlan($planData);
    }

    /**
     * create manual plan for Car.
     *
     * @return void
     */
    public function createPlan($data, $quote, $createdById)
    {
        $logPrefix = 'CreatePlan FN: createPlan UUID: '.$quote->uuid;
        info($logPrefix.' Create Plan Started');

        $provider = InsuranceProvider::where('text', $data['provider_name'])->first();

        $carPlan = CarPlan::where([
            'text' => $data['plan_name'],
            'repair_type' => $data['plan_type'],
            'provider_id' => $provider->id,
        ])->with(['carAddons' => function ($q) {
            $q->whereIn('code', [
                CarPlanAddonsCode::DRIVER_COVER,
                CarPlanAddonsCode::PASSENGER_COVER,
                CarPlanAddonsCode::CAR_HIRE,
                CarPlanAddonsCode::OMAN_COVER,
                CarPlanAddonsCode::BREAKDOWN_COVER,
            ])->with('carAddonOptions');
        }])->first();

        $planData = [
            'quoteUID' => $quote->uuid,
            'update' => false,
            'url' => strval(request()->current_url),
            'ipAddress' => request()->ip(),
            'userAgent' => request()->header('User-Agent'),
            'userId' => strval($createdById),
        ];

        $plan = [
            'planId' => $carPlan->id,
            'isDisabled' => false,
            'isManualUpdate' => false,
            'actualPremium' => $data['premium'] ?? 0,
            'discountPremium' => $data['premium'] ?? 0,
            'ancillaryExcess' => $data['ancillary_excess'] ?? 0,
            'carValue' => $data['car_value'] ?? 0,
        ];

        if (! empty($data['insurer_quote_no'])) {
            $plan['insurerQuoteNo'] = strval($data['insurer_quote_no']);
        }

        // excess will be used for comp or agency repair type
        if ($data['plan_type'] == CarPlanType::COMP || $data['plan_type'] == CarPlanType::AGENCY) {
            $plan['excess'] = $data['excess'];
        }

        // trim is optional
        if (! empty($data['trim'])) {
            if ($valuation = CarQuoteValuation::where('quote_request_id', $quote->id)->where('provider_id', $provider->id)->first()) {
                if (! empty($valuation->insurer_available_trims)) {
                    $trims = collect($valuation->insurer_available_trims)->keyBy('description')->toArray();
                    if (! empty($trims[$data['trim']]['admeId'])) {
                        $plan['insurerTrimId'] = $trims[$data['trim']]['admeId'];
                    }
                }
            }
        }

        $planAddons = collect($carPlan->carAddons)->keyBy('code')->toArray();

        $addons = [
            'driver_cover' => CarPlanAddonsCode::DRIVER_COVER,
            'passenger_cover' => CarPlanAddonsCode::PASSENGER_COVER,
            'car_hire' => CarPlanAddonsCode::CAR_HIRE,
            'oman_cover' => CarPlanAddonsCode::OMAN_COVER,
            'road_side_assistance' => CarPlanAddonsCode::BREAKDOWN_COVER,
        ];

        foreach ($addons as $key => $addonCode) {
            if (isset($planAddons[$addonCode]) && ! empty($data[$key])) {
                $addon = $planAddons[$addonCode];

                foreach ($addon['car_addon_options'] as $option) {
                    if (strtolower(trim($option['value'])) == strtolower(trim($data[$key]))) {
                        $price = $data[$key.'_amount'];

                        $planDataAddon = [
                            'addonId' => $option['addon_id'],
                            'addonOptionId' => $option['id'],
                            'price' => $price,
                            'isSelected' => ($price == 0),
                        ];

                        $plan['addons'][] = $planDataAddon;
                        break;
                    }
                }
            } else {
                info($logPrefix.'('.$addonCode.') not found');
            }
        }

        $planData['plans'][] = $plan;

        info($logPrefix.' PlanData: '.json_encode($planData));

        return $this->carQuoteService->renewalCreatePlan($planData);
    }

    public function getquoteStatusIdbyCode($quoteStatus)
    {
        return QuoteStatus::where('code', '=', $quoteStatus)->value('id');
    }

    public function generateRandomString()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 8; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public function renewalBatchEmailProcess($batch, RenewalsBatchEmails $renewalsBatchEmail, RenewalQuoteProcess $renewalQuoteProcess)
    {
        try {
            $carQuote = CarQuote::find($renewalQuoteProcess->quote_id);
            Log::info('Renewals OCB Email started for uuid: '.$carQuote->uuid);

            if ($carQuote->previous_quote_policy_number != null) {

                $response = Ken::request('/send-motor-renewal-ocb-whatsapp', 'post', [
                    'quoteUID' => $carQuote->uuid,
                    'filters' => [[
                        'field' => 'isRenewalSort',
                        'value' => false,
                    ]],
                    'callSource' => 'imcrm',
                ]);
                info('fn: renewalBatchEmailProcess renewals-ocb-whatsapp-'.json_encode($response).'- UUID: '.$carQuote->uuid);

                $listQuotePlans = $carQuote->car_make_id != null && $carQuote->car_model_id != null ? $this->carQuoteService->getPlans($carQuote->uuid, true, true, false, true) : [];
                $quotePlansCount = is_countable($listQuotePlans) ? count($listQuotePlans) : 0;
                $emailTemplateId = $this->getEmailTemplateId($carQuote, $quotePlansCount);

                $previousAdvisor = $this->getPreviousAdvisor($carQuote);
                $tierR = Tier::where('name', TiersEnum::TIER_R)->where('is_active', 1)->first();
                $emailData = (new CarEmailService($this->sendEmailCustomerService))->buildEmailData($carQuote, $listQuotePlans, $previousAdvisor, $tierR->id);
                Log::info('fn: renewalBatchEmailProcess Renewals OCB Email email data created');

                $this->attachPdfIfNeeded($carQuote, $listQuotePlans, $emailData);

                $responseCode = $this->sendEmail($carQuote, $emailTemplateId, $emailData);
                $this->handleResponse($responseCode, $carQuote, $renewalsBatchEmail, $renewalQuoteProcess);
            }

            Log::info('Renewals OCB Email completed for uuid: '.$carQuote->uuid);
        } catch (\Exception $exception) {
            Log::info('Renewals OCB Email failed error: '.$exception->getMessage());
            RenewalsBatchEmails::where('id', $renewalsBatchEmail->id)->update(['total_failed' => DB::raw('total_failed+1')]);
        }
    }

    /**
     * This function use to retrieve template id for emails
     *
     * @param  CarQuote  $carQuote
     * @param  int  $quotePlansCount
     * @return int
     */
    private function getEmailTemplateId($carQuote, $quotePlansCount)
    {
        $emailTemplateId = (int) $this->crudService->getOcbCustomerEmailTemplate($quotePlansCount);
        Log::info('fn: renewalBatchEmailProcess Renewals OCB Email email template id: '.$emailTemplateId);

        if (isset($carQuote->advisor_id)) {
            $advisor = $this->userService->getUserById($carQuote->advisor_id);
        } else {
            $key = $this->getNoAdvisorKey($quotePlansCount);
            $noAdvisorTemplateId = ApplicationStorage::where('key_name', $key)->first();
            $emailTemplateId = $noAdvisorTemplateId ? (int) $noAdvisorTemplateId->value : 551;
        }

        return $emailTemplateId;
    }

    /**
     * This function use to get key for no advisor email template
     *
     * @param  int  $quotePlansCount
     * @return string
     */
    private function getNoAdvisorKey($quotePlansCount)
    {
        if ($quotePlansCount == 0) {
            return ApplicationStorageEnums::OCB_NEW_BUSINESS_ZERO_PLAN;
        } elseif ($quotePlansCount == 1) {
            return ApplicationStorageEnums::OCB_NEW_BUSINESS_SINGLE_PLAN;
        } else {
            return ApplicationStorageEnums::OCB_NEW_BUSINESS_MULTIPLE_PLANS;
        }
    }

    /**
     * This function use to get previous advisor
     *
     * @param  CarQuote  $carQuote
     * @return mixed
     */
    private function getPreviousAdvisor($carQuote)
    {
        if (! empty($carQuote->previous_advisor_id)) {
            return $this->userService->getUserById($carQuote->previous_advisor_id);
        }

        return null;
    }

    /**
     * This function use to attach pdf if needed
     *
     * @param  CarQuote  $carQuote
     * @param  array  $listQuotePlans
     * @param  object  $emailData
     */
    private function attachPdfIfNeeded($carQuote, $listQuotePlans, &$emailData)
    {
        if (count($listQuotePlans) > 0) {
            $pdfData = [
                'plan_ids' => collect($listQuotePlans)->take(5)->pluck('id')->toArray(),
                'quote_uuid' => $carQuote->uuid,
            ];

            $pdf = $this->carQuoteService->exportPlansPdf(quoteTypeCode::Car, $pdfData, json_decode(json_encode(['quotes' => ['plans' => $listQuotePlans], 'isDataSorted' => true])));

            if (isset($pdf['error'])) {
                info('Failed to generate PDF for UUID: '.$carQuote->uuid.' Error: '.$pdf['error']);
            } else {
                $emailData->pdfAttachment = (object) $pdf;
            }
        }
    }

    /**
     * This function use to send email
     *
     * @param  CarQuote  $carQuote
     * @param  int  $emailTemplateId
     * @param  object  $emailData
     * @return int
     */
    private function sendEmail($carQuote, $emailTemplateId, $emailData)
    {
        info('Renewals OCB Email sending email to email: '.$carQuote->email);
        info('fn: renewalBatchEmailProcess Renewals OCB Email email template id: '.$emailTemplateId);
        info('Renewals OCB Email check email data: '.json_encode($emailData));

        if (isset($carQuote->advisor_id)) {
            return $this->sendEmailCustomerService->sendRenewalsOcbEmail($emailTemplateId, $emailData, 'car-quote-one-click-buy-batch');
        } else {
            info('Renewals OCB Email sending without advisor');
            $responseCode = $this->sendEmailCustomerService->sendNonAdvisorIntroEmail($emailData, 'car-quote-one-click-buy-batch', $emailTemplateId);
            $this->triggerBirdWorkflow($emailData, $carQuote->mobile_no, $carQuote->uuid);

            return $responseCode;
        }
    }

    /**
     * This function use to trigger bird workflow
     *
     * @param  object  $emailData
     */
    private function triggerBirdWorkflow($emailData, $mobile, $uuid)
    {
        $birdEmailData = [
            'SendNewProcessRenewalEmail' => true,
            'customerEmail' => $emailData->customerEmail,
            'phone' => formatMobileNoWithoutPlus($mobile),
            'customerName' => $emailData->customerName,
            'quotePlanLink' => $emailData->quoteLink,
            'instantAlfredLink' => $emailData->quoteLink.'?IA=true',
            'refID' => $emailData->carQuoteId,
            'requestForAdvisor' => $emailData->requestAdvisorLink,
            'quoteUUID' => $uuid,
            'tag' => ThirdPartyTagEnum::BIRD_SIC_MOTOR_RENEWAL_TAG,
        ];

        $sicEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_SIC_MOTOR_RENEWAL_WORKFLOW)->first();
        info('Renewals OCB Email No advisor: workflow trigger on BIRD, BIRD_SIC_MOTOR_RENEWAL_WORKFLOW value: '.$sicEvent->value);

        if ($sicEvent) {
            app(BirdService::class)->triggerWebHookRequest($sicEvent->value, $birdEmailData);
        }
    }

    /**
     * This function use to handle response
     *
     * @param  int  $responseCode
     * @param  CarQuote  $carQuote
     * @param  RenewalsBatchEmails  $renewalsBatchEmail
     * @param  RenewalQuoteProcess  $renewalQuoteProcess
     */
    private function handleResponse($responseCode, $carQuote, $renewalsBatchEmail, $renewalQuoteProcess)
    {
        info('Renewals OCB Email response: '.$responseCode);

        if ($responseCode == 201) {
            $this->updateQuoteStatus($carQuote);
            $this->recordOcbSentDate($carQuote);
            Log::info('Renewals OCB Email sent to uuid: '.$carQuote->uuid.' ResponseCode: '.$responseCode);
            RenewalsBatchEmails::where('id', $renewalsBatchEmail->id)->update(['total_sent' => DB::raw('total_sent+1')]);
            RenewalQuoteProcess::where('id', $renewalQuoteProcess->id)->update(['email_sent' => 1]);
        } else {
            Log::error('Renewals OCB Email failed for uuid: '.$carQuote->uuid.' ResponseCode: '.$responseCode.' batchEmailId:'.$renewalsBatchEmail->id.' Customer EmailAddress:'.$carQuote->email);
            RenewalsBatchEmails::where('id', $renewalsBatchEmail->id)->update(['total_failed' => DB::raw('total_failed+1')]);
        }
    }

    /**
     * This function use to update quote status
     *
     * @param  CarQuote  $carQuote
     */
    private function updateQuoteStatus($carQuote)
    {
        $notes = 'Change quote status to Quoted as OCB sent';
        app(QuoteStatusService::class)->updateQuoteStatus(QuoteTypes::CAR->id(), $carQuote->uuid, quoteStatusCode::QUOTED, [], $notes);
    }

    /**
     * This function use to record OCB sent date
     *
     * @param  CarQuote  $carQuote
     */
    private function recordOcbSentDate($carQuote)
    {
        $carQuote->carQuoteRequestDetail->updateOrCreate(
            ['car_quote_request_id' => $carQuote->id],
            ['ocb_sent_date' => Carbon::now()]
        );
    }

    /**
     * //$modelName, $quoteRequestIdName.
     *
     * @param  $quoteRequestIdName
     * @return false|mixed
     */
    public function updateAdvisorAssignedDateTime($quoteType, $quoteId, $currentUserId, $advisorId)
    {
        if (checkPersonalQuotes($quoteType)) {
            $quoteRequestDetail = '\\App\\Models\\PersonalQuoteDetail';
            $quoteRequestField = 'personal_quote_id';
        } else {
            $quoteRequestDetail = '\\App\\Models\\'.ucfirst($quoteType).'QuoteRequestDetail';
            $quoteRequestField = strtolower($quoteType).'_quote_request_id';
        }

        // check if record exists in model_detail table
        if ($quoteDetail = $quoteRequestDetail::where($quoteRequestField, $quoteId)->first()) {
            $quoteDetail->update([
                'advisor_assigned_by_id' => $currentUserId,
                'advisor_assigned_date' => Carbon::now(),
            ]);
        } else {
            $quoteDetail = $quoteRequestDetail::create([
                $quoteRequestField => $quoteId, // i-e: $quoteRequestIdName = car_quote_request_id
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'advisor_assigned_by_id' => $currentUserId,
                'advisor_assigned_date' => Carbon::now(),
            ]);
        }

        return $quoteDetail;
    }

    public function uploadedLeadsValidation(RenewalsUploadLeads $renewalsUploadLead)
    {
        $isSIC = $renewalsUploadLead->is_sic;
        RenewalQuoteProcess::where('status', RenewalProcessStatuses::NEW)->where('renewals_upload_lead_id', $renewalsUploadLead->id)->chunkById(50, function ($leads) use ($isSIC) {
            foreach ($leads as $lead) {
                $leadValidationErrors = collect();

                if ($lead->type == RenewalsUploadType::CREATE_LEADS) {
                    if (! QuoteType::where('short_code', $lead->quote_type)->first()) {
                        $leadValidationErrors->push('Invalid Insurance Type Provided');
                    }
                }

                $quoteType = $this->getQuoteTypeByShortCode($lead->quote_type);
                $quoteTypeObject = $this->createQuoteObject($quoteType->code);
                $isQuotePersonal = checkPersonalQuotes($quoteType->code);

                $leadData = (object) $lead->data;
                info('CQF VALIDATION - Checking Quote Existence PolicyNo - '.$lead->policy_number.' Quote Type - '.json_encode($quoteTypeObject));
                if ($lead->type == RenewalsUploadType::UPDATE_LEADS && ! $lead->policy_number) {
                    $leadValidationErrors->push('Policy Number is mandatory for update process');
                } elseif ($lead->type == RenewalsUploadType::UPDATE_LEADS && $lead->policy_number && $quoteTypeObject) {
                    info('CQF VALIDATION - Checking Quote Existence 1 - '.$lead->policy_number);
                    if (! $quoteTypeObject->where('previous_quote_policy_number', $lead->policy_number)->where('previous_policy_expiry_date', $this->formatDate($leadData->end_date))->where('source', '=', LeadSourceEnum::RENEWAL_UPLOAD)->first()) {
                        $leadValidationErrors->push('Quote does not exist for this policy number, use upload and create');
                    } else {
                        info('CQF VALIDATION - Quote Found for Update - '.$lead->policy_number);
                    }
                }
                // If the request is for Travel Renewal Expired Process, it will skip the insurer conditions.
                if ($lead->quote_type != quoteTypeCode::TRA) {
                    if (! $leadData->insurer) {
                        $leadValidationErrors->push('Insurance Provider is required');
                    } elseif (! ($insurer = InsuranceProvider::where('code', $leadData->insurer)->first())) {
                        $leadValidationErrors->push('Invalid Insurance Code Provided');
                    }
                }

                if ($lead->quote_type == QuoteTypeShortCode::HEA && $lead->type == RenewalsUploadType::CREATE_LEADS && isset($insurer->id) && ! empty($leadData->plan_name)) {
                    if (! HealthPlan::where(['provider_id' => $insurer->id, 'text' => $leadData->plan_name])->first()) {
                        $leadValidationErrors->push('Invalid Plan Name Provided');
                    }
                }

                if ($lead->type == RenewalsUploadType::UPDATE_LEADS && ! $leadData->product_type) {
                    $leadValidationErrors->push('Product Type is Required');
                }
                if ($leadData->advisor && $isSIC == 0 && ! User::where('email', $leadData->advisor)->first()) {
                    $leadValidationErrors->push('Invalid Advisor Email Address');
                }
                if (isset($leadData->start_date) && $leadData->start_date && ! $this->validateDate($leadData->start_date)) {
                    $leadValidationErrors->push('Invalid Start Date');
                }

                if (isset($leadData->end_date) && $leadData->end_date && ! $this->validateDate($leadData->end_date)) {
                    $leadValidationErrors->push('Invalid Policy End date');
                }

                if (isset($leadData->dob) && $leadData->dob) {
                    if (! $this->validateDate($leadData->dob)) {
                        $leadValidationErrors->push('Invalid Date of Birth');
                    } else {
                        $dob = Carbon::createFromFormat('d/m/Y', $leadData->dob);
                        $minDate = Carbon::createFromFormat('d/m/Y', '01/01/1930');

                        if ($dob->lt($minDate)) {
                            $leadValidationErrors->push('Date of birth cannot be earlier than 01/01/1930');
                        }
                        if ($dob->age < 18) {
                            $leadValidationErrors->push('Customer age should be 18 years or more');
                        }
                        if ($dob->gt(now())) {
                            $leadValidationErrors->push('Date of birth cannot be future date');
                        }
                    }
                }

                if ($lead->type == RenewalsUploadType::CREATE_LEADS && $lead->policy_number && $quoteTypeObject) {
                    $quoteExist = $isQuotePersonal == 1 ? $quoteTypeObject->where('quote_type_id', $quoteType->id)->where('previous_quote_policy_number', $lead->policy_number)->where('previous_policy_expiry_date', $this->formatDate($leadData->end_date))->where('source', '=', LeadSourceEnum::RENEWAL_UPLOAD)->first() : $quoteTypeObject->where('previous_quote_policy_number', $lead->policy_number)->where('previous_policy_expiry_date', $this->formatDate($leadData->end_date))->where('source', '=', LeadSourceEnum::RENEWAL_UPLOAD)->first();

                    if ($quoteExist != null && isset($quoteExist)) {
                        $leadValidationErrors->push('Quote already created for this policy number, use upload and update');
                    }
                }

                switch (strtoupper($lead->quote_type)) {
                    case QuoteTypeShortCode::CAR:
                        if ($lead->type == RenewalsUploadType::UPDATE_LEADS) {
                            if ($leadData->make && ! CarMake::where('text', $leadData->make)->first()) {
                                $leadValidationErrors->push('Invalid Car Make');
                            }

                            if ($leadData->model && ! CarModel::where('text', $leadData->model)->first()) {
                                $leadValidationErrors->push('Invalid Car Model');
                            }

                            if ($leadData->product_type != carTypeInsuranceCode::Comprehensive && $leadData->product_type != carTypeInsuranceCode::ThirdPartyOnly) {
                                $leadValidationErrors->push('Invalid Product Type, needs to be Third Party Only or Comprehensive');
                            }
                            if ($leadData->nationality && ! Nationality::where('text', $leadData->nationality)->first()) {
                                $leadValidationErrors->push('Invalid Nationality Text');
                            }
                            if ($leadData->claim_history && ! ClaimHistory::where('text', $leadData->claim_history)->first()) {
                                $leadValidationErrors->push('Invalid Claim History');
                            }

                            if (! empty($leadData->driving_experience) && ! UAELicenseHeldFor::where('text', $leadData->driving_experience)->first()) {
                                $leadValidationErrors->push('Invalid Driving Experience');
                            }

                            if ($leadData->premium) {
                                if (! $leadData->plan_type) {
                                    $leadValidationErrors->push('Repair Type is required');
                                } elseif ($leadData->plan_type == CarPlanType::TPL && $leadData->excess != 0) {
                                    $leadValidationErrors->push('Excess should be 0 with TPL');
                                } elseif ($leadData->plan_type == CarPlanType::COMP || $leadData->plan_type == CarPlanType::AGENCY) {
                                    if (! $leadData->excess) {
                                        $leadValidationErrors->push('Excess should be > 0 with Repair Type - COMP or AGENCY');
                                    }
                                }

                                if ($lead->type == RenewalsUploadType::UPDATE_LEADS && $leadData->premium > 0 && ! $leadData->insurer_quote_no) {
                                    $leadValidationErrors->push('Insurer Quote No is required');
                                }
                                if (! $leadData->premium && $leadData->excess) {
                                    $leadValidationErrors->push('Renewal Premium is required with Excess');
                                }
                                if (! $leadData->provider_name) {
                                    $leadValidationErrors->push('Provider Name is required');
                                }
                                if (! $leadData->plan_name) {
                                    $leadValidationErrors->push('Plan Name is required');
                                }

                                if ($leadData->provider_name && $leadData->plan_type && $leadData->plan_name && $insuranceProvider = InsuranceProvider::where('text', $leadData->provider_name)->where('code', $leadData->insurer)->first()) {
                                    if (! $carPlan = CarPlan::where('repair_type', $leadData->plan_type)->where('text', $leadData->plan_name)->where('provider_id', $insuranceProvider->id)->first()) {
                                        $leadValidationErrors->push('Invalid Insurer Plan Name or Repair Type');
                                    }
                                } else {
                                    $leadValidationErrors->push('Invalid Insurance Provider & Provider Name Combination Provided');
                                }
                                if (isset($carPlan)) {
                                    if (! $leadData->driver_cover) {
                                        $leadValidationErrors->push('PAB Driver is required with Renewal Premium & Excess');
                                    }
                                    if ($leadData->driver_cover_amount == '') {
                                        $leadValidationErrors->push('Amount - PAB Driver is required with Renewal Premium & Excess');
                                    }
                                    if (! $leadData->passenger_cover) {
                                        $leadValidationErrors->push('PAB Passenger is required with Renewal Premium & Excess');
                                    }
                                    if ($leadData->driver_cover_amount == '') {
                                        $leadValidationErrors->push('Amount - PAB Driver is required with Renewal Premium & Excess');
                                    }
                                    if ($leadData->plan_type != CarPlanType::TPL && $leadData->insurer != InsuranceProvidersEnum::TM && ! $leadData->car_hire) {
                                        $leadValidationErrors->push('Rent a car is required with TPL & TM');
                                    }
                                    if ($leadData->plan_type != CarPlanType::TPL && $leadData->insurer != 'TM' && $leadData->car_hire_amount == '') {
                                        $leadValidationErrors->push('Amount - Rent a Car is required with TPL & TM');
                                    }

                                    if ($leadData->plan_type != CarPlanType::TPL && $leadData->oman_cover_amount == '') {
                                        $leadValidationErrors->push('Amount- Oman Cover is required');
                                    }
                                    if ($leadData->road_side_assistance_amount == '') {
                                        $leadValidationErrors->push('Amount- Road Side Assistance is required with Renewal Premium & Excess');
                                    }
                                    if ($leadData->plan_type != CarPlanType::TPL && ! $leadData->oman_cover) {
                                        $leadValidationErrors->push('Oman cover is required');
                                    }
                                    if (! $leadData->road_side_assistance) {
                                        $leadValidationErrors->push('Road Side Assistance is required with Renewal Premium & Excess');
                                    }
                                    if (! $leadData->year_of_first_registration) {
                                        $leadValidationErrors->push('First Year of Registration is required with Renewal Premium & Excess');
                                    }

                                    $carPlan->load([
                                        'carAddons' => function ($q) {
                                            $q->whereIn('code', [
                                                CarPlanAddonsCode::DRIVER_COVER,
                                                CarPlanAddonsCode::PASSENGER_COVER,
                                                CarPlanAddonsCode::CAR_HIRE,
                                                CarPlanAddonsCode::OMAN_COVER,
                                                CarPlanAddonsCode::BREAKDOWN_COVER,
                                            ])->with('carAddonOptions');
                                        },
                                    ]);

                                    $planAddons = collect($carPlan->carAddons)->keyBy('code')->toArray();

                                    $addons = [
                                        'driver_cover' => CarPlanAddonsCode::DRIVER_COVER,
                                        'passenger_cover' => CarPlanAddonsCode::PASSENGER_COVER,
                                        'car_hire' => CarPlanAddonsCode::CAR_HIRE,
                                        'oman_cover' => CarPlanAddonsCode::OMAN_COVER,
                                        'road_side_assistance' => CarPlanAddonsCode::BREAKDOWN_COVER,
                                    ];

                                    foreach ($addons as $key => $addonCode) {
                                        info('planType:'.$leadData->plan_type.' insurer:'.$leadData->insurer.' addonCode:'.$addonCode);

                                        if (
                                            $leadData->plan_type == CarPlanType::TPL &&
                                            $leadData->insurer == InsuranceProvidersEnum::TM &&
                                            $addonCode == CarPlanAddonsCode::CAR_HIRE
                                        ) {
                                            continue;
                                        }

                                        if (isset($planAddons[$addonCode])) {
                                            $addon = $planAddons[$addonCode];

                                            $found = false;
                                            foreach ($addon['car_addon_options'] as $option) {
                                                if (strtolower(trim($option['value'])) == strtolower(trim($leadData->{$key}))) {
                                                    $found = true;
                                                    break;
                                                }
                                            }

                                            if (! $found) {
                                                $leadValidationErrors->push('Invalid car addon option provided for  - '.$addonCode);
                                            }
                                        }
                                    }
                                }
                            }
                            if (! empty($leadData->registration_location) && ! Emirate::where('text', $leadData->registration_location)->first()) {
                                $leadValidationErrors->push('Invalid Registration Location');
                            }
                            if ($leadData->previous_advisor && ! User::where('email', $leadData->previous_advisor)->first()) {
                                $leadValidationErrors->push('Invalid Previous Advisor Email');
                            }

                            // Validation batch for car removed as per the discussion with the team
                            // click up: https://app.clickup.com/t/86eqmrdec
                            // if ($leadData->batch) {
                            //     $batchRef = $leadData->batch == null ? false : RenewalBatch::where([['name', $leadData->batch], ['quote_type_id', QuoteTypeId::Car]])->first();
                            //     ! $batchRef && $leadValidationErrors->push('Invalid Renewal Batch Provided');
                            // }
                        }
                        break;
                    default:
                        $checkBatch = $this->validateBatch($leadData->batch, $leadData->end_date, $lead->type == RenewalsUploadType::CREATE_LEADS, $lead);
                        ! $checkBatch && $leadValidationErrors->push('Invalid Renewal Batch Provided');
                        break;
                }

                if ($leadValidationErrors->count() == 0) {
                    $lead->status = RenewalProcessStatuses::VALIDATED;
                } else {
                    $lead->validation_errors = $leadValidationErrors;
                    $lead->status = RenewalProcessStatuses::BAD_DATA;
                }

                $lead->save();

                if ($lead->status == RenewalProcessStatuses::BAD_DATA) {
                    $renewalUploadLead = $lead->renewalUploadLead;
                    $renewalUploadLead->cannot_upload += 1;
                    $renewalUploadLead->save();
                }
            }
        }, $column = 'id');

        return true;
    }

    /**
     * This function use to validate batch for non motors only
     *
     * @param  string  $batchName
     * @param  string  $endDate
     * @return void
     */
    private function validateBatch($batchName, $endDate, $isCreated, &$lead)
    {
        info('Validating batch: '.$batchName.' with end date: '.$endDate);
        // Extract year from endDate
        $endDate = Carbon::createFromFormat('d/m/Y', $endDate);
        $year = $endDate->format('Y');

        // Extract week number from endDate and remove leading zero if present
        $weekNumber = 'W'.$endDate->weekOfYear;

        info('Validating year: '.$year.' with week number: '.$weekNumber);

        // Validate batch name by checking if it contains the week number
        // if ((strpos($batchName, $weekNumber) === false || $batchName != $weekNumber) && !$isCreated ) {
        //     info('Batch name does not contain week number');

        //     return false;
        // }

        // Check if the batch exists in the table with the extracted year and week number
        $batch = RenewalBatch::where([
            ['name', $weekNumber.'-'.$year],
            ['quote_type_id', null],
        ])->first();

        if ($batch) {
            info('Batch found');
            if ($isCreated) {
                $data = $lead->data;
                $data['renewal_batch_id'] = $batch->id;
                $lead->data = $data;
            }

            return true;
        }
        info('Batch not found with year: '.$year.' and batch name: '.$batchName);

        return false;
    }

    private function validateDate($date, $format = 'd/m/Y')
    {
        $d = DateTime::createFromFormat($format, $date);

        return $d && $d->format($format) === $date;
    }

    public function getProcessTotalLeads($batch)
    {
        return RenewalQuoteProcess::where([
            'quote_type' => QuoteTypeShortCode::CAR,
            'batch' => $batch,
            'type' => RenewalsUploadType::UPDATE_LEADS,
        ])->distinct('quote_id')->count();
    }

    public function getProcessTotalLeadsWithPlans($batch)
    {
        return RenewalQuoteProcess::where([
            'quote_type' => QuoteTypeShortCode::CAR,
            'batch' => $batch,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'status' => RenewalProcessStatuses::PLANS_FETCHED,
            'fetch_plans_status' => FetchPlansStatuses::FETCHED,
        ])->distinct('quote_id')->count();
    }

    public function getProcessLeads($batch)
    {
        return RenewalQuoteProcess::select('quote_id as id')->where([
            'quote_type' => QuoteTypeShortCode::CAR,
            'batch' => $batch,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'status' => RenewalProcessStatuses::PLANS_FETCHED,
            'fetch_plans_status' => FetchPlansStatuses::FETCHED,
        ])->distinct('quote_id')->get();
    }

    public function getOcbLeadsQuery($batch)
    {
        return RenewalQuoteProcess::select('id', 'quote_id')->where([
            'quote_type' => QuoteTypeShortCode::CAR,
            'batch' => $batch,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'status' => RenewalProcessStatuses::PLANS_FETCHED,
            'email_sent' => 0,
            'fetch_plans_status' => FetchPlansStatuses::FETCHED,
        ])
            ->whereHas('carQuote', function ($q) {
                $q->whereNull('paid_at');
            })->groupBy('quote_id');
    }

    public function getPendingOcbLeadsTotal($batch)
    {
        return $this->getOcbLeadsQuery($batch)->get()->count();
    }

    public function scheduleRenewalsOcbEmails($batch, RenewalsBatchEmails $renewalsBatchEmail)
    {
        $logPrefix = 'Renewals OCB email ';

        try {
            $jobs = null;

            $this->getOcbLeadsQuery($batch)
                ->chunkById(50, function ($leads) use (&$jobs, $batch, $renewalsBatchEmail) {
                    foreach ($leads as $lead) {
                        $jobs[] = new RenewalBatchEmailJob($batch, $renewalsBatchEmail, $lead);
                    }
                });

            if ($jobs != null && count($jobs)) {
                info($logPrefix.'total leads to be scheduled for OCB : '.count($jobs));
                Haystack::build()
                    ->onQueue('renewals')
                    ->addJobs($jobs)
                    ->then(function () use ($logPrefix, $renewalsBatchEmail, $batch) {
                        info($logPrefix.' all jobs completed successfully');
                        $renewalsBatchEmail->update(['status' => ProcessStatusCode::COMPLETED]);

                        if (($enableFollowups = ApplicationStorage::where('key_name', ApplicationStorageEnums::ENABLE_AUTO_FOLLOWUP)->first())) {
                            if ($enableFollowups->value == 1) {
                                info($logPrefix.'EnableFollowup is: ON. Renewals OCB email followup job dispatched for batch:'.$batch);
                                CreateRenewalsWorkflowJob::dispatch($renewalsBatchEmail);
                            } else {
                                info($logPrefix.'EnableFollowup is: OFF batch: '.$batch.' is not scheduled');
                            }
                        }
                    })
                    ->catch(function () use ($logPrefix, $renewalsBatchEmail) {
                        info($logPrefix.' one of batch is failed. ');
                        $renewalsBatchEmail->update(['status' => ProcessStatusCode::FAILED]);
                    })
                    ->finally(function () use ($logPrefix) {
                        info($logPrefix.' everything done');
                    })
                    ->allowFailures()
                    ->withDelay(1)
                    ->dispatch();
            } else {
                info($logPrefix.' No leads to schedule OCB email');
                $renewalsBatchEmail->update(['status' => ProcessStatusCode::COMPLETED]);
            }
        } catch (\Exception $exception) {
            info($logPrefix.' one of batch is failed. Exception : '.$exception->getMessage());
            $renewalsBatchEmail->update(['status' => ProcessStatusCode::FAILED]);
        }

        // todo: remove this code
        //        foreach ($batchLeads as $key => $batchLead) {
        //            $isCompleted = $batchLeadsCount - 1 == $key ? 1 : 0;
        //            dispatch(new RenewalBatchEmailJob($batchLead->quote_id, $batchEmail->id, QuoteTypeId::Car, $isCompleted, $batch));
        //            sleep(0.5);
        //        }
    }

    // todo: remove this code
    //    public function updateRenewalQuoteEmailSent($batch, $quoteId)
    //    {
    //        info('updateRenewalQuoteEmailSent START batch: '.$batch.' quoteId: '.$quoteId);
    //        $emailSent = RenewalQuoteProcess::where([
    //            'quote_type' => QuoteTypeShortCode::CAR,
    //            'batch' => $batch,
    //            'type' => RenewalsUploadType::UPDATE_LEADS,
    //            'status' => RenewalProcessStatuses::PLANS_FETCHED,
    //            'email_sent' => 0,
    //            'fetch_plans_status' => FetchPlansStatuses::FETCHED,
    //            'quote_id' => $quoteId,
    //        ])->first();
    //        if ($emailSent) {
    //            $emailSent->email_sent = 1;
    //            $emailSent->save();
    //        }
    //        info('updateRenewalQuoteEmailSent END emailSent->id: '.$emailSent->id);
    //    }

    /**
     * Travel renewals upload and create.
     *
     * @return mixed
     */
    public function travelRenewalsUploadCreate($data)
    {
        $isTravel = true;
        // upload renewal file to azure
        $uploadedFile = $this->uploadRenewalsFile($isTravel);

        // create lead record
        $renewalsUploadLead = $this->createRenewalsLead($uploadedFile, RenewalsUploadType::CREATE_LEADS, $data);
        info('UAT FN: renewalsUploadCreate File uploaded and renewals lead created');

        // start import process
        ProcessTravelRenewalsUploadCreate::dispatch($renewalsUploadLead);

        return true;
    }

    /**
     * this will be triggered by job to start import process for upload and create.
     *
     * @return void
     */
    public function travelProcessUploadCreate(RenewalsUploadLeads $renewalsUploadLead)
    {
        $logPrefix = 'UAC FN: travelExpiredRenewalsUploadCreate RenewalLeadId: '.$renewalsUploadLead->id.' FileName: '.$renewalsUploadLead->file_name;

        try {
            $renewalsUploadLead->update(['status' => ProcessStatusCode::IN_PROGRESS]);

            info($logPrefix.' In Progress Now');

            $renewalsUploadLead = DB::transaction(function () use ($renewalsUploadLead) {
                // start file import
                $renewalsUpload = new TravelUploadAndCreateImport($renewalsUploadLead);
                $renewalsUpload->import($renewalsUploadLead->file_path, 'azureIM');

                // update counts
                $validRows = $renewalsUpload->getValidCount();
                $failedRows = $renewalsUpload->getFailedCount();

                $renewalsUploadLead->update([
                    'cannot_upload' => $failedRows,
                    'good' => 0,
                    'total_records' => ($validRows + $failedRows),
                ]);

                return $renewalsUploadLead;
            });

            info($logPrefix.' excel data stored in DB');

            $validationResult = $this->uploadedLeadsValidation($renewalsUploadLead);
            if ($validationResult) {
                $this->createTravelQuotes($renewalsUploadLead);
            }

            info($logPrefix.' validation and quote creation is completed');

            return true;
        } catch (\Exception $exception) {
            $renewalsUploadLead->update(['status' => ProcessStatusCode::FAILED]);
            Log::error($logPrefix.'Process Failed. Error: '.$exception->getMessage());

            return false;
        }
    }

    public function createTravelQuotes(RenewalsUploadLeads $renewalsUploadLead)
    {
        $logPrefix = 'UAC fn: createTravelQuotes ';
        info($logPrefix.' QuoteCreation started');

        try {
            $jobs = null;

            RenewalQuoteProcess::where([
                'renewals_upload_lead_id' => $renewalsUploadLead->id,
                'status' => RenewalProcessStatuses::VALIDATED,
            ])->chunkById(50, function ($leads) use (&$jobs) {
                foreach ($leads as $lead) {
                    $jobs[] = new CreateTravelRenewalQuotesJob($lead);
                }
            });

            if ($jobs != null && count($jobs)) {
                info('the value of $jobs is : '.count($jobs));
                Haystack::build()
                    ->onQueue('renewals')
                    ->addJobs($jobs)
                    ->then(function () use ($logPrefix, $renewalsUploadLead) {
                        info($logPrefix.' all jobs completed successfully');
                        $renewalsUploadLead->update(['status' => ProcessStatusCode::COMPLETED]);
                    })
                    ->catch(function () use ($logPrefix, $renewalsUploadLead) {
                        info($logPrefix.' one of batch is failed. ');
                        $renewalsUploadLead->update(['status' => ProcessStatusCode::FAILED]);
                    })
                    ->finally(function () use ($logPrefix) {
                        info($logPrefix.' everything done');
                    })
                    ->allowFailures()
                    ->withDelay(2)
                    ->dispatch();
            } else {
                info($logPrefix.' No jobs to create quotes');
                $renewalsUploadLead->update(['status' => ProcessStatusCode::COMPLETED]);
            }
        } catch (\Exception $exception) {
            info('BATCH: one of batch is failed. Exception : '.$exception->getMessage());
            $renewalsUploadLead->update(['status' => ProcessStatusCode::FAILED]);
        }
    }

    /**
     * create quote for all businesses.
     *
     * @return void
     */
    public function createTravelProcessQuote(RenewalQuoteProcess $renewalQuoteProcess)
    {
        $data = $renewalQuoteProcess->data;
        $quoteType = $this->getQuoteTypeByShortCode(QuoteTypeShortCode::TRA);
        $logPrefix = 'UAC FN: createQuote Policy NO: '.$data['policy_number'];
        info($logPrefix.' Quote creation started');

        $quote = DB::transaction(function () use ($renewalQuoteProcess, $logPrefix, $data, $quoteType) {
            $searchByName = true;
            $renewalUploadLead = RenewalsUploadLeads::where('id', $renewalQuoteProcess->renewals_upload_lead_id)->first();
            $transApprovedId = $this->getquoteStatusIdbyCode(quoteStatusCode::NEW_LEAD);
            // advisor and previous advisors will be ignored when not exists
            $advisorId = $this->renewalsAddonService->getUserInfo($data['advisor']);
            $quoteUuid = $this->generateUUID($quoteType->code, $quoteType->id);
            $payment_status_id = $this->getPaymentStatusIdByCode($data['payment_status']);
            $customer = $this->getCustomer($data, $searchByName);
            $currently_located_in_id = $this->getCurrentlyLocatedIdByCode($data['currently_located_in']);

            $quoteData = [
                'destination' => trim($data['destination']),
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'source' => TravelQuoteEnum::REVIVAL,
                'customer_id' => $customer->id ?? null,
                'payment_status_id' => $payment_status_id,
                'quote_status_id' => $transApprovedId,
                'code' => trim($data['code']),
                'uuid' => $quoteUuid,
                'policy_number' => trim($data['policy_number']),
                'advisor_id' => $advisorId,
                'premium' => trim($data['premium']),
                'is_ecommerce' => trim($data['is_ecommerce']) == GenericRequestEnum::Yes ? 1 : 0,
                'renewal_batch' => trim($data['renewal_batch']),
                'currently_located_in_id' => $currently_located_in_id,
                'policy_expiry_date' => Carbon::parse(trim($data['policy_expiry_date']))->format('Y-m-d'),
                'email' => trim($data['customer_email']),
                'mobile_no' => trim($data['customer_mobile']),
            ];

            $quote = TravelQuote::create($quoteData);

            // update advisor assign date/time
            if (! empty($advisorId)) {
                $this->updateAdvisorAssignedDateTime($quoteType->code, $quote->id, $renewalUploadLead->created_by_id, $advisorId);
            }

            $renewalQuoteProcess->update(['status' => RenewalProcessStatuses::PROCESSED, 'quote_id' => $quote->id]);

            RenewalsUploadLeads::where('id', $renewalUploadLead->id)->update(['good' => DB::raw('good+1')]);

            info($logPrefix.' Quote created. QuoteType: Travel UUID: '.$quote->uuid);

            return $quote;
        });

        return $quote;
    }

    public function getPaymentStatusIdByCode($paymentStatus)
    {
        return PaymentStatus::where('code', strtolower($paymentStatus))->value('id');
    }

    public function getCurrentlyLocatedIdByCode($currently_located_in)
    {
        return CurrentlyLocatedIn::where(DB::raw('LOWER(code)'), strtolower($currently_located_in))
            ->orWhere(DB::raw('LOWER(text)'), strtolower($currently_located_in))
            ->value('id');
    }

    public function getSearch($data)
    {
        $quotes = [];
        $product = $data->product;
        if ($product == QuoteTypeId::Business) {
            $quotes = BusinessQuoteRepository::getDataOfBusiness()->withQueryString();
        } else {
            $quoteType = QuoteTypes::getName($product);
            $repository = '\\App\\Repositories\\'.ucwords($quoteType->value).'QuoteRepository';
            $quotes = $repository::getData()->withQueryString();
        }

        return $quotes;
    }

    public function getExport($data)
    {
        $quotes = [];
        $product = $data->product;
        $quoteType = QuoteTypes::getName($product);
        $repository = '\\App\\Repositories\\'.ucwords($quoteType->value).'QuoteRepository';
        $quotes = $repository::export();

        return (new RenewalQuotesExport($quotes, $quoteType->name))->download('Renewal');
    }

    private function isFakeEmail($email)
    {
        $fakeEmail = false;

        $fakeDomains = explode(',', ApplicationStorage::where('key_name', ApplicationStorageEnums::FAKE_LEAD_DOMAINS)->first()->value);

        if (in_array(substr($email, strrpos($email, '@') + 1), $fakeDomains)) {
            $fakeEmail = true;
        }

        return $fakeEmail;
    }

}
