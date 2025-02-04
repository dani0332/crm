<?php

namespace App\Repositories;

use App\Enums\LeadSourceEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\BikeQuote;
use App\Models\CycleQuote;
use App\Models\InslyAdvisor;
use App\Models\InslyDetail;
use App\Models\PetQuote;
use App\Models\QuoteType;
use App\Models\YachtQuote;
use App\Services\CapiRequestService;
use App\Services\CustomerService;
use App\Services\InslyDataService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\PersonalQuoteSyncTrait;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTime;

class InslyDetailRepository extends BaseRepository
{
    use GenericQueriesAllLobs, PersonalQuoteSyncTrait;

    public function model()
    {
        return InslyDetail::class;
    }
    public function fetchGetData()
    {
        $coverage = $this->getCoverageList(auth()->user());

        $query = InslyDetail::query();

        if (! empty($coverage)) {
            $query->whereIn('policy.coverage', $coverage);
        }

        if (! empty(request()->policy_number)) {
            $query->where('policy_no', '=', request()->policy_number);
        }

        if (! empty(request()->email)) {
            $query->where('customer.email', 'like', '%'.request()->email.'%');
        }

        if (! empty(request()->mobile_no)) {
            $query->where('customer.mobile_phone', 'like', '%'.request()->mobile_no.'%')
                ->orWhere('customer.mobile_phone', 'regex', $this->searchPhoneNumberRegexPattern(request()->mobile_no));
        }

        $data = $query->simplePaginate()->withQueryString()->toArray();

        return $data;
    }

    public function fetchGetBy($column, $value)
    {
        $query = $this->where($column, $value);
        if (! empty(request()->policy_oid)) {
            $query->orWhere('policy_oid', (int) request()->policy_oid);
        }
        $policy = $query->firstOrFail();
        $data = $policy->toArray();
        $policy->quoteType = $this->getQuoteTypeFromCoverage($data['policy']['coverage']);
        $policy->imcrm_link = $this->replaceStoredAppURLWithCurrentAppURL($policy->imcrm_link);

        $data['customer']['email'] = $this->maskData($data['customer']['email'], 'email');
        $data['customer']['mobile_phone'] = $this->maskData($data['customer']['mobile_phone'], 'phone');
        $data['customer']['phone'] = $this->maskData($data['customer']['phone'], 'phone');

        $policy->customer = $data['customer'];

        if (! empty($data['installments'])) {
            $policy->premium = collect($data['installments'])->sum('gross_premium');
        }

        return $policy;
    }

    private function getQuoteTypeFromCoverage($coverage)
    {
        $coverage = $coverage ?? null;
        $inslyCoverageArray = (new InslyDataService)->inslyInsurances();
        $quoteType = null;
        foreach ($inslyCoverageArray as $key => $item) {
            $lowerCaseCoverageValues = array_map('strtolower', $item);
            $item = array_merge($item, $lowerCaseCoverageValues);
            if (in_array($coverage, $item)) {
                $quoteType = $key;
            }
        }

        return $quoteType;
    }

