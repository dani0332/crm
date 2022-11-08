<?php

namespace App\Services;

use App\Enums\carTypeInsuranceCode;
use App\Enums\ProcessStatusCode;
use App\Enums\quoteStatusCode;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeShortCode;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Imports\UploadAndCreateImport;
use App\Imports\UploadAndUpdateImport;
use App\Jobs\ProcessRenewalsUploadCreate;
use App\Jobs\ProcessRenewalsUploadUpdate;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarPlan;
use App\Models\CarQuote;
use App\Models\CarQuoteValuation;
use App\Models\CarTypeInsurance;
use App\Models\ClaimHistory;
use App\Models\Customer;
use App\Models\EmailActivity;
use App\Models\EmailStatus;
use App\Models\Emirate;
use App\Models\InsuranceProvider;
use App\Models\Nationality;
use App\Models\QuoteStatus;
use App\Models\QuoteType;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsBatchEmails;
use App\Models\RenewalsUploadLeads;
use App\Models\UAELicenseHeldFor;
use App\Models\User;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Config;
use DateTime;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RenewalsUploadService
{
    use GenericQueriesAllLobs;

    protected $renewalsAddonService;
    protected $checkAMLService;
    protected $capiRequestService;
    protected $insuranceProviderService;
    protected $carQuoteService;

    public function __construct(
        RenewalsAddonServices $renewalsAddonService,
        CheckAmlService $checkAMLService,
        CapiRequestService $capiRequestService,
        InsuranceProviderService $insuranceProviderService,
        CarQuoteService $carQuoteService
    ) {
        $this->renewalsAddonService = $renewalsAddonService;
        $this->checkAMLService = $checkAMLService;
        $this->capiRequestService = $capiRequestService;
        $this->insuranceProviderService = $insuranceProviderService;
        $this->carQuoteService = $carQuoteService;
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
     * @param $uploadedFile
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
            'created_by_id' => auth()->user()->id,
            'renewal_import_type' => $renewalImportType,
        ]);
    }

    /**
     * renewals upload and create.
     *
     * @param $data
     * @return mixed
     */
    public function renewalsUploadCreate($data)
    {
        //upload renewal file to azure
        $uploadedFile = $this->uploadRenewalsFile();

        //create lead record
        $renewalsUploadLead = $this->createRenewalsLead($uploadedFile, RenewalsUploadType::CREATE_LEADS);

        //start import process
        ProcessRenewalsUploadCreate::dispatch($renewalsUploadLead);

        return true;
    }

    /**
     * this will be triggered by job to start import process for upload and create.
     *
     * @param  RenewalsUploadLeads  $renewalsUploadLead
     * @return void
     */
    public function processUploadCreate(RenewalsUploadLeads $renewalsUploadLead)
    {
        $renewalsUploadLead = DB::transaction(function () use ($renewalsUploadLead) {
            $renewalsUploadLead->update(['status' => ProcessStatusCode::IN_PROGRESS]);

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

        $this->uploadedLeadsValidation();
        $this->fetchLeadPlans();

        $renewalsUploadLead->update(['status' => ProcessStatusCode::COMPLETED]);

        return true;
    }

    /**
     * @return void
     */
    public function fetchLeadPlans()
    {
        RenewalQuoteProcess::where('status', RenewalProcessStatuses::PROCESSED)->where('quote_type', QuoteTypeShortCode::CAR)->chunk(50, function ($leads) {
            foreach ($leads as $lead) {
                $leadData = (object) $lead->data;

                $quoteType = $this->getQuoteTypeByShortCode($lead->quote_type);
                $quoteObject = $this->createQuoteObject($quoteType->code);

                if ($quoteObject && ($quote = $quoteObject->where([
                    'previous_quote_policy_number' => $lead->policy_number,
                    'previous_policy_expiry_date' => $this->formatDate($leadData->end_date),
                ])->first())) {
                    $plans = $this->carQuoteService->getPlans($quote->uuid);
                    if (isset($plans[0]->id)) {
                        //update status to plans fetched
                        $lead->update(['status' => RenewalProcessStatuses::PLANS_FETCHED]);
                    }
                }
            }
        });
    }

    /**
     * @param $data
     * @return bool
     */
    public function renewalsUploadUpdate($data)
    {
        //upload renewal file to azure
        $uploadedFile = $this->uploadRenewalsFile();

        //create lead record
        $renewalsUploadLead = $this->createRenewalsLead($uploadedFile, RenewalsUploadType::UPDATE_LEADS);

        ProcessRenewalsUploadUpdate::dispatch($renewalsUploadLead);

        return true;
    }

    /**
     * @param  RenewalsUploadLeads  $renewalsUploadLead
     * @return bool
     */
    public function processUploadUpdate(RenewalsUploadLeads $renewalsUploadLead)
    {
        $renewalsUploadLead = DB::transaction(function () use ($renewalsUploadLead) {
            $renewalsUploadLead->update(['status' => ProcessStatusCode::IN_PROGRESS]);

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

        $this->uploadedLeadsValidation();

        $renewalsUploadLead->update(['status' => ProcessStatusCode::COMPLETED]);

        return true;
    }

    /**
     * create quote object.
     *
     * @param $quoteType
     * @return false|mixed
     */
    public function createQuoteObject($quoteType)
    {
        $nameSpace = '\\App\\Models\\';
        $model = $nameSpace.ucwords($quoteType).'Quote';

        return (class_exists($model)) ? $model::query() : false;
    }

    /**
     * get quote request detail class.
     *
     * @param $quoteType
     * @return string
     */
    public function getQuoteRequestDetailClass($quoteType)
    {
        return ucwords($quoteType).'QuoteRequestDetail';
    }

    /**
     * clean input.
     *
     * @param $value
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
     * @param $data
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
            $nameParts = explode(' ', $data['customer_name']);
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
     * @param $customerData
     * @return mixed
     */
    public function getCustomer($customerData)
    {
        $customer = CustomerService::getUniqueCustomerByEmail($customerData['email']);

        //create new customer if not exists
        if (! isset($customer->id)) {
            $customer = Customer::create(Arr::only($customerData, ['first_name', 'last_name', 'email', 'mobile_no']));

            // create additional emails
            if (count($customerData['additional_emails'])) {
                foreach ($customerData['additional_emails'] as $additionalEmail) {
                    $customer->additionalContactInfo()->create(['key' => 'email', 'value' => $additionalEmail]);
                }
            }

            // create additional mobile nos
            if (count($customerData['additional_mobiles'])) {
                foreach ($customerData['additional_mobiles'] as $additionalMobile) {
                    $customer->additionalContactInfo()->create(['key' => 'mobile_no', 'value' => $additionalMobile]);
                }
            }
        }

        return $customer;
    }

    /**
     * update customer detail if required
     * todo: test its working
     *
     * @param $customerData
     * @return void
     */
    public function udpateCustomer($customerData, $customerId)
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
     * @param $shortCode
     * @return mixed
     */
    public function getQuoteTypeByShortCode($shortCode)
    {
        return QuoteType::where('short_code', $shortCode)->first();
    }

    /**
     * @param $claimHistory
     * @return mixed
     */
    public function getClaimHistory($claimHistory)
    {
        return ClaimHistory::where('text', $claimHistory)->first();
    }

    /**
     * create quote for all businesses.
     *
     * @param  RenewalQuoteProcess  $renewalQuoteProcess
     * @return void
     */
    public function createQuote(RenewalQuoteProcess $renewalQuoteProcess)
    {
        return DB::transaction(function () use ($renewalQuoteProcess) {
            $data = $renewalQuoteProcess->data;

            $renewalUploadLead = RenewalsUploadLeads::where('id', $renewalQuoteProcess->renewals_upload_lead_id)->first();

            $transApprovedId = $this->getquoteStatusIdbyCode(quoteStatusCode::NEW_LEAD);

            $quoteType = $this->getQuoteTypeByShortCode($data['quote_type']);

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
                $vehicleType = $this->renewalsAddonService->getVehicleType($model->vehicle_type_id);

                $quoteData['car_make_id'] = $make->id;
                $quoteData['car_model_id'] = $model->id;
                $quoteData['year_of_manufacture'] = $data['year'];
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

            //send AML request
            $this->checkAMLService->checkAML($quote->first_name, $quote->last_name, $quote->id, $quoteType->id, false, null, null);

            //update advisor assign date/time
            if (! empty($advisorId)) {
                $this->updateAdvisorAssignedDateTime($quoteType->code, $quote->id, $renewalUploadLead->created_by_id, $advisorId);
            }

            $renewalQuoteProcess->update(['status' => RenewalProcessStatuses::PROCESSED]);
            $renewalUploadLead->good += 1;
            $renewalUploadLead->save();

            return $quote;
        });
    }

    /**
     * ignore fields having empty/null.
     *
     * @param $values
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
     * @param $date
     * @return string
     */
    public function formatDate($date)
    {
        return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
    }

    /**
     * @param  RenewalQuoteProcess  $renewalQuoteProcess
     * @return mixed
     */
    public function updateQuote(RenewalQuoteProcess $renewalQuoteProcess)
    {
        return DB::transaction(function () use ($renewalQuoteProcess) {
            $data = $renewalQuoteProcess->data;
            $renewalUploadLead = RenewalsUploadLeads::where('id', $renewalQuoteProcess->renewals_upload_lead_id)->first();

            $quoteType = $this->getQuoteTypeByShortCode($data['quote_type']);
            $carMake = $this->renewalsAddonService->getCarMake($data['make']);
            $carModel = $this->renewalsAddonService->getCarModel($data['model']);
            $advisorId = $this->renewalsAddonService->getUserInfo($data['advisor']);
            $previousAdvisorId = $this->renewalsAddonService->getUserInfo($data['previous_advisor']);
            $claimHistory = $this->getClaimHistory($data['claim_history']);
            $nationality = Nationality::where('text', $data['nationality'])->first();
            $emirate = Emirate::where('text', $data['registration_location'])->first();
            $uaeLicenseHeldFor = UAELicenseHeldFor::where('text', $data['driving_experience'])->first();

            if ($carModel) {
                $vehicleType = $this->renewalsAddonService->getVehicleType($carModel->vehicle_type_id);
            }

            if ($data['product_type'] != null) {
                $carTypeOfInsurance = $this->renewalsAddonService->getCarTypeOfInsurance($data['product_type']);
            }

            // Previous Car Lead
            $quoteObject = $this->createQuoteObject(ucfirst($quoteType->code));
            $quote = $quoteObject->where('previous_quote_policy_number', $data['policy_number'])->first();

            $customerData = $this->buildCustomerData($data);
            $this->udpateCustomer($customerData, $quote->customer_id);

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
                'uae_license_held_for_id' => $uaeLicenseHeldFor->id,
                'car_value' => $data['car_value'],
                'previous_policy_expiry_date' => (! empty($data['end_date'])) ? $this->formatDate($data['end_date']) : null,
                'previous_quote_policy_premium' => $data['premium'],
                'advisor_id' => $advisorId,
                'renewal_batch' => $data['batch'],
                'additional_notes' => $data['notes'],
                'car_make_id' => $carMake->id ?? null,
                'car_model_id' => $carModel->id ?? null,
                'cylinder' => $carModel->cylinder ?? null,
                'vehicle_category' => $vehicleType->category ?? null,
                'year_of_manufacture' => $data['year'] ?? null,
                'previous_advisor_id' => $previousAdvisorId,
            ]);

            if (in_array($quoteType->code, [quoteTypeCode::Car, quoteTypeCode::Bike])) {
                $quoteData['currently_insured_with'] = $this->insuranceProviderService->getProviderByCode($data['insurer'])->text;
            }

            $quote->update($quoteData);

            if (! empty($advisorId)) {
                $this->updateAdvisorAssignedDateTime($quoteType->code, $quote->id, $renewalUploadLead->created_by_id, $advisorId);
            }

            //todo: check if this fails
            $response = $this->modifyPlan($data, $quote);

            $renewalQuoteProcess->update(['status' => RenewalProcessStatuses::PROCESSED]);

            return $quote;
        });
    }

    /**
     * todo: add conditions if before updating plan info.
     *
     * @param $data
     * @param $quote
     * @return void
     */
    public function modifyPlan($data, $quote)
    {
        $provider = InsuranceProvider::where('text', $data['provider_name'])->first();

        $carPlan = CarPlan::where([
            'text' => $data['plan_name'],
            'repair_type' => $data['plan_type'],
            'provider_id' => $provider->id,
        ])->first();

        $planData = Arr::only($data, ['premium', 'car_value', 'excess']);
        $planData['plan_id'] = $carPlan->id;
        $planData['quote_uuid'] = $quote->uuid;
        $planData['created_by'] = $quote->created_by;

        //trim is optional
        if (! empty($data['trim'])) {
            $valuation = CarQuoteValuation::where('quote_request_id', $quote->id)->where('provider_id', $provider->id)->first();
            $trims = collect($valuation->insurer_available_trims)->keyBy('description')->toArray();
            if (! empty($trims[$data['trim']]['admeId'])) {
                $planData['trim_id'] = $trims[$data['trim']]['admeId'];
            }
        }

        //todo: what to do when it fails
        return $this->carQuoteService->renewalModifyPlan($planData);
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

    public function updateExistingCarQuote($quoteData, $renewalImportCode, $currentUserId)
    {
        $carTypeOfInsurance = null;
        $carMake = $this->renewalsAddonService->getCarMake($quoteData->make);
        $carModel = $this->renewalsAddonService->getCarModel($quoteData->model);
        $vehicleType = null;
        $previousAdvisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->advisor);

        if ($carModel) {
            $vehicleType = $this->renewalsAddonService->getVehicleType($carModel->vehicle_type_id);
        }

        if ($quoteData->product_type != null) {
            $carTypeOfInsuranceInstance = $this->renewalsAddonService->getCarTypeOfInsurance($quoteData->product_type);
            if ($carTypeOfInsuranceInstance) {
                $carTypeOfInsurance = $carTypeOfInsuranceInstance->id;
            }
        }

        // Previous Car Lead
        $updateCarQuote = CarQuote::where('renewal_import_code', $renewalImportCode)
            ->where('policy_number', $quoteData->policy)->first();

        $previousAdvisorEmail = 'Previous Advisor Email Id : '.$quoteData->pAdvisor;
        $carMakeModel = 'Car Make/Model/Year : '.$quoteData->make.' '.$quoteData->year;
        $notes = $previousAdvisorId == '' ? $updateCarQuote->additional_notes.' - '.$carMakeModel.' - '.$previousAdvisorEmail.' - '.$quoteData->notes : $updateCarQuote->additional_notes.' - '.$carMakeModel.' - '.$quoteData->notes;

        $updateCarQuote->car_type_insurance_id = $carTypeOfInsurance;
        $updateCarQuote->advisor_id = $previousAdvisorId;
        $updateCarQuote->renewal_batch = $quoteData->batch;
        $updateCarQuote->additional_notes = $notes;
        $updateCarQuote->car_make_id = $carMake->id ?? null;
        $updateCarQuote->car_model_id = $carModel->id ?? null;
        $updateCarQuote->cylinder = $carModel->cylinder ?? null;
        $updateCarQuote->vehicle_category = $vehicleType->category ?? null;
        $updateCarQuote->year_of_manufacture = $quoteData->year ?? null;
        $updateCarQuote->save();
        $this->updateAdvisorAssignedDateTime('CarQuoteRequestDetail', $updateCarQuote->id, 'car_quote_request_id', $currentUserId, $previousAdvisorId);

        // Renewal Car Lead
        $updateCarQuoteRenewal = CarQuote::where('renewal_import_code', $renewalImportCode)
            ->where('previous_quote_policy_number', $quoteData->policy)->first();

        $updateCarQuoteRenewal->car_type_insurance_id = $carTypeOfInsurance;
        $updateCarQuoteRenewal->advisor_id = $advisorId;
        $updateCarQuoteRenewal->renewal_batch = $quoteData->batch;
        $updateCarQuoteRenewal->additional_notes = $notes;
        $updateCarQuoteRenewal->car_make_id = $carMake->id ?? null;
        $updateCarQuoteRenewal->car_model_id = $carModel->id ?? null;
        $updateCarQuoteRenewal->cylinder = $carModel->cylinder ?? null;
        $updateCarQuoteRenewal->vehicle_category = $vehicleType->category ?? null;
        $updateCarQuoteRenewal->year_of_manufacture = $quoteData->year ?? null;
        $updateCarQuoteRenewal->save();
        $this->updateAdvisorAssignedDateTime('CarQuoteRequestDetail', $updateCarQuoteRenewal->id, 'car_quote_request_id', $currentUserId, $advisorId);
    }

    public function renewalBatchEmailProcess($batchLeadId, $batchEmailId)
    {
        $carQuote = CarQuote::find($batchLeadId);
        $ecomUrl = Config::get('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$carQuote->uuid;

        if ($carQuote->previous_quote_id != null) {
            $primaryEmail = $carQuote->email;
            $otherEmails = $carQuote->other_email_addresses;

            if ($otherEmails) {
                $allEmails = $primaryEmail.','.$otherEmails;
            } else {
                $allEmails = $primaryEmail;
            }

            $finalEmails = explode(',', $allEmails);

            foreach ($finalEmails as $finalEmail) {
                $previousCarQuote = CarQuote::find($carQuote->previous_quote_id);
                Log::info('CDBID: '.$carQuote->code.' RenewalBatch: '.$carQuote->renewal_batch.' PreviousQuoteId: '.$carQuote->previous_quote_id.' Email: '.$carQuote->email.' finalEmail: '.$finalEmail.' ecomUrl: '.$ecomUrl.' renewal_expiry_date: '.$previousCarQuote->renewal_expiry_date);

                if (isset($previousCarQuote->renewal_expiry_date)) {
                    $renewalExpiryDate = date('d/m/Y', strtotime($previousCarQuote->renewal_expiry_date));
                } else {
                    $renewalExpiryDate = '';
                }

                if (isset($carQuote->car_type_insurance_id)) {
                    $carTypeInsurance = CarTypeInsurance::where('id', '=', $carQuote->car_type_insurance_id)->value('text');
                } else {
                    $carTypeInsurance = '';
                }

                if (isset($carQuote->car_make_id)) {
                    $carMake = CarMake::where('id', '=', $carQuote->car_make_id)->value('text');
                } else {
                    $carMake = '';
                }

                if (isset($carQuote->car_model_id)) {
                    $carModel = CarModel::where('id', '=', $carQuote->car_model_id)->value('text');
                } else {
                    $carModel = '';
                }

                if (isset($carQuote->advisor_id)) {
                    $advisorModel = User::where('id', '=', $carQuote->advisor_id)->first();
                    $advisorName = $advisorModel->name;
                    $advisorEmail = $advisorModel->email;
                    $advisorMobile = $advisorModel->mobile_no;
                    $advisorLandline = $advisorModel->landline_no;
                } else {
                    $advisorName = '';
                    $advisorEmail = '';
                    $advisorMobile = '';
                    $advisorLandline = '';
                }

                // Send Email
                $emailData = [
                    'customerName' => $carQuote->first_name.' '.$carQuote->last_name,
                    'customerEmail' => $finalEmail,
                    'cdbId' => $carQuote->code,
                    'policyNumber' => $carQuote->previous_quote_policy_number,
                    'expiryDate' => $renewalExpiryDate,
                    'insurerName' => $carQuote->currently_insured_with,
                    'planType' => $carTypeInsurance,
                    'carMake' => $carMake,
                    'carModel' => $carModel,
                    'advisorName' => $advisorName,
                    'advisorEmail' => $advisorEmail,
                    'advisorMobile' => $advisorMobile,
                    'advisorLandline' => $advisorLandline,
                    'ecomUrl' => $ecomUrl,
                ];

                $getmessageId = $this->sendRenewalEmail($emailData);

                $newEmailStatus = new EmailStatus();
                $newEmailStatus->quote_type_id = 1;
                $newEmailStatus->quote_id = $carQuote->id;
                $newEmailStatus->email_address = $finalEmail;
                $newEmailStatus->msg_id = $getmessageId;
                $newEmailStatus->email_status = ProcessStatusCode::IN_PROGRESS;
                $newEmailStatus->save();
            }
        }

        $this->updateRenewalBatchRecord($batchEmailId);
    }

    public function sendRenewalEmail($emailData)
    {
        try {
            $apiKey = Config::get('constants.SENDINBLUE_KEY');
            $url = Config::get('constants.SIB_URL');
            $appEnv = Config::get('constants.APP_ENV');
            $emailTemplateId = (int) Config::get('constants.SIB_CAR_RENEWALS_TEMPLATE_ID');
            $tag = 'renewal';

            if ($appEnv == 'production') {
                $tag = $tag;
            } else {
                $tag = $appEnv.'-'.$tag;
            }

            $headers = [
                'Accept' => 'application/json',
                'api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ];

            $body = json_encode([
                'to' => [[
                    'email' => $emailData['customerEmail'],
                    'name' => $emailData['customerName'],
                ]],
                'bcc' => [[
                    'email' => $emailData['advisorEmail'],
                    'name' => $emailData['advisorName'],
                ]],
                'templateId' => $emailTemplateId,
                'params' => [
                    'customerName' => $emailData['customerName'],
                    'customerEmail' => $emailData['customerEmail'],
                    'cdbId' => $emailData['cdbId'],
                    'policyNumber' => $emailData['policyNumber'],
                    'expiryDate' => $emailData['expiryDate'],
                    'insurerName' => $emailData['insurerName'],
                    'planType' => $emailData['planType'],
                    'carMake' => $emailData['carMake'],
                    'carModel' => $emailData['carModel'],
                    'advisorName' => $emailData['advisorName'],
                    'advisorEmail' => $emailData['advisorEmail'],
                    'advisorMobile' => $emailData['advisorMobile'],
                    'advisorLandline' => $emailData['advisorLandline'],
                    'ecomUrl' => $emailData['ecomUrl'],
                ],
                'replyTo' => [
                    'email' => $emailData['advisorEmail'],
                ],
                'tags' => [
                    $tag,
                ],
            ]);

            $client = new \GuzzleHttp\Client();
            $clientRequest = $client->post(
                $url,
                [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 10000,
                ]
            );

            $getMsgDetail = json_decode($clientRequest->getBody()->getContents());

            $getStatusCode = $clientRequest->getStatusCode();
            $getResponse = json_encode($clientRequest->getStatusCode().' '.$clientRequest->getBody()->getContents());

            if ($getStatusCode == 201) {
                $isEmailSent = 1;
            } else {
                $errorMessage = 'SIB Error:  '.$getStatusCode.' '.$emailData['customerEmail'].' '.get_class();
                Log::error('errorMessage: '.$errorMessage);
                $isEmailSent = 0;
            }
        } catch (Exception $ex) {
            $errorMessage = 'SIB Failed Error: '.$ex->getCode().' '.$ex->getMessage().' '.get_class();
            Log::info('errorMessage: '.$errorMessage);
            $getStatusCode = $ex->getCode();
            $getResponse = json_encode($ex->getCode().' '.$ex->getMessage());
            $isEmailSent = 0;
        }

        $newEmailActivity = new EmailActivity();
        $newEmailActivity->api_response = $getResponse;
        $newEmailActivity->successful = $isEmailSent;
        $newEmailActivity->email = $emailData['customerEmail'];
        $newEmailActivity->save();

        return $getMsgDetail->messageId;
    }

    public function updateRenewalBatchRecord($batchEmailId)
    {
        $renewalsBatchStatus = RenewalsBatchEmails::where('id', $batchEmailId)->first();
        if ($renewalsBatchStatus) { // if record exists, update the number of rows uploaded
            $renewalsBatchStatus->total_sent = $renewalsBatchStatus->total_sent + 1;
            $renewalsBatchStatus->save();
        }
        if (($renewalsBatchStatus->total_sent + $renewalsBatchStatus->total_bounced) == $renewalsBatchStatus->total_leads) { // if all records are uploaded, update the status to completed
            $renewalsBatchStatus->status = ProcessStatusCode::COMPLETED;
            $renewalsBatchStatus->save();
        }
    }

    /**
     * //$modelName, $quoteRequestIdName.
     *
     * @param $quoteId
     * @param $quoteRequestIdName
     * @param $currentUserId
     * @param $advisorId
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

    public function uploadedLeadsValidation()
    {
        RenewalQuoteProcess::where('status', RenewalProcessStatuses::NEW)->chunk(50, function ($leads) {
            foreach ($leads as $lead) {
                $leadValidationErrors = collect();

                if (! QuoteType::where('short_code', $lead->quote_type)->first()) {
                    $leadValidationErrors->push('Invalid Insurance Type Provided');
                }
                if ($lead->type == RenewalsUploadType::UPDATE_LEADS && ! $lead->policy_number) {
                    $leadValidationErrors->push('Policy Number is mandatory for update process');
                }

                $leadData = (object) $lead->data;
                if (! InsuranceProvider::where('code', $leadData->insurer)->first()) {
                    $leadValidationErrors->push('Invalid Insurance Code Provided');
                }
                if ($leadData->advisor && ! User::where('email', $leadData->advisor)->first()) {
                    $leadValidationErrors->push('Invalid Advisor Email Address');
                }
                if (isset($leadData->start_date) && $leadData->start_date && ! $this->validateDate($leadData->start_date)) {
                    $leadValidationErrors->push('Invalid Start Date');
                }
                if (isset($leadData->end_date) && $leadData->end_date && ! $this->validateDate($leadData->end_date)) {
                    $leadValidationErrors->push('Invalid End Date');
                }
                if (isset($leadData->dob) && $leadData->dob && ! $this->validateDate($leadData->dob)) {
                    $leadValidationErrors->push('Invalid Date of Birth');
                }
                switch($lead->quote_type) {
                    case QuoteTypeShortCode::CAR:
                        if ($leadData->make && ! CarMake::where('text', $leadData->make)->first()) {
                            $leadValidationErrors->push('Invalid Car Make');
                        }
                        if ($leadData->model && ! CarModel::where('text', $leadData->model)->first()) {
                            $leadValidationErrors->push('Invalid Car Model');
                        }
                        if ($leadData->product_type != carTypeInsuranceCode::Comprehensive && $leadData->product_type != carTypeInsuranceCode::ThirdPartyOnly) {
                            $leadValidationErrors->push('Invalid Product Type');
                        }
                        if ($lead->type == RenewalsUploadType::CREATE_LEADS && $lead->policy_number) {
                            if (CarQuote::where('previous_quote_policy_number', $lead->policy_number)->where('previous_policy_expiry_date', $leadData->end_date)->first()) {
                                $leadValidationErrors->push('Quote already created for this policy number, use upload and update');
                            }
                        }
                        if ($lead->type == RenewalsUploadType::UPDATE_LEADS && ! CarQuote::where('policy_number', $lead->policy_number)->first()) {
                            $leadValidationErrors->push('No Quote exists against this Policy Number, either create quote or check policy number');
                        }

                        if ($lead->type == RenewalsUploadType::UPDATE_LEADS) {
                            $insuranceProvider = InsuranceProvider::where('text', $leadData->provider_name)->first();
                            if ($insuranceProvider) {
                                if (! CarPlan::where('repair_type', $leadData->plan_type)->where('text', $leadData->plan_name)->where('provider_id', $insuranceProvider->id)->first()) {
                                    $leadValidationErrors->push('Invalid Insurer Plan Name or Plan Type');
                                }
                            } else {
                                $leadValidationErrors->push('Invalid Provider Name');
                            }
                        }
                        if ($lead->type == RenewalsUploadType::UPDATE_LEADS && $leadData->claim_history) {
                            if (! ClaimHistory::where('text', $leadData->claim_history)->first()) {
                                $leadValidationErrors->push('Invalid Claim History');
                            }
                        }

                        if ($lead->type == RenewalsUploadType::UPDATE_LEADS && $leadData->nationality) {
                            if (! Nationality::where('text', $leadData->nationality)->first()) {
                                $leadValidationErrors->push('Invalid Nationality Text');
                            }
                        }
                        if ($lead->type == RenewalsUploadType::UPDATE_LEADS && $leadData->registration_location) {
                            if (! Emirate::where('text', $leadData->registration_location)->first()) {
                                $leadValidationErrors->push('Invalid Emirate');
                            }
                        }
                        if ($lead->type == RenewalsUploadType::UPDATE_LEADS && $leadData->driving_experience) {
                            if (! UAELicenseHeldFor::where('text', $leadData->driving_experience)->first()) {
                                $leadValidationErrors->push('Invalid Driving Experience');
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
                $renewalUploadLead = $lead->renewalUploadLead;
                if ($lead->status == RenewalProcessStatuses::VALIDATED) {
                    if ($lead->type == RenewalsUploadType::CREATE_LEADS) {
                        $this->createQuote($lead);
                    } elseif ($lead->type == RenewalsUploadType::UPDATE_LEADS) {
                        $this->updateQuote($lead);
                    }
                } else {
                    $renewalUploadLead->cannot_upload += 1;
                }
                $renewalUploadLead->save();
            }
        });
    }

    private function validateDate($date, $format = 'd/m/Y')
    {
        $d = DateTime::createFromFormat($format, $date);

        return $d && $d->format($format) === $date;
    }
}
