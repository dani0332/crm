<?php

namespace App\Services\PolicyIssuanceAutomation\Car;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\TravelQuoteEnum;
use App\Interfaces\PolicyIssuanceInterface;
use App\Repositories\PaymentRepository;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class LivaInsuranceService implements PolicyIssuanceInterface
{
    private $className = 'livaInsuranceService';
    private mixed $baseUrl;
    private $headers = [];

    public const INSURER_CODE = InsuranceProvidersEnum::ALNC;
    public const TYPE = quoteTypeCode::Car;
    public const TYPE_ID = QuoteTypeId::Car;

    public mixed $vat = null;
    public $policyIssuance = null;
    public $currentInsurerApiStatus = null;
    public function __construct()
    {
        $this->baseUrl = config('constants.LIVA_API_BASE_URL');
        $this->headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic '.config('constants.LIVA_BASIC_AUTH'),
            'PartnerId' => config('constants.LIVA_PARENT_ID'),
            'location' => config('constants.LIVA_LOCATION'),
            'Authentication' => 'Bearer '.config('constants.LIVA_AUTHENTICATION'),
            'SubscriptionKey' => config('constants.LIVA_SUBSCRIPTION_KEY'),
        ];
    }

    private function livaHttpCall($endPoint, $payload)
    {
        $url = $this->baseUrl.$endPoint;

        return Http::timeout(20)->withHeaders($this->headers)->post($url, $payload);
    }

    public function updateQuoteRequest($quote)
    {
        LoggerService::startQuoteLogging($quote->code, LoggerFeatureEnum::POLICY_AUTOMATION);

        $livaMapping = app(LivaInsurancePayloadMapping::class);

        $nationalityId = $livaMapping->nationalityList($quote->latestInsured->nationality->text);
        if (! $nationalityId) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Nationality not found on LIVA', extra: [
                'nationality' => $quote->latestInsured->nationality->text,
            ]);
        }

        $homeCountryLicenseIssuance = $livaMapping->nationalityList($quote?->carQuoteRequestDetail?->home_country_license_issuance);
        if (! $homeCountryLicenseIssuance) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Home Country License Issuance nationality not found on LIVA', extra: [
                'nationality' => $quote?->carQuoteRequestDetail?->home_country_license_issuance,
            ]);
        }

        $vehicleMakeId = $livaMapping->vehicleMakeList(strtoupper($quote?->carMake?->text));
        if (! $vehicleMakeId) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Vehicle Make not found on LIVA', extra: [
                'vehicleMake' => strtoupper($quote?->carMake?->text),
            ]);
        }

        $vehicleModelList = $this->getVehicleModelId($vehicleMakeId);

        if (empty($vehicleModelList) || ! isset($vehicleModelList[strtoupper($quote?->carModel?->text)])) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Vehicle Model not found on LIVA', extra: [
                'vehicleModel' => $quote?->carModel?->text,
                'vehicleMake' => $quote?->carMake?->text,
            ]);
        }

        $vehicleModelId = $vehicleModelList[strtoupper($quote?->carModel?->text)];

        $vehicleVariants = $this->getVehicleVariants($quote->latestInsured->dob, $quote->mobile_no, $vehicleMakeId, $vehicleModelId, $quote->year_of_manufacture, $quote->code);

        $closestVariant = $this->matchClosestVehicleVariant($vehicleVariants, [
            'cc' => $quote?->carModelDetail?->cubic_capacity,
            'noOfDoors' => $quote?->carModelDetail?->no_of_doors,
            'noOfCyl' => $quote?->cylinder,
            'bodyType' => $quote?->vehicleType->text,
            'trim' => $quote?->carQuoteRequestDetail?->insurer_trim,
        ]);

        $endPoint = 'quote/update/v2';
        $payload = [
            'QuotationRequest' => [
                'CustomerDetails' => [
                    'Gender' => 'M',
                    'DOB' => $quote->latestInsured->dob.' 00:00:00',
                    'Nationality' => $nationalityId,
                    'MobileNo' => $quote->mobile_no,
                    'EmailId' => $quote->email,
                    'FirstName' => $quote->latestInsured->first_name,
                    'LastName' => $quote->latestInsured->last_name,
                    'NationalId' => $quote->id_number,
                    'CustomerCategory' => 1,
                ], 
                'VehicleDetails' => [
                    'CC' => $quote?->carModelDetail?->cubic_capacity,
                    'PlaceOfRegn' => "1", // TODO: need to ask
                    'NcbYears' => 99,
                    'DateOfRegn' => $quote?->carQuoteRequestDetail?->first_registration_date ? ($quote?->carQuoteRequestDetail?->first_registration_date.' 00:00:00') : '',
                    'YearOfManf' => $quote?->year_of_manufacture,
                    'InsuredValue' => $quote?->car_value,
                    'VehicleDescCode' => $closestVariant->VehicleDescCode,
                    'VehicleDesc' => $quote?->carQuoteRequestDetail?->insurer_trim,
                    'Seats' => $quote?->seat_capacity,
                    'UseCode' => "2", // TODO: need to ask
                    'BodyType' => $closestVariant->BodyTypeCode,
                    'MakeCode' => $vehicleMakeId,
                    'ModelCode' => $vehicleModelId,
                    'NoOfCyl' => $quote?->cylinder,
                    'EstimatedAnnualMileage' => $quote?->carQuoteRequestDetail?->annual_mileage_estimate,
                    'VehicleSpecification' => $quote?->is_gcc_standard,
                    'vehHP' => $closestVariant->HP,
                    'NoOfDoors' => $quote?->carModelDetail?->no_of_doors,
                    'DrivenWheel' => $closestVariant->DrivenWheel,
                    'modelSpecification' => $closestVariant->ModelSpecification,
                    'ColorCode' => $quote?->carQuoteRequestDetail?->vehicle_color,
                    'RegistrationType' => "1", //TODO: need to ask, no UI available
                    'RtaTransactionType' => (string) $quote?->carQuoteRequestDetail?->rta_transaction_type,
                    'RegnNoText' => $quote->carQuoteRequestDetail?->plate_code,
                    'RegnNoNumber' => $quote->carQuoteRequestDetail?->plate_number,
                    'ChassisNo' => $quote->carQuoteRequestDetail?->chassis_number,
                    'EngineNo' => $quote->carQuoteRequestDetail?->engine_number,
                    'TcfNo' => $quote->carQuoteRequestDetail?->traffic_code_number,
                ],
                'TransactionDetails' => [
                    'PolicyTypeCode' => "1", // TODO: need to ask
                    'EffectiveDate' => "2025-01-06 21:09:00", // TODO: need to ask
                    'SchemeCode' => "13",
                    'TariffCode' => "17", // TODO: need to ask
                    'PartnerTrnReferenceNumber' => 'Q1aw2bvcvT', // TODO: need to ask
                ],
                'OptionalCovers' => [
                    [
                        'CoverIncluded' => true,
                        'CoverMappingCode' => "2-1-0", // TODO: need to ask
                    ],
                ],
                'DriverDetails' => [
                    [
                        'DriverName' => $quote?->latestInsured->first_name.' '.$quote?->latestInsured->last_name,
                        'MainDriverInd' => $quote?->carQuoteRequestDetail?->is_insured_and_driver_same ? "Y" : "N",
                        'DriverDOB' => $quote?->carQuoteRequestDetail?->driver_dob.' 00:00:00',
                        'DriverGender' => str_starts_with(strtoupper($quote?->carQuoteRequestDetail?->driver_gender ?? ''), 'M') ? "M" : "F",
                        'FirstDrvLicCountry' => $homeCountryLicenseIssuance,
                        'LocalLicense' => $quote?->carQuoteRequestDetail?->driver_uae_driving_experience,
                        'OtherLicense' => $quote?->carQuoteRequestDetail?->home_country_driving_experience,
                        'LicenseNo' => $quote?->carQuoteRequestDetail?->driver_license_number,
                    ],
                ],
                'QuotationNo' => $quote?->carQuotePlanDetail?->insurer_quote_no,
                'PolicyId' => $quote?->carQuotePlanDetail?->insurer_quote_no,
                'EndtId' => "0",
                'ProposalForm' => false,
                'UserComments' => "Update Quote Request",
            ]
        ];

        // dd($payload, "{$quote?->carQuoteRequestDetail?->rta_transaction_type}", (string) $quote?->carQuoteRequestDetail?->rta_transaction_type);


        $response = $this->livaHttpCall($endPoint, $payload);
        dd($response->object());

        return $response;
    }
    
    public function retrieveQuoteRequest()
    {
        $endPoint = 'transactions/retrieve/v2';
        $payload['RetrieveRequest'] = [
            'RetrieveType' => '2', // Retrive Type 2 = New Business Quote Retrival
            'TransactionNumber' => '7860255', // Quote number
            'EmailId' => 'john.doe@example.com',
            'PartnerTrnReferenceNumber' => 'Q1aw2bvcvT',
            'ProposalForm' => true,
        ];

        $response = $this->livaHttpCall($endPoint, $payload);

        return $response;
    }

    public function getVehicleModelId($makeCode)
    {
        $payload = [
            'MDRequest' => [
                'dropdowns' => [
                    'dropdown-name' => 'getVehicleModelListFromMakePartner',
                    'dropdown-criteria' => [
                        [
                            'criteria-name' => 'makeCode',
                            'criteria-value' => $makeCode,
                        ],
                        [
                            'criteria-name' => 'partnerID',
                            'criteria-value' => config('constants.LIVA_PARENT_ID'),
                        ],
                        [
                            'criteria-name' => 'schemeCode',
                            'criteria-value' => "13",
                        ],
                    ]
                ]
            ]
        ];

        $livaMapping = $this->livaHttpCall('v2/masterservices', $payload);

        return collect($livaMapping->object()->getVehicleModelListFromMake->items)
            ->pluck('value', 'name')
            ->mapWithKeys(fn($value, $key) => [strtoupper(trim($key)) => $value]);
    }

    public function getVehicleVariants($dob, $mobileNo, $makeCode, $modelCode, $modelYear, $refId)
    {
        $payload = [
            'VehicleVariantsRequest' => [
                'DOB' => $dob.' 00:00:00',
                'MobileNo' => $mobileNo,
                'MakeCode' => (int) $makeCode,
                'ModelCode' => (int) $modelCode,
                'ModelYear' => (int) $modelYear,
                'PartnerTrnReferenceNumber' => $refId,
            ]
        ];

        $livaMapping = $this->livaHttpCall('vehicle/variants/v2', $payload);

        return collect($livaMapping->object()->VehicleVariantsResponse->Variants);
    }

    public function createPolicyIssuanceSchedule($quote, $insurer)
    {

        LoggerService::startQuoteLogging($quote);
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        /* if ($this->isPolicyIssuanceAutomationEnabled()) {

            $this->policyIssuance = (new PolicyIssuanceService)->schedulePolicyIssuance($quote, $insurer, self::TYPE, $this->className);

        } else {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' -  Alliance Travel Automation is disabled');
        } */

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        return $this->policyIssuance;
    }

    public function executeSteps($process)
    {
        $response = ['status' => false, 'error' => null, 'message' => null];

        $this->policyIssuance = $process;
        $quote = $process->model;
        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::POLICY_AUTOMATION);

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' started');

        /* try {
            if ($this->isPolicyIssuanceAutomationEnabled()) {
                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Plan ID : '.$quote->plan_id);

                $payment = PaymentRepository::mainQuotePayment($quote);

                $selectedPlan = $quote->travelQuotePlanDetails()->where('plan_id', $payment->plan_id)->first();

                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - TravelQuotePlanDetails ID : '.$selectedPlan?->id);

                $travelType = TravelQuoteEnum::ALLIANCE_IN_BOUND;
                $directionCode = $quote->direction_code;
                if ($directionCode === TravelQuoteEnum::TRAVEL_UAE_OUTBOUND) {
                    $travelType = TravelQuoteEnum::ALLIANCE_OUT_BOUND;
                }

                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Travel Type : '.$travelType);
                $lastCompletedStep = $process->completed_step;
                $nextStepToBeExecuted = $lastCompletedStep ? $this->getNextStep($lastCompletedStep) : PolicyIssuanceEnum::ALLIANCE_TRAVEL_ISSUE_POLICY;
                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Next Step : '.$nextStepToBeExecuted);

                if ($nextStepToBeExecuted) {
                    if ($nextStepToBeExecuted === PolicyIssuanceEnum::ALLIANCE_TRAVEL_ISSUE_POLICY) {
                        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted);
                        $this->currentInsurerApiStatus = PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID;
                        $policyIssuanceResponse = $this->issuePolicyAndFillPolicyDetails($quote, $selectedPlan, $travelType);
                        if (! $policyIssuanceResponse['status']) {
                            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - trigger fn:updateQuoteApiIssuanceStatusAndAllocate for step '.$nextStepToBeExecuted, extra: ['response' => $policyIssuanceResponse]);
                            $this->updateQuoteApiIssuanceStatusAndAllocate($quote, $this->currentInsurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);

                            return $policyIssuanceResponse;
                        }
                        $process->update(['completed_step' => $policyIssuanceResponse['completed_step']]);
                    }

                    $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
                    if ($nextStepToBeExecuted === PolicyIssuanceEnum::ALLIANCE_TRAVEL_PURCHASE_POLICY) {
                        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted);
                        $this->currentInsurerApiStatus = PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID;
                        $policyPurchaseResponse = $this->policyPurchase($quote, $payment, $travelType);
                        if (! $policyPurchaseResponse['status']) {
                            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - trigger fn:updateQuoteApiIssuanceStatusAndAllocate for step '.$nextStepToBeExecuted, extra: ['response' => $policyPurchaseResponse]);
                            $this->updateQuoteApiIssuanceStatusAndAllocate($quote, $this->currentInsurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);

                            return $policyPurchaseResponse;
                        }
                        $process->update(['completed_step' => $policyPurchaseResponse['completed_step']]);
                    }

                    $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
                    if ($nextStepToBeExecuted === PolicyIssuanceEnum::ALLIANCE_TRAVEL_UPLOAD_POLICY_DOCUMENTS) {
                        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted);
                        $this->currentInsurerApiStatus = PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID;
                        $uploadPolicyDocumentResponse = $this->fetchAndUploadDocument($quote, $travelType);
                        if (! $uploadPolicyDocumentResponse['status']) {
                            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - trigger fn:updateQuoteApiIssuanceStatusAndAllocate for step '.$nextStepToBeExecuted, extra: ['response' => $uploadPolicyDocumentResponse]);
                            $this->updateQuoteApiIssuanceStatusAndAllocate($quote, $this->currentInsurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);

                            return $uploadPolicyDocumentResponse;
                        }
                        $quote->update(['quote_status_id' => QuoteStatusEnum::PolicyIssued, 'policy_issuance_status_id' => PolicyIssuanceStatusEnum::PolicyIssued, 'quote_status_date' => now()]);
                        $process->update(['completed_step' => $uploadPolicyDocumentResponse['completed_step']]);
                    }

                    $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
                    if ($nextStepToBeExecuted === PolicyIssuanceEnum::ALLIANCE_TRAVEL_FILL_POLICY_BOOKING_DETAILS) {
                        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted);
                        $this->currentInsurerApiStatus = PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID;
                        $fillPolicyDetailsResponse = $this->uploadBuyerTaxInvoiceAndFillBookingDetails($quote, $payment);
                        if (! $fillPolicyDetailsResponse['status']) {
                            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - trigger fn:updateQuoteApiIssuanceStatusAndAllocate for step '.$nextStepToBeExecuted, extra: ['response' => $fillPolicyDetailsResponse]);
                            $this->updateQuoteApiIssuanceStatusAndAllocate($quote, $this->currentInsurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);

                            return $fillPolicyDetailsResponse;
                        }
                        $process->update(['completed_step' => $fillPolicyDetailsResponse['completed_step']]);
                    }

                    $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
                    if ($nextStepToBeExecuted === PolicyIssuanceEnum::ALLIANCE_TRAVEL_BOOK_POLICY) {
                        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted);
                        $this->currentInsurerApiStatus = PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID;
                        $triggerBookPolicyResponse = $this->triggerBookPolicyProcess($quote);
                        if (! $triggerBookPolicyResponse['status']) {
                            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - trigger fn:updateQuoteApiIssuanceStatusAndAllocate for step '.$nextStepToBeExecuted, extra: ['response' => $triggerBookPolicyResponse]);
                            $this->updateQuoteApiIssuanceStatusAndAllocate($quote, $this->currentInsurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);

                            return $triggerBookPolicyResponse;
                        }
                        $process->update(['completed_step' => $triggerBookPolicyResponse['completed_step']]);
                    }
                } else {
                    LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Last Completed Step : '.$lastCompletedStep);
                }

                $response['status'] = true;
                $response['message'] = 'Sage Booking of policy triggered successfully';
            } else {

                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' -  Alliance Travel Automation is disabled');
                $response['error'] = 'Alliance Travel Automation is disabled';
                $response['message'] = 'Alliance Travel Automation is disabled';

            }
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
            $this->updateQuoteApiIssuanceStatusAndAllocate($quote, $this->currentInsurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Exception : '.$e->getMessage());

            return $response;
        } */

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' ended');

        return $response;
    }

    /* public function issuePolicyAndFillPolicyDetails($quote, $selectedPlan, $travelType): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        $response = ['status' => false, 'completed_step' => PolicyIssuanceEnum::ALLIANCE_TRAVEL_ISSUE_POLICY, 'error' => null, 'message' => null];

        $titleTraveller = [];
        $firstNameTraveller = [];
        $lastNameTraveller = [];
        $dobTraveller = [];
        $passportTraveller = [];
        $nationalityTraveller = [];

        $customerMembers = $quote->customerMembers;
        $primaryMember = $this->getPrimaryMember($quote, $customerMembers);
        foreach ($customerMembers as $member) {

            $titleTraveller[] = $this->getTitle($member->gender);
            $firstNameTraveller[] = $member->first_name;
            $lastNameTraveller[] = $member->last_name ?? ' ';
            $dobTraveller[] = $member->dob ? Carbon::parse($member->dob)->format('Y-m-d') : null;
            $passportTraveller[] = $member->passport;
            $nationalityTraveller[] = $member->nationality->alliance_nationality_id;
        }

        $endPoint = '/v1/quote/'.$travelType.'/finalise';
        $payload = [
            'quote_id' => $selectedPlan?->insurer_quote_id,
            'scheme_id' => $selectedPlan?->alliance_scheme_id,
            'title_customer' => $this->getTitle($primaryMember->gender),
            'first_name_customer' => $primaryMember->first_name,
            'last_name_customer' => $primaryMember->last_name,
            'title_traveller' => $titleTraveller,
            'first_name_traveller' => $firstNameTraveller,
            'last_name_traveller' => $lastNameTraveller,
            'dob' => $dobTraveller,
            'passport_number' => $passportTraveller,
            'nationality_traveller' => $nationalityTraveller,
            'email' => 'happiness@support.insurancemarket.ae', // will be static, as we dont share customer contact details outside organization
            'mobile' => '971502245943', // will be static, as we dont share customer contact details outside organization
            'agency_reference' => 'asc',
        ];
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PayLoad : '.json_encode($payload));

        $issuePolicy = $this->livaHttpCall($endPoint, $payload);
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Response : '.$issuePolicy);

        $issuePolicyResponse = $issuePolicy->object();
        $this->storePolicyIssuanceLog($quote, $payload, $issuePolicyResponse, $this->baseUrl.$endPoint, $response['completed_step'], $issuePolicy->failed() ? PolicyIssuanceEnum::FAILED_STATUS : PolicyIssuanceEnum::SUCCESS_STATUS);

        if ($issuePolicy->failed()) {
            $response['error'] = $issuePolicyResponse?->errors;

            return $response;
        }
        $issuePolicyResult = $issuePolicyResponse?->result;
        $insurerPolicyId = $issuePolicyResult?->policy_id;
        $premium = $issuePolicyResult?->premium;
        $priceVatApplicable = $premium / (1 + ((float) $this->vat / 100));
        $policyIssuanceDate = Carbon::now();
        $coverDays = $this->calculateCoverDaysForExpiryDate($quote, $travelType);
        $policyExpiryDate = Carbon::parse($quote->policy_start_date)->addDays($coverDays)->subDay();

        $quote->update([
            'insurer_policy_id' => $insurerPolicyId,
            'price_with_vat' => $premium,
            'vat' => $premium - $priceVatApplicable,
            'price_vat_applicable' => $priceVatApplicable,
            'policy_issuance_date' => $policyIssuanceDate,
            'policy_expiry_date' => $policyExpiryDate,
        ]);
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy Issued Api called successfully and Quote is updated');

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        $response['status'] = true;
        $response['message'] = 'Policy Issued Api called successfully and Quote is updated.';

        return $response;
    } */

    private function matchClosestVehicleVariant($vehicleVariants, $searchCriteria)
    {
        if (empty($vehicleVariants)) {
            return null;
        }

        $closestVariant = null;
        $highestScore = 0;

        foreach ($vehicleVariants as $variant) {
            $score = $this->calculateVariantMatchScore($variant, $searchCriteria);
            
            if ($score > $highestScore) {
                $highestScore = $score;
                $closestVariant = $variant;
            }
        }

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' - Best match found with score: '.$highestScore, extra: [
            'variant' => $closestVariant,
            'criteria' => $searchCriteria
        ]);

        return $closestVariant;
    }

    private function calculateVariantMatchScore($variant, $searchCriteria): float
    {
        $score = 0;
        $maxScore = 0;

        // CC (Engine Capacity) - Weight: 30%
        if (isset($searchCriteria['cc']) && $searchCriteria['cc'] !== null) {
            $ccScore = $this->calculateNumericScore($variant->CC ?? 0, $searchCriteria['cc']);
            $score += $ccScore * 0.3;
        }
        $maxScore += 0.3;

        // Number of Cylinders - Weight: 20%
        if (isset($searchCriteria['noOfCyl']) && $searchCriteria['noOfCyl'] !== null) {
            $cylScore = $this->calculateNumericScore($variant->NoOfCyl ?? 0, $searchCriteria['noOfCyl']);
            $score += $cylScore * 0.2;
        }
        $maxScore += 0.2;

        // Number of Doors - Weight: 20%
        if (isset($searchCriteria['noOfDoors']) && $searchCriteria['noOfDoors'] !== null) {
            $doorScore = $this->calculateNumericScore($variant->NoOfDoors ?? 0, $searchCriteria['noOfDoors']);
            $score += $doorScore * 0.2;
        }
        $maxScore += 0.2;

        // Body Type - Weight: 20%
        if (isset($searchCriteria['bodyType']) && $searchCriteria['bodyType'] !== null) {
            $bodyTypeScore = $this->calculateStringScore($variant->BodyType ?? '', $searchCriteria['bodyType']);
            $score += $bodyTypeScore * 0.2;
        }
        $maxScore += 0.2;

        // Variant Name - Weight: 10%
        if (isset($searchCriteria['trim']) && $searchCriteria['trim'] !== null) {
            $nameScore = $this->calculateStringScore($variant->ModelSpecification ?? '', $searchCriteria['trim']);
            $score += $nameScore * 0.1;
        }
        $maxScore += 0.1;

        // Normalize score to 0-100 range
        return $maxScore > 0 ? ($score / $maxScore) * 100 : 0;
    }

    private function calculateNumericScore($value1, $value2): float
    {
        $val1 = (float) $value1;
        $val2 = (float) $value2;

        if ($val1 == $val2) {
            return 1.0; // Perfect match
        }

        if ($val1 == 0 || $val2 == 0) {
            return 0.0; // No match if one is zero
        }

        $difference = abs($val1 - $val2);
        $average = ($val1 + $val2) / 2;
        $percentageDifference = ($difference / $average) * 100;

        // Return score based on percentage difference
        return match (true) {
            $percentageDifference <= 5 => 0.95,   // Very close
            $percentageDifference <= 10 => 0.85,  // Close
            $percentageDifference <= 20 => 0.70,  // Moderate
            $percentageDifference <= 30 => 0.50,  // Fair
            $percentageDifference <= 50 => 0.30,  // Poor
            default => 0.10                       // Very poor
        };
    }

    private function calculateStringScore($string1, $string2): float
    {
        $str1 = strtolower(trim($string1));
        $str2 = strtolower(trim($string2));

        if ($str1 === $str2) {
            return 1.0; // Perfect match
        }

        if (empty($str1) || empty($str2)) {
            return 0.0; // No match if either is empty
        }

        // Check if one string contains the other
        if (str_contains($str1, $str2) || str_contains($str2, $str1)) {
            return 0.8; // High score for partial match
        }

        // Calculate similarity percentage
        $similarity = 0;
        similar_text($str1, $str2, $similarity);
        
        return $similarity / 100;
    }
}
