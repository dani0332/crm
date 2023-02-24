<?php

namespace App\Services;

use App\Enums\CarPlanAddonsCode;
use App\Enums\CarPlanType;
use App\Enums\carTypeInsuranceCode;
use App\Enums\FetchPlansStatuses;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\quoteStatusCode;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeShortCode;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Imports\UploadAndCreateImport;
use App\Imports\UploadAndUpdateImport;
use App\Jobs\Renewals\CreateRenewalQuotesJob;
use App\Jobs\Renewals\FetchPlansForRenewalsQuoteJob;
use App\Jobs\Renewals\ProcessRenewalsUploadCreate;
use App\Jobs\Renewals\ProcessRenewalsUploadUpdate;
use App\Jobs\Renewals\UpdateRenewalQuotesJob;
use App\Models\AML;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarPlan;
use App\Models\CarQuote;
use App\Models\CarQuoteValuation;
use App\Models\ClaimHistory;
use App\Models\Customer;
use App\Models\Emirate;
use App\Models\InsuranceProvider;
use App\Models\Nationality;
use App\Models\QuoteStatus;
use App\Models\QuoteType;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsBatchEmails;
use App\Models\RenewalStatusProcess;
use App\Models\RenewalsUploadLeads;
use App\Models\UAELicenseHeldFor;
use App\Models\User;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Sammyjo20\LaravelHaystack\Models\Haystack;

class RenewalsUploadService
{
    use GenericQueriesAllLobs;

    protected $renewalsAddonService;
    protected $checkAMLService;
    protected $capiRequestService;
    protected $insuranceProviderService;
    protected $carQuoteService;
    protected $crudService;
    protected $lookupService;
    protected $sendEmailCustomerService;
    protected $userService;

    public function __construct(
        RenewalsAddonServices $renewalsAddonService,
        CheckAmlService $checkAMLService,
        CapiRequestService $capiRequestService,
        InsuranceProviderService $insuranceProviderService,
        CarQuoteService $carQuoteService,
        CRUDService $crudService,
        LookupService $lookupService,
        SendEmailCustomerService $sendEmailCustomerService,
        UserService $userService
    ) {
        $this->renewalsAddonService = $renewalsAddonService;
        $this->checkAMLService = $checkAMLService;
        $this->capiRequestService = $capiRequestService;
        $this->insuranceProviderService = $insuranceProviderService;
        $this->carQuoteService = $carQuoteService;
        $this->crudService = $crudService;
        $this->lookupService = $lookupService;
        $this->sendEmailCustomerService = $sendEmailCustomerService;
        $this->userService = $userService;
    }

    /*
    * @name generateUUID()
    * @returns a 16 character UUIDv4 string
    */
    public function generateUUID($quoteTypeId)
    {
        $response = $this->capiRequestService->getUUID($quoteTypeId);

        if ($response) {
            return $response->uuid;
        }
    }

    /**
     * upload renewal file to azure.
     *
     * @return array
     */
    public function uploadRenewalsFile()
    {
        // Getting original file name
        $fileName = request()->file('file_name')->getClientOriginalName();

        // Generating name for file for azure usage
        $azureFileName = get_guid().'_'.$fileName;

        // Uploading file to Azure
        $azureFilePath = request()->file('file_name')->storeAs('renewals', $azureFileName, 'azureIM');

        return [
            'file_name' => $fileName,
            'azure_file_path' => $azureFilePath,
        ];
    }

    /**
     * @return RenewalsUploadLeads
     */
    public function createRenewalsLead($uploadedFile, $renewalImportType)
    {
        return RenewalsUploadLeads::create([
            'renewal_import_code' => $this->generateRandomString(),
            'file_name' => $uploadedFile['file_name'],
            'file_path' => $uploadedFile['azure_file_path'],
            'status' => ProcessStatusCode::UPLOADED,
            'good' => 0,
            'cannot_upload' => 0,
            'created_by_id' => auth()->user()->id,
            'renewal_import_type' => $renewalImportType,
        ]);
    }