    public function fetchSaveToImcrm($data)
    {
        $policyID = $data['policy_oid'];
        $validateAll = $data['validateAll'];

        $policy = $this->where('policy_oid', $policyID)->first();
        $email = $policy['customer']['email'] ?? null;

        /* Temp Code - assign email for particular Policy id/number */
        $tempEmail = 'vitara@inbox.ru';
        $tempPolicyId = 66495910;
        if ($tempPolicyId == $data['policy_oid']) {
            $email = $tempEmail;
        }
        /* Temp Code - assign email for particular Policy id/number */

        if (empty($email)) {
            return [
                'status' => 400,
                'message' => 'Customer email not found.',
                'data' => '',
            ];
        }

        $inslyPolicyIssueDate = $policy['policy']['issue_date'] ?? null;

        if ($inslyPolicyIssueDate) {
            $inslyPolicyIssueDate = $this->formatDate($inslyPolicyIssueDate);
        }
        $appUrl = config('constants.APP_URL');
        $advisorName = $policy['policy']['renewer_person'] ?? null;
        if ($advisorName == null) {
            $advisorName = $policy['quote']['broker'] ?? null;
        }
        $appUrl = config('constants.APP_URL');
        $advisorId = optional(InslyAdvisor::where('name', $advisorName)->first())->user_id;
        if ($advisorId == null) {
            return [
                'status' => 400,
                'message' => 'Advisor not found.',
                'data' => '',
            ];
        }

        if (! empty($policy)) {
            $policyNumber = $policy['policy']['policy_no'];
            $coverage = $policy['policy']['coverage'];
            $quoteType = $this->getQuoteTypeFromCoverage($coverage);
            $data = [];
            $model = $this->getModelObject($quoteType);
            if ($model) {
                // quote against policy number
                $quote = $model::where('policy_number', $policyNumber)->orWhere('previous_quote_policy_number', $policyNumber)->first();
                $isPersonalQuote = checkPersonalQuotes($quoteType);
                if (! empty($quote) && $validateAll) {
                    if ($isPersonalQuote) {
                        $quote->link = $appUrl.'/personal-quotes/'.strtolower($quoteType).'/'.$quote->uuid;
                    } else {
                        $quote->link = $appUrl.'/quotes/'.strtolower($quoteType).'/'.$quote->uuid;
                    }
                    $quote->modelType = $quoteType;
                    $data[] = $quote;

                    ! $isPersonalQuote && $this->syncQuote($quote, $quote->getDirty());

                    return [
                        'status' => 200,
                        'message' => '',
                        'type' => 'policy_number',
                        'data' => $data,
                    ];
                }

                $dateFrom = Carbon::createFromFormat('Y-m-d', $inslyPolicyIssueDate)->addMonths(-1)->startOfDay();
                $dateTo = Carbon::createFromFormat('Y-m-d', $inslyPolicyIssueDate)->addMonths(1)->endOfDay();

                $modelClassName = app($model);
                $tableName = $modelClassName->getTable();

                $quote = $model::leftJoin('payments as py', function ($join) use ($modelClassName, $tableName) {
                    $join->on('py.paymentable_id', '=', $tableName.'.id')
                        ->where('py.paymentable_type', '=', $modelClassName::class);
                })
                    ->where($tableName.'.email', $email)
                    ->whereBetween('py.captured_at', [$dateFrom, $dateTo])
                    ->get();

                // quote against email and in between two month of payment captured
                if (! $quote->isEmpty() && $validateAll) {
                    foreach ($quote as $item) {

                        $item->advisor_name = $item->advisor->name ?? null;

                        if (ucfirst($quoteType) == QuoteTypes::CAR->value) {
                            $item->make = $item->carMake->text ?? null;
                            $item->model = $item->carModel->text ?? null;
                        }
                        if (ucfirst($quoteType) == QuoteTypes::BUSINESS->value) {
                            $item->type_of_insurance = $item->businessTypeOfInsurance->code ?? null;
                        }
                        if (ucfirst($quoteType) == QuoteTypes::HOME->value) {
                            $item->apartment_or_villa = $item->accommodationType->text ?? null;
                            $item->landlord_or_tenant = $item->possessionType->text ?? null;
                        }
                        if (ucfirst($quoteType) == QuoteTypes::PET->value) {
                            $item->breed = $item->petQuote->breed_of_pet1 ?? null;
                        }
                        if ($isPersonalQuote) {
                            $item->link = $appUrl.'/personal-quotes/'.strtolower($quoteType).'/'.$item->uuid;
                        } else {
                            $item->link = $appUrl.'/quotes/'.strtolower($quoteType).'/'.$item->uuid;
                        }
                        $item->modelType = $quoteType;
                        ! $isPersonalQuote && $this->syncQuote($item, $item->getDirty());
                        $data[] = $item;
                    }

                    return [
                        'status' => 200,
                        'message' => '',
                        'type' => 'email',
                        'data' => $data,
                    ];
                }

                // create lead in case no record found
                $payLoad = $this->prePareData($policy, $quoteType, $isPersonalQuote);
                $payLoad['advisor_id'] = $advisorId;
                info('InslyLead - Payload: '.json_encode($payLoad));
                $id = $model::create($payLoad)->id;
                info('InslyLead - created Lead Id : '.json_encode($id));
                if (! empty($id)) {
                    $obj = $model::where('id', $id)->first();
                    switch (ucfirst($quoteType)) {

                        case QuoteTypes::BUSINESS->value:
                            $obj->businessQuoteRequestDetail()->updateOrCreate(
                                ['business_quote_request_id' => $obj->id],
                                ['insly_id' => $policy->_id]
                            );
                            break;

                        case QuoteTypes::CAR->value:
                            $upsertRecord = $obj->carQuoteRequestDetail()->updateOrCreate(
                                ['car_quote_request_id' => $obj->id],
                                ['insly_id' => $policy->_id]
                            );
                            info('fetchSaveToImcrm - leadId : '.$obj->id.' - CarQuoteRequestDetail - created: '.$upsertRecord->wasRecentlyCreated);
                            break;

                        case QuoteTypes::LIFE->value:
                            $obj->lifeQuoteRequestDetail()->updateOrCreate(
                                ['life_quote_request_id' => $obj->id],
                                ['insly_id' => $policy->_id]
                            );
                            break;

                        case QuoteTypes::HOME->value:
                            $obj->homeQuoteRequestDetail()->updateOrCreate(
                                ['home_quote_request_id' => $obj->id],
                                ['insly_id' => $policy->_id]
                            );
                            break;

                        case QuoteTypes::TRAVEL->value:
                            $obj->travelQuoteRequestDetail()->updateOrCreate(
                                ['travel_quote_request_id' => $obj->id],
                                ['insly_id' => $policy->_id]
                            );
                            break;

                        case QuoteTypes::HEALTH->value:
                            $obj->healthQuoteRequestDetail()->updateOrCreate(
                                ['health_quote_request_id' => $obj->id],
                                ['insly_id' => $policy->_id]
                            );
                            break;
                        case QuoteTypes::PET->value:
                            $obj->petQuote()->updateOrCreate(
                                ['personal_quote_id' => $id],
                                Arr::only($payLoad, (new PetQuote)->allowedColumns())
                            );
                            $obj->quoteDetail()->updateOrCreate(
                                ['personal_quote_id' => $id],
                                ['insly_id' => $policy->_id]
                            );
                            break;
                        case QuoteTypes::BIKE->value:
                            $obj->bikeQuote()->updateOrCreate(
                                ['personal_quote_id' => $id],
                                Arr::only($payLoad, (new BikeQuote)->allowedColumns())
                            );
                            $obj->quoteDetail()->updateOrCreate(
                                ['personal_quote_id' => $id],
                                ['insly_id' => $policy->_id]
                            );
                            break;
                        case QuoteTypes::CYCLE->value:
                            $obj->cycleQuote()->updateOrCreate(
                                ['personal_quote_id' => $id],
                                Arr::only($payLoad, (new CycleQuote)->allowedColumns())
                            );
                            $obj->quoteDetail()->updateOrCreate(
                                ['personal_quote_id' => $id],
                                ['insly_id' => $policy->_id]
                            );
                            break;
                        case QuoteTypes::YACHT->value:
                            $obj->yachtQuote()->updateOrCreate(
                                ['personal_quote_id' => $id],
                                Arr::only($payLoad, (new YachtQuote)->allowedColumns())
                            );
                            $obj->quoteDetail()->updateOrCreate(
                                ['personal_quote_id' => $id],
                                ['insly_id' => $policy->_id]
                            );
                            break;
                    }
                    $policy->moved_to_imcrm = true;
                    if ($isPersonalQuote) {
                        $policy->imcrm_link = '/personal-quotes/'.strtolower($quoteType).'/'.$obj->uuid;
                    } else {
                        $policy->imcrm_link = '/quotes/'.strtolower($quoteType).'/'.$obj->uuid;
                    }
                    ! $isPersonalQuote && $this->syncQuote($obj, $payLoad);
                    $policy->moved_to_imcrm_date = date('Y-m-d H:i:s');
                    $policy->moved_to_imcrm_by = auth()->user()->name;
                    $policy->code = $obj->code;
                    $policy->save();
                }
                $inslyPolicy = $this->where('policy_no', $policyNumber)->first();
                if ($inslyPolicy) {
                    $data[] = $inslyPolicy->toArray();
                }

                return [
                    'status' => 201,
                    'message' => 'Lead Created Successfully',
                    'data' => $data,
                ];
            } else {
                return [
                    'status' => 400,
                    'message' => 'Quote Type not found.',
                    'data' => '',
                ];
            }
        } else {
            return [
                'status' => 400,
                'message' => 'Quote not found.',
                'data' => '',
            ];
        }
    }

    // payload
    private function prePareData($policy, $quoteType, $isPersonalQuote)
    {

        $dataArr = [];
        $coverage = $policy['policy']['coverage'];
        $dataArr['previous_quote_policy_number'] = $policy['policy_no'] ?? null;

        [$dataArr['email'], $additionalEmails] = $this->getPrimaryAndAdditionalEmails($policy);

        /* Temp Code - assign email for particular Policy id/number */
        $tempEmail = 'vitara@inbox.ru';
        $tempPolicyId = 66495910;
        if ($tempPolicyId == $policy['policy_oid']) {
            [$dataArr['email'], $additionalEmails] = [$tempEmail, []];
        }
        /* Temp Code - assign email for particular Policy id/number */

        $dataArr['policy_number'] = $policy['policy_no'] ?? null;
        $dataArr['policy_start_date'] = isset($policy['policy']['start_date']) ? $this->formatDate($policy['policy']['start_date']) : null;
        $dataArr['policy_expiry_date'] = isset($policy['policy']['end_date']) ? $this->formatDate($policy['policy']['end_date']) : null;

        if ($insurer = $policy['policy']['insurer'] ?? null) {
            info('Fetching Insurance Provider Id from Legacy Lead policy no: '.$policy['policy_no'].' and Insurer: '.trim($insurer));
            $insuranceProviderId = InsuranceProviderRepository::getInslyProviderId(trim($insurer));
            $dataArr['insurance_provider_id'] = $insuranceProviderId;
            info('Assign Insurance Provider Id: '.$insuranceProviderId.' against Insurer: '.trim($insurer).' Legacy Lead policy no: '.$policy['policy_no']);
        }

        $dataArr['policy_issuance_date'] = now()->format('Y-m-d');

        $previousPolicyStartDate = $policy['policy']['end_date'] ?? null;
        if ($previousPolicyStartDate) {
            $dataArr['previous_policy_expiry_date'] = $this->formatDate($previousPolicyStartDate);
        }

        $customerName = $policy['customer']['name'] ?? null;
        $arr = explode(' ', trim($customerName));
        $dataArr['first_name'] = $arr[0];
        array_shift($arr);

        $dataArr['last_name'] = implode(' ', $arr);

        [$dataArr['mobile_no'], $additionalMobiles] = $this->getPrimaryAndAdditionalMobileNumbers($policy);

        $dataArr['mobile_no'] = $dataArr['mobile_no'] ?? '0552244556';

        $premium = null;
        $data = $policy->toArray();
        if (! empty($data['installments'])) {
            $premium = collect($data['installments'])->sum('gross_premium');
        }
        $quoteTypeData = QuoteType::where('code', $quoteType)->first();
        if ($dataArr['email'] != null) {
            $customerService = new CustomerService;
            $customer = $customerService->createCustomerIfNotExists($dataArr);

            $customerService->addAdditionalContactsIfNotExists($customer, [
                'additional_emails' => $additionalEmails,
                'additional_mobiles' => $additionalMobiles,
            ]);
            $dataArr['customer_id'] = $customer->id ?? null;
        } else {
            $dataArr['customer_id'] = null;
        }
        $capi = new CapiRequestService;
        $resp = $capi->getUUID($quoteTypeData->id);
        if ($resp) {
            $dataArr['uuid'] = $resp->uuid;
            $dataArr['code'] = $quoteTypeData->short_code.'-'.$resp->uuid;
        }
        $dataArr['premium'] = $premium;
        $dataArr['source'] = LeadSourceEnum::INSLY;
        $dataArr['insly_migrated'] = true;
        $dataArr['quote_status_id'] = QuoteStatusEnum::NewLead;
        if ($isPersonalQuote) {
            $dataArr['quote_type_id'] = $quoteTypeData->id;
            $dataArr['is_ecommerce'] = false;
        }
        if (ucfirst($quoteType) == QuoteTypes::BUSINESS->value) {
            $dataArr['business_type_of_insurance_id'] = $coverage ? $this->getBusinessTypeOfInsuranceIDFromCoverage(strtolower($coverage)) : null;
        }

        return $dataArr;
    }