    /**
     * renewals upload and create.
     *
     * @return mixed
     */
    public function renewalsUploadCreate($data)
    {
        //upload renewal file to azure
        $uploadedFile = $this->uploadRenewalsFile();

        //create lead record
        $renewalsUploadLead = $this->createRenewalsLead($uploadedFile, RenewalsUploadType::CREATE_LEADS);
        info('UAT FN: renewalsUploadCreate File uploaded and renewals lead created');

        //start import process
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
                //start file import
                $renewalsUpload = new UploadAndCreateImport($renewalsUploadLead);
                $renewalsUpload->import($renewalsUploadLead->file_path, 'azureIM');

                //update counts
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
        $quotePlans = $this->carQuoteService->getQuotePlans($id, false, true);

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

            RenewalQuoteProcess::where([
                'status' => RenewalProcessStatuses::PROCESSED,
                'quote_type' => QuoteTypeShortCode::CAR,
                'batch' => $batch,
                'type' => RenewalsUploadType::UPDATE_LEADS,
                'fetch_plans_status' => FetchPlansStatuses::PENDING,
            ])->chunkById(50, function ($leads) use ($renewalStatusProcess, &$jobs) {
                foreach ($leads as $lead) {
                    $jobs[] = new FetchPlansForRenewalsQuoteJob($lead, $renewalStatusProcess);
                }
            });

            if ($jobs != null && count($jobs)) {
                info($logPrefix.count($jobs).' found to schedule for fetch plans');

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
                info($logPrefix.' no jobs to create quotes');
                $renewalStatusProcess->update(['status' => ProcessStatusCode::COMPLETED]);
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
            if ($renewalQuoteProcess->quote_type == QuoteTypeShortCode::CAR && (! $aml = AML::where('quote_request_id', $renewalQuoteProcess->quote_id)->where('quote_type_id', $quoteType->id)->first())) {
                info('FetchPlans FN: fetchRenewalPlans'.' AML check started for UUID: '.$quote->uuid);
                $this->checkAMLService->checkAML($quote->first_name, $quote->last_name, $quote->id, $quoteType->id, false, null, null);
                info('FetchPlans FN: fetchRenewalPlans'.' AML check completed for UUID: '.$quote->uuid);
            }

            if (! empty($leadData->provider_name) && ! empty($leadData->plan_name) && ! empty($leadData->plan_type)) {
                info('FetchPlans FN: fetchRenewalPlans'.' create manual plan for ('.$leadData->provider_name.') for UUID: '.$quote->uuid);
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

            info('FetchPlans FN: fetchRenewalPlans'.' fetching plans for quoteType: '.$renewalQuoteProcess->quote_type.' UUID: '.$quote->uuid);
            $plansResponse = $this->getPlans($quote->uuid);
            if ($plansResponse === true) {
                info('FetchPlans FN: fetchRenewalPlans'.' Plans Fetched for quoteType: '.$renewalQuoteProcess->quote_type.' UUID: '.$quote->uuid);
                //update status to plans fetched
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
        //upload renewal file to azure
        $uploadedFile = $this->uploadRenewalsFile();

        //create lead record
        $renewalsUploadLead = $this->createRenewalsLead($uploadedFile, RenewalsUploadType::UPDATE_LEADS);
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
                //start file import
                $renewalsUpload = new UploadAndUpdateImport($this, $renewalsUploadLead);
                $renewalsUpload->import($renewalsUploadLead->file_path, 'azureIM');

                //todo: correct these values
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
        $nameSpace = '\\App\\Models\\';
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
        //default values
        $customerData = [
            'first_name' => $data['customer_name'],
            'last_name' => '',
            'notes' => '',
        ];

        //check if name have last name
        if (strpos($data['customer_name'], ' ')) {
            $nameParts = explode(' ', $data['customer_name'], 2);
            $customerData['first_name'] = $nameParts[0];
            $customerData['last_name'] = $nameParts[1];
        }

        $emails = explode(',', $this->cleanValue($data['email']));
        $customerData['email'] = $emails[0];

        //check in case of additional emails, and add in notes
        if (count($emails) > 1) {
            unset($emails[0]);
            $customerData['additional_emails'] = $emails;
        }

        $mobileNos = explode(',', $this->cleanValue($data['mobile_no']));
        $customerData['mobile_no'] = strtok($mobileNos[0], ',');

        //check in case of additional mobile nos, and add in notes
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
    public function getCustomer($customerData)
    {
        $customer = CustomerService::getCustomerByEmail($customerData['email']);

        //create new customer if not exists
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

    /**
     * update customer detail if required
     * todo: test its working.
     *
     * @return void
     */
    public function updateCustomer($customerData, $customerId)
    {
        $update = false;
        $customer = CustomerService::getCustomerById($customerId);

        //update primary email if changed
        if (! empty($customerData['email']) && $customer->email != $customerData['email'] && ! ($exists = CustomerService::getCustomerByEmail($customerData['email']))) {
            $customer->email = $customerData['email'];
            $update = true;
        }

        //update primary mobile no if changed
        if (! empty($customerData['mobile_no']) && $customer->mobile_no != $customerData['mobile_no'] && ! ($exists = CustomerService::getUniqueCustomerByMobileNo($customerData['mobile_no']))) {
            $customer->mobile_no = $customerData['mobile_no'];
            $update = true;
        }

        if ($update) {
            $customer->save();
        }

        //add additional emails or ignore
        if (isset($customerData['additional_emails']) && count($customerData['additional_emails'])) {
            foreach ($customerData['additional_emails'] as $additionalEmail) {
                $customer->additionalContactInfo()->firstOrCreate(['key' => 'email', 'value' => $additionalEmail]);
            }
        }

        //add additional mobile numbers or ignore
        if (isset($customerData['additional_mobiles']) && count($customerData['additional_mobiles'])) {
            foreach ($customerData['additional_mobiles'] as $additionalMobile) {
                $customer->additionalContactInfo()->firstOrCreate(['key' => 'mobile_no', 'value' => $additionalMobile]);
            }
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
        $logPrefix = 'UAC FN: createQuote Policy NO: '.$data['policy_number'].' EndDate: '.$data['end_date'];
        info($logPrefix.' Quote creation started');

        $quote = DB::transaction(function () use ($renewalQuoteProcess, $logPrefix, $data, $quoteType) {
            $renewalUploadLead = RenewalsUploadLeads::where('id', $renewalQuoteProcess->renewals_upload_lead_id)->first();

            $transApprovedId = $this->getquoteStatusIdbyCode(quoteStatusCode::NEW_LEAD);

            //advisor and previous advisors will be ignored when not exists
            $advisorId = $this->renewalsAddonService->getUserInfo($data['advisor']);
            $previousAdvisorId = $this->renewalsAddonService->getUserInfo($data['previous_advisor']);

            $quoteUuid = $this->generateUUID($quoteType->id);

            $customerData = $this->buildCustomerData($data);
            $customer = $this->getCustomer($customerData);

            $quoteData = [
                'customer_id' => $customer->id,
                'first_name' => $customerData['first_name'],
                'last_name' => $customerData['last_name'],
                'email' => $customerData['email'],
                'mobile_no' => $customerData['mobile_no'],
                'uuid' => $quoteUuid,
                'code' => $renewalQuoteProcess->quote_type.'-'.$quoteUuid,
                'source' => 'Renewal_upload',
                'additional_notes' => $data['notes'].$customerData['notes'],
                'advisor_id' => $advisorId,
                'renewal_batch' => $data['batch'],
                'quote_status_id' => $transApprovedId,
                'renewal_import_code' => $renewalUploadLead->renewal_import_code,
                'previous_quote_policy_number' => $data['policy_number'],
                'previous_policy_expiry_date' => $this->formatDate($data['end_date']),
                'previous_quote_policy_premium' => $data['premium'],
                'previous_advisor_id' => $previousAdvisorId,
            ];

            if ($quoteType->code == quoteTypeCode::Car) {
                $make = CarMake::where('text', $data['make'])->first();
                $model = CarModel::where('text', $data['model'])->first();

                if ($model) {
                    $vehicleType = $this->renewalsAddonService->getVehicleType($model->vehicle_type_id);
                }

                $quoteData['is_quote_locked'] = true;
                $quoteData['car_make_id'] = $make->id ?? null;
                $quoteData['car_model_id'] = $model->id ?? null;
                $quoteData['year_of_manufacture'] = $data['year'];
                $quoteData['year_of_first_registration'] = $data['year'];
                $quoteData['cylinder'] = $model->cylinder ?? null;
                $quoteData['vehicle_category'] = $vehicleType->category ?? null;

                if (! empty($data['product_type']) && ($carTypeOfInsuranceInstance = $this->renewalsAddonService->getCarTypeOfInsurance($data['product_type']))) {
                    $quoteData['car_type_insurance_id'] = $carTypeOfInsuranceInstance->id;
                }
            }

            if (in_array($quoteType->code, [quoteTypeCode::Car, quoteTypeCode::Bike])) {
                $quoteData['currently_insured_with'] = $this->insuranceProviderService->getProviderByCode($data['insurer'])->text;
            }

            //set business type insurance id
            if (! empty($data['product_type'] && $quoteType->code == quoteTypeCode::Business)) {
                if (($businessSubline = $this->renewalsAddonService->getBusinessSublineInsurance($data['product_type']))) {
                    $quoteData['business_type_of_insurance_id'] = $businessSubline->id;
                }
            }

            $quoteObject = $this->createQuoteObject($quoteType->code);
            $quote = $quoteObject->create($quoteData);

            //update advisor assign date/time
            if (! empty($advisorId)) {
                $this->updateAdvisorAssignedDateTime($quoteType->code, $quote->id, $renewalUploadLead->created_by_id, $advisorId);
            }

            $renewalQuoteProcess->update(['status' => RenewalProcessStatuses::PROCESSED, 'quote_id' => $quote->id]);

            RenewalsUploadLeads::where('id', $renewalUploadLead->id)->update(['good' => DB::raw('good+1')]);

            info($logPrefix.' Quote created. QuoteType: '.$data['quote_type'].' UUID: '.$quote->uuid);

            return $quote;
        });

        if ($quote) {
            info($logPrefix.' AML check started for UUID: '.$quote->uuid);
            $this->checkAMLService->checkAML($quote->first_name, $quote->last_name, $quote->id, $quoteType->id, false, null, null);
            info($logPrefix.' AML check completed for UUID: '.$quote->uuid);
        }

        return $quote;
    }

    /**
     * run aml for renewal quote process
     *
     * @return bool
     */
    public function checkAml($renewalQuoteProcess)
    {
        $logPrefix = 'Renewals AML - CL: RenewalsUploadService FN: checkAml. ';

        $quoteType = $this->getQuoteTypeByShortCode($renewalQuoteProcess->quote_type);
        if ($aml = AML::where('quote_request_id', $renewalQuoteProcess->quote_id)->where('quote_type_id', $quoteType->id)->first()) {
            info($logPrefix.' aml already ran for renewalQuoteProcess id: '.$renewalQuoteProcess->id.' quote_id: '.$renewalQuoteProcess->quote_id);

            return true;
        }

        $quoteType = $this->getQuoteTypeByShortCode($renewalQuoteProcess->quote_type);
        $quoteObject = $this->createQuoteObject($quoteType->code);
        if ($quote = $quoteObject->where('id', $renewalQuoteProcess->quote_id)->first()) {
            info($logPrefix.' AML process Started for quote uuid: '.$quote->uuid.' quote_id: '.$renewalQuoteProcess->quote_id);
            $this->checkAMLService->checkAML($quote->first_name, $quote->last_name, $quote->id, $quoteType->id, false, null, null);
            info($logPrefix.' AML process completed for quote uuid: '.$quote->uuid);

            return true;
        }

        info($logPrefix.' quote not found for renewalQuoteProcess id: '.$renewalQuoteProcess->id.' quote_id: '.$renewalQuoteProcess->quote_id);

        return false;
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
                ->where('previous_policy_expiry_date', $this->formatDate($data['end_date']))->first();

            throw_unless($quote, ('Quote not found for PolicyNumber: '.$data['policy_number'].' EndDate: '.$data['end_date'].' Batch: '.$renewalQuoteProcess->batch));

            $carMake = $this->renewalsAddonService->getCarMake($data['make']);
            $carModel = $this->renewalsAddonService->getCarModel($data['model']);
            $advisorId = $this->renewalsAddonService->getUserInfo($data['advisor']);
            $previousAdvisorId = $this->renewalsAddonService->getUserInfo($data['previous_advisor']);
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

            //check if name is changed , then run AML again
            if (($quote->first_name != $customerData['first_name'] || $quote->last_name != $customerData['last_name'])) {
                $isNameChanged = true;
            }

            $this->updateCustomer($customerData, $quote->customer_id);

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
                'previous_policy_expiry_date' => (! empty($data['end_date'])) ? $this->formatDate($data['end_date']) : null,
                'advisor_id' => $advisorId,
                'renewal_batch' => $data['batch'],
                'additional_notes' => $data['notes'],
                'car_make_id' => $carMake->id ?? null,
                'car_model_id' => $carModel->id ?? null,
                'cylinder' => $carModel->cylinder ?? null,
                'vehicle_category' => $vehicleType->category ?? null,
                'year_of_manufacture' => $data['year'] ?? null,
                'previous_advisor_id' => $previousAdvisorId,
                'quote_updated_at' => Carbon::now(),
                'has_ncd_supporting_documents' => $data['nc_letter'],
            ]);

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

            info($logPrefix.' quote updated UUID: '.$quote->uuid);

            if (! empty($advisorId)) {
                $this->updateAdvisorAssignedDateTime($quoteType->code, $quote->id, $renewalUploadLead->created_by_id, $advisorId);
                info($logPrefix.' quote advisor assigned datetime updated UUID: '.$quote->uuid);
            }

            //mark all other fetch plans pending records as outdated, it will help to target unique records during fetch plans process
            RenewalQuoteProcess::where([
                'quote_id' => $quote->id,
                'status' => RenewalProcessStatuses::PROCESSED,
                'type' => RenewalsUploadType::UPDATE_LEADS,
                'fetch_plans_status' => FetchPlansStatuses::PENDING,
            ])->update(['fetch_plans_status' => FetchPlansStatuses::OUTDATED]);

            //mark renewal quote process as processed and assign quote id
            $renewalQuoteProcess->update([
                'status' => RenewalProcessStatuses::PROCESSED,
                'quote_id' => $quote->id,
                'fetch_plans_status' => FetchPlansStatuses::PENDING,
            ]);

            RenewalsUploadLeads::where('id', $renewalUploadLead->id)->update(['good' => DB::raw('good+1')]);
            info($logPrefix.' quoted updated completed for UUID: '.$quote->uuid);

            return $quote;
        });

        if ($quote && $isNameChanged) {
            info($logPrefix.' AML check started for UUID: '.$quote->uuid);
            $this->checkAMLService->checkAML($quote->first_name, $quote->last_name, $quote->id, $quoteType->id, false, null, null);
            info($logPrefix.' AML check completed for UUID: '.$quote->uuid);
        }

        return $quote;
    }

    /**
     * todo: add conditions if before updating plan info.
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
            $q->whereIn('code', [CarPlanAddonsCode::DRIVER_COVER, CarPlanAddonsCode::PASSENGER_COVER,
                CarPlanAddonsCode::CAR_HIRE, CarPlanAddonsCode::OMAN_COVER, CarPlanAddonsCode::BREAKDOWN_COVER,
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

        //excess will be used for comp or agency repair type
        if ($data['plan_type'] == CarPlanType::COMP || $data['plan_type'] == CarPlanType::AGENCY) {
            $plan['excess'] = $data['excess'];
        }

        //trim is optional
        if (! empty($data['trim'])) {
            if (($valuation = CarQuoteValuation::where('quote_request_id', $quote->id)->where('provider_id', $provider->id)->first())) {
                if (! empty($valuation->insurer_available_trims)) {
                    $trims = collect($valuation->insurer_available_trims)->keyBy('description')->toArray();
                    if (! empty($trims[$data['trim']]['admeId'])) {
                        $plan['insurerTrimId'] = $trims[$data['trim']]['admeId'];
                    }
                }
            }
        }

        info($logPrefix.' car plan detail with addons fetched');

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

        info($logPrefix.' setup create plan data is completed.');

        //todo: temporary logging, remove later
        info($logPrefix.' PlanData: '.json_encode($planData));

        //todo: what to do when it fails
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

    public function renewalBatchEmailProcess($batchLeadId, $batchEmailId, $quoteTypeId, $isCompleted, $batch)
    {
        Log::info('renewalBatchEmailProcess START');
        $carQuote = CarQuote::find($batchLeadId);

        if ($carQuote->previous_quote_policy_number != null) {
            // CHECK NUMBER OF PLAN AND SEND RESPECTIVE 'ONE CLICK BUY' EMAIL TO CUSTOMER
            $listQuotePlans = $this->carQuoteService->getPlans($carQuote->uuid, true, true);
            $quotePlansCount = is_countable($listQuotePlans) ? count($listQuotePlans) : 0;
            $emailTemplateId = (int) $this->crudService->getOcbCustomerEmailTemplate($quotePlansCount);

            if (isset($carQuote->advisor_id)) {
                $advisor = $this->userService->getUserById($carQuote->advisor_id);
                $advisorName = $advisor->name;
                $advisorEmail = $advisor->email;
                $advisorMobile = $advisor->mobile_no;
                $advisorLandline = $advisor->landline_no;
            }

            // Send Email Data
            $carMake = $this->lookupService->getCarMake($carQuote->car_make_id);
            $carModel = $this->lookupService->getCarModel($carQuote->car_model_id);
            $emailData = (object) [
                'quoteTypeId' => $quoteTypeId,
                'quoteId' => $carQuote->id,
                'templateId' => $emailTemplateId,
                'quoteCdbId' => $carQuote->code,
                'customerName' => $carQuote->first_name.' '.$carQuote->last_name,
                'customerEmail' => $carQuote->email,
                'previousPolicyExpiryDate' => $carQuote->previous_policy_expiry_date,
                'currentlyInsuredWith' => $carQuote->currently_insured_with,
                'carMake' => isset($carMake->text) ? $carMake->text : null,
                'carModel' => isset($carModel->text) ? $carModel->text : null,
                'carManufactureYear' => $carQuote->year_of_manufacture,
                'previousPolicyNumber' => $carQuote->previous_quote_policy_number,
                'advisorName' => isset($advisorName) ? $advisorName : null,
                'advisorEmailAddress' => isset($advisorEmail) ? $advisorEmail : null,
                'advisorMobileNo' => isset($advisorMobile) ? $advisorMobile : null,
                'advisorLandlineNo' => isset($advisorLandline) ? $advisorLandline : null,
                'buttonUrl' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$carQuote->uuid,
                'listQuotePlans' => $listQuotePlans,
                'multipleQuoteUrl' => config('constants.AFIA_WEBSITE_DOMAIN').'/car-insurance/quote/'.$carQuote->uuid.'/'.'payment/?providerCode=',
                'quotePlansCount' => isset($quotePlansCount) ? $quotePlansCount : 0,
            ];

            $responseCode = $this->sendEmailCustomerService->sendOcbEmail($emailTemplateId, $emailData, 'car-quote-one-click-buy-batch');

            if ($responseCode == 201) {
                Log::info('renewalBatchEmailProcess EmailSent: '.$responseCode);
                $this->updateRenewalQuoteEmailSent($batch, $carQuote->id);
            } else {
                Log::error('renewalBatchEmailProcess EmailNotSent: '.$responseCode.' batchEmailId:'.$batchEmailId.' Customer EmailAddress:'.$carQuote->email);
            }
        }

        $this->updateRenewalEmailBatchStatus($batchEmailId, $isCompleted);
        Log::info('renewalBatchEmailProcess END');
    }

    public function updateRenewalEmailBatchStatus($batchEmailId, $isCompleted)
    {
        Log::info('updateRenewalEmailBatchStatus START');
        $renewalsBatchStatus = RenewalsBatchEmails::find($batchEmailId);
        if ($renewalsBatchStatus) { // if record exists, update the number of rows uploaded
            $renewalsBatchStatus->total_sent = $renewalsBatchStatus->total_sent + 1;
            $renewalsBatchStatus->save();
        }
        if (($renewalsBatchStatus->total_sent + $renewalsBatchStatus->total_bounced) == $renewalsBatchStatus->total_leads || $isCompleted == 1) { // if all records are uploaded, update the status to completed
            $renewalsBatchStatus->status = ProcessStatusCode::COMPLETED;
            $renewalsBatchStatus->save();
        }
        Log::info('updateRenewalEmailBatchStatus END');
    }

    /**
     * //$modelName, $quoteRequestIdName.
     *
     * @param $quoteRequestIdName
     * @return false|mixed
     */
    public function updateAdvisorAssignedDateTime($quoteType, $quoteId, $currentUserId, $advisorId)
    {
        $quoteRequestDetail = '\\App\\Models\\'.ucfirst($quoteType).'QuoteRequestDetail';

        $quoteRequestField = strtolower($quoteType).'_quote_request_id';

        // check if record exists in model_detail table
        if (($quoteDetail = $quoteRequestDetail::where($quoteRequestField, $quoteId)->first())) {
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
        RenewalQuoteProcess::where('status', RenewalProcessStatuses::NEW)->where('renewals_upload_lead_id', $renewalsUploadLead->id)->chunkById(50, function ($leads) {
            foreach ($leads as $lead) {
                $leadValidationErrors = collect();

                if ($lead->type == RenewalsUploadType::CREATE_LEADS) {
                    if (! QuoteType::where('short_code', $lead->quote_type)->first()) {
                        $leadValidationErrors->push('Invalid Insurance Type Provided');
                    }
                }

                $quoteType = $this->getQuoteTypeByShortCode($lead->quote_type);
                $quoteTypeObject = $this->createQuoteObject($quoteType->code);

                $leadData = (object) $lead->data;
                info('CQF VALIDATION - Checking Quote Existence PolicyNo - '.$lead->policy_number.' Quote Type - '.json_encode($quoteTypeObject));
                if ($lead->type == RenewalsUploadType::UPDATE_LEADS && ! $lead->policy_number) {
                    $leadValidationErrors->push('Policy Number is mandatory for update process');
                } elseif ($lead->type == RenewalsUploadType::UPDATE_LEADS && $lead->policy_number && $quoteTypeObject) {
                    info('CQF VALIDATION - Checking Quote Existence 1 - '.$lead->policy_number);
                    if (! $quoteTypeObject->where('previous_quote_policy_number', $lead->policy_number)->where('previous_policy_expiry_date', $this->formatDate($leadData->end_date))->first()) {
                        $leadValidationErrors->push('Quote does not exist for this policy number, use upload and create');
                    } else {
                        info('CQF VALIDATION - Quote Found for Update - '.$lead->policy_number);
                    }
                }
                if (! $leadData->insurer) {
                    $leadValidationErrors->push('Insurance Provider is required');
                } elseif (! InsuranceProvider::where('code', $leadData->insurer)->first()) {
                    $leadValidationErrors->push('Invalid Insurance Code Provided');
                }
                if ($lead->type == RenewalsUploadType::UPDATE_LEADS && ! $leadData->product_type) {
                    $leadValidationErrors->push('Product Type is Required');
                }
                if ($leadData->advisor && ! User::where('email', $leadData->advisor)->first()) {
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
                    if ($quoteTypeObject->where('previous_quote_policy_number', $lead->policy_number)->where('previous_policy_expiry_date', $this->formatDate($leadData->end_date))->first()) {
                        $leadValidationErrors->push('Quote already created for this policy number, use upload and update');
                    }
                }

                switch($lead->quote_type) {
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
                            if (! $leadData->driving_experience) {
                                $leadValidationErrors->push('Driving Experience is required');
                            } elseif (! UAELicenseHeldFor::where('text', $leadData->driving_experience)->first()) {
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
                                    if (! $leadData->passenger_cover) {
                                        $leadValidationErrors->push('PAB Passenger is required with Renewal Premium & Excess');
                                    }
                                    if ($leadData->plan_type != CarPlanType::TPL && $leadData->insurer != InsuranceProvidersEnum::TM && ! $leadData->car_hire) {
                                        $leadValidationErrors->push('Rent a car is required with TPL & TM');
                                    }
                                    if ($leadData->plan_type != CarPlanType::TPL && $leadData->insurer != 'TM' && $leadData->car_hire_amount == '') {
                                        $leadValidationErrors->push('Amount - Rent a Car is required with TPL & TM');
                                    }
                                    if ($leadData->driver_cover_amount == '') {
                                        $leadValidationErrors->push('Amount - PAB Driver is required with Renewal Premium & Excess');
                                    }
                                    if ($leadData->passenger_cover_amount == '') {
                                        $leadValidationErrors->push('Amount- PAB Passenger is required with Renewal Premium & Excess');
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
                                            $q->whereIn('code', [CarPlanAddonsCode::DRIVER_COVER, CarPlanAddonsCode::PASSENGER_COVER,
                                                CarPlanAddonsCode::CAR_HIRE, CarPlanAddonsCode::OMAN_COVER, CarPlanAddonsCode::BREAKDOWN_COVER,
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
                            if (! $leadData->registration_location) {
                                $leadValidationErrors->push('Registration Location is required');
                            } elseif (! Emirate::where('text', $leadData->registration_location)->first()) {
                                $leadValidationErrors->push('Invalid Registration Location');
                            }
                            if ($leadData->previous_advisor && ! User::where('email', $leadData->previous_advisor)->first()) {
                                $leadValidationErrors->push('Invalid Previous Advisor Email');
                            }
                        }
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
            'type' => RenewalsUploadType::UPDATE_LEADS, ])->distinct('quote_id')->count();
    }

    public function getProcessTotalLeadsWithPlans($batch)
    {
        return RenewalQuoteProcess::where([
            'quote_type' => QuoteTypeShortCode::CAR,
            'batch' => $batch,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'status' => RenewalProcessStatuses::PLANS_FETCHED,
            'fetch_plans_status' => FetchPlansStatuses::FETCHED, ])->distinct('quote_id')->count();
    }

    public function getProcessLeads($batch)
    {
        return RenewalQuoteProcess::select('quote_id as id')->where([
            'quote_type' => QuoteTypeShortCode::CAR,
            'batch' => $batch,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'status' => RenewalProcessStatuses::PLANS_FETCHED,
            'fetch_plans_status' => FetchPlansStatuses::FETCHED, ])->distinct('quote_id')->get();
    }

    public function getProcessLeadsToSendEmails($batch)
    {
        return RenewalQuoteProcess::select('id', 'quote_id')->where([
            'quote_type' => QuoteTypeShortCode::CAR,
            'batch' => $batch,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'status' => RenewalProcessStatuses::PLANS_FETCHED,
            'email_sent' => 0,
            'fetch_plans_status' => FetchPlansStatuses::FETCHED, ])->distinct('quote_id')->get();
    }

    public function updateRenewalQuoteEmailSent($batch, $quoteId)
    {
        Log::info('updateRenewalQuoteEmailSent START');
        Log::info('updateRenewalQuoteEmailSent batch: '.$batch);
        Log::info('updateRenewalQuoteEmailSent quoteId: '.$quoteId);
        $emailSent = RenewalQuoteProcess::where([
            'quote_type' => QuoteTypeShortCode::CAR,
            'batch' => $batch,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'status' => RenewalProcessStatuses::PLANS_FETCHED,
            'email_sent' => 0,
            'fetch_plans_status' => FetchPlansStatuses::FETCHED,
            'quote_id' => $quoteId, ])->first();
        $emailSent->email_sent = 1;
        $emailSent->save();
        Log::info('updateRenewalQuoteEmailSent emailSent->id: '.$emailSent->id);
        Log::info('updateRenewalQuoteEmailSent END');
    }
}