    public function getCoverageList($user)
    {
        $coverage = [];
        $inslyCoverageArray = (new InslyDataService)->inslyInsurances();
        if ($user->hasRole(RolesEnum::BikeAdvisor)) {
            $coverage = array_merge($coverage, $inslyCoverageArray[QuoteTypes::BIKE->value]);
        }
        if ($user->hasRole(RolesEnum::CorpLineAdvisor)) {
            $coverage = array_merge($coverage, $inslyCoverageArray[QuoteTypes::BUSINESS->value]);
        }
        if ($user->hasRole(RolesEnum::CarAdvisor)) {

            $coverage = array_merge($coverage, $inslyCoverageArray[QuoteTypes::CAR->value]);
        }
        if ($user->hasRole(RolesEnum::LifeAdvisor)) {
            $coverage = array_merge($coverage, $inslyCoverageArray[QuoteTypes::LIFE->value]);
        }
        if ($user->hasRole(RolesEnum::HomeAdvisor)) {

            $coverage = array_merge($coverage, $inslyCoverageArray[QuoteTypes::HOME->value]);
        }
        if ($user->hasRole(RolesEnum::TravelAdvisor)) {

            $coverage = array_merge($coverage, $inslyCoverageArray[QuoteTypes::TRAVEL->value]);
        }
        if ($user->hasRole(RolesEnum::HealthAdvisor)) {

            $coverage = array_merge($coverage, $inslyCoverageArray[QuoteTypes::HEALTH->value]);
        }
        if ($user->hasRole(RolesEnum::CycleAdvisor)) {

            $coverage = array_merge($coverage, $inslyCoverageArray[QuoteTypes::CYCLE->value]);
        }
        if ($user->hasRole(RolesEnum::PetAdvisor)) {

            $coverage = array_merge($coverage, $inslyCoverageArray[QuoteTypes::PET->value]);
        }
        if ($user->hasRole(RolesEnum::YachtAdvisor)) {

            $coverage = array_merge($coverage, $inslyCoverageArray[QuoteTypes::YACHT->value]);
        }
        if (! empty($coverage)) {
            // converted all values to lower case because some time data in mongodb have different case values.
            $lowerCaseCoverageValues = array_map('strtolower', $coverage);
            $coverage = array_merge($coverage, $lowerCaseCoverageValues);
        }

        return $coverage;
    }

    private function formatDate($date)
    {
        if ($date instanceof UTCDateTime) {
            return $date->toDateTime()->format('Y-m-d');
        } else {
            return Carbon::parse($date)->format('Y-m-d');
        }
    }
    private function replaceStoredAppURLWithCurrentAppURL($url)
    {
        $hostUrl = config('constants.APP_URL');
        if ($url) {
            $parsedUrl = parse_url($url);

            return $hostUrl.$parsedUrl['path'];
        }

        return null;

    }

    private function searchPhoneNumberRegexPattern($mobileNo)
    {
        $phoneNumber = str_replace(' ', '', $mobileNo);
        // Creating a regex pattern to match phone numbers ignoring spaces
        $regexPattern = implode('.*', str_split($phoneNumber));

        return new Regex("$regexPattern", 'i');
    }

    private function getBusinessTypeOfInsuranceIDFromCoverage($coverage)
    {
        $coverage = $coverage ?? null;
        $inslyBusinessTypeOfInsurances = (new InslyDataService)->inslyBusinessTypeOfInsurance();
        $businessTypeOfInsurance = null;
        foreach ($inslyBusinessTypeOfInsurances as $key => $inslyBusinessTypeOfInsurance) {
            $lowercaseBusinessTypeOfInsurance = array_map('strtolower', $inslyBusinessTypeOfInsurance);
            $inslyBusinessTypeOfInsurance = array_merge($inslyBusinessTypeOfInsurance, $lowercaseBusinessTypeOfInsurance);
            if (in_array($coverage, $inslyBusinessTypeOfInsurance)) {
                $businessTypeOfInsurance = $key;
            }
        }

        return $businessTypeOfInsurance ? quoteBusinessTypeCode::getId($businessTypeOfInsurance) : null;
    }

    private function getPrimaryAndAdditionalEmails($policy)
    {
        // Handling multiple emails in comma separated format
        $primaryEmail = null;
        $emails = [];

        if (isset($policy['customer']['email'])) {
            $emails = $this->splitAndFilterValues($policy['customer']['email']);
            if (count($emails) > 0) {
                $primaryEmail = $emails[0];
                unset($emails[0]);
            }
        }

        if (isset($policy['customer']['contact_person_email'])) {
            $contactEmails = $this->splitAndFilterValues($policy['customer']['contact_person_email']);
            // If there's already an email set, ensure it's not duplicated
            if ($primaryEmail !== null) {
                $contactEmails = array_diff($contactEmails, [$primaryEmail]);
            }
            $emails = array_merge($emails, $contactEmails);
            if ($primaryEmail === null && count($emails) > 0) {
                $primaryEmail = $emails[0];
                unset($emails[0]);
            }
        }

        // Remove duplicates
        $additionalEmails = array_unique($emails);

        return [$primaryEmail, $additionalEmails];
    }

    private function getPrimaryAndAdditionalMobileNumbers($policy)
    {
        // Handling multiple phone numbers in comma separated format
        $primaryPhone = null;
        $phones = [];

        if (isset($policy['customer']['mobile_phone'])) {
            $phones = $this->splitAndFilterValues($policy['customer']['mobile_phone']);
            if (count($phones) > 0) {
                $primaryPhone = $phones[0];
                unset($phones[0]);
            }
        }

        if (isset($policy['customer']['phone'])) {
            $contactPhones = $this->splitAndFilterValues($policy['customer']['phone']);
            // If there's already a phone set, ensure it's not duplicated
            if ($primaryPhone !== null) {
                $contactPhones = array_diff($contactPhones, [$primaryPhone]);
            }
            $phones = array_merge($phones, $contactPhones);
            if ($primaryPhone === null && count($phones) > 0) {
                $primaryPhone = $phones[0];
                unset($phones[0]);
            }
        }

        // Remove duplicates
        $additionalPhones = array_unique($phones);

        return [$primaryPhone, $additionalPhones];
    }

    private function splitAndFilterValues($inputString)
    {
        return array_filter(array_map('trim', preg_split('/[;,]/', $inputString)), function ($value) {
            return ! empty($value);
        });
    }

    private function maskData($data, $type)
    {
        if (empty($data)) {
            return null;
        }

        $dataArray = ($type === 'email')
            ? preg_split('/[;,]\s*/', $data)
            : preg_split('/[;,]+/', $data);

        $maskedData = array_map(function ($item) use ($type) {
            $item = trim($item);
            if ($type === 'email') {
                if (! isValidEmail($item)) {
                    return $item;
                }

                [$localPart, $domainPart] = explode('@', $item);
                $halfLength = ceil(strlen($localPart) / 2);
                $maskedLocalPart = substr($localPart, 0, $halfLength).str_repeat('*', strlen($localPart) - $halfLength);

                return $maskedLocalPart.'@'.$domainPart;
            } elseif ($type === 'phone') {

                $cleanedNumber = preg_replace('/\D/', '', $item);
                if (strlen($cleanedNumber) < 7) {
                    return $item;
                }

                $prefix = substr($cleanedNumber, 0, 3);
                $suffix = substr($cleanedNumber, -3);

                return "{$prefix}****{$suffix}";
            }
        }, $dataArray);

        return implode(', ', array_filter($maskedData));
    }
}
