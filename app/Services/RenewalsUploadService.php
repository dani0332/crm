<?php

namespace App\Services;

use App\Models\BikeQuote;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\TravelQuote;
use App\Models\YachtQuote;
use App\Models\RenewalsDump;
use App\Models\EmailActivity;
use App\Models\QuoteStatus;
use App\Models\RenewalsBatchEmails;
use App\Models\RenewalsUploadLeads;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarTypeInsurance;
use App\Models\User;
use App\Services\RenewalsAddonServices;
use App\Services\CheckAmlService;
use App\Services\CapiRequestService;
use App\Enums\quoteTypeCode;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypeShortCode;
use App\Enums\RenewalsUploadType;
use App\Enums\ProcessStatusCode;
use App\Models\EmailStatus;
use Exception;
use Config;
use Illuminate\Support\Facades\Log;

class RenewalsUploadService
{
    protected $renewalsAddonService, $checkAMLService, $capiRequestService;
    
    function __construct(RenewalsAddonServices $renewalsAddonService, CheckAmlService $checkAMLService, CapiRequestService $capiRequestService)
    {
        $this->renewalsAddonService = $renewalsAddonService;
        $this->checkAMLService = $checkAMLService;
        $this->capiRequestService = $capiRequestService;
    }

    /*
    * @name generateUUID()
    * @returns a 16 character UUIDv4 string
    */
    public function generateUUID($quoteTypeId)
    {
        $response = $this->capiRequestService->getUUID($quoteTypeId);

        if($response) {
            return $response->uuid;
        }
    }

    /*
    * @name createUpdateQuote()
    * @params $quoteData - extracted data from excel file, $qouteType - type of quote
    * @returns custom function decalred against each quote type else returns false
    */
    public function createUpdateQuote($quoteData, $quoteType, $fileName, $renewalImportCode, $uploadType)
    {
        if (!$quoteData) {
            return false;
        }
        if (!$quoteType) {
            return false;
        }

        $transApprovedId = $this->getquoteStatusIdbyCode(quoteStatusCode::TRANSACTION_APPROVED);
        $newLeadId = $this->getquoteStatusIdbyCode(quoteStatusCode::NEW_LEAD);

        if ($quoteType == QuoteTypeShortCode::BIK) {
            $this->createNewBikeQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode);
            $this->updateRenewalUploadLeadRecord($fileName);
        }
        if ($quoteType == QuoteTypeShortCode::BUS) {
            $this->createNewBusinessQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode);
            $this->updateRenewalUploadLeadRecord($fileName);
        }
        if ($quoteType == QuoteTypeShortCode::CAR) {
            if ($uploadType == RenewalsUploadType::CREATE_LEADS) {
                $this->createNewCarQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode);
                $this->updateRenewalUploadLeadRecord($fileName);
            }
            if ($uploadType == RenewalsUploadType::UPDATE_LEADS) {
                $this->updateExistingCarQuote($quoteData, $renewalImportCode);
                $this->updateRenewalUploadLeadRecord($fileName);
            }
        }
        
        if ($quoteType == QuoteTypeShortCode::HEA) {
            $this->createNewHealthQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode);
            $this->updateRenewalUploadLeadRecord($fileName);
        }
        if ($quoteType == QuoteTypeShortCode::HOM) {
            $this->createNewHomeQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode);
            $this->updateRenewalUploadLeadRecord($fileName);
        }
        if ($quoteType == QuoteTypeShortCode::LIF) {
            $this->createNewLifeQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode);
            $this->updateRenewalUploadLeadRecord($fileName);
        }
        if ($quoteType == QuoteTypeShortCode::TRA) {
            $this->createNewTravelQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode);
            $this->updateRenewalUploadLeadRecord($fileName);
        }
        if ($quoteType == QuoteTypeShortCode::YAC) {
            $this->createNewYachtQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode);
            $this->updateRenewalUploadLeadRecord($fileName);
        }
    }

    public function updateRenewalUploadLeadRecord($fileName)
    {
        $record = RenewalsUploadLeads::where('file_name', $fileName)->first();
        if($record)
        {
            // if record exists, update the number of rows uploaded
            $record->good = $record->good + 1;
            $record->save();
        }
        if(($record->good + $record->cannot_upload) == $record->total_records){
            // if all records are uploaded, update the status to completed
                $record->status = ProcessStatusCode::COMPLETED;
                $record->save();
        }
    }

    function createNewBikeQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Bike);

        $newBikeQuote = new BikeQuote([
            "first_name" => $quoteData->first_name,
            "last_name" => $quoteData->last_name,
            "customer_id" => $quoteData->customer_id,
            "email" => $quoteData->email,
            "mobile_no" => $quoteData->phoneNumber,
            "uuid" => $quoteUuid,
            "code" => 'BIK-'.$quoteUuid,
            "source" => 'Renewal_upload',
            "currently_insured_with" => $quoteData->insurer,
            "year_of_manufacture" => $quoteData->year,
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes,
            "renewal_batch" => $quoteData->batch,
            "renewal_expiry_date" => $quoteData->endDate,
            "quote_status_id" => $transApprovedId,
            "other_email_addresses" => $quoteData->other_email_ids,
            "renewal_import_code" => $renewalImportCode,
            "premium" => $quoteData->gross_premium
        ]);
        $newBikeQuote->save();
        $createRenewalQuote = $this->createNewRenewalBikeQuote($newBikeQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode, $quoteData->endDate, $quoteData->gross_premium);
        $this->createRenewalDumpRecord('Bike', $createRenewalQuote, $quoteData);
    }

    function createNewBusinessQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Business);

        if (isset($quoteData->product_type)) {
            $businessSubline = $this->renewalsAddonService->getBusinessSublineInsurance($quoteData->product_type);	
            $businessSublineInsuranceId = isset($businessSubline->id) ? $businessSubline->id : NULL;
        } else {
            $businessSublineInsuranceId = NULL;
        }

        $newBusinessQuote = new BusinessQuote([
            "first_name" => $quoteData->first_name,
            "last_name" => $quoteData->last_name,
            "customer_id" => $quoteData->customer_id,
            "email" => $quoteData->email,
            "mobile_no" => $quoteData->phoneNumber,
            "uuid" => $quoteUuid,
            "code" => 'BUS-'.$quoteUuid,
            "source" => 'Renewal_upload',
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes,
            "renewal_batch" => $quoteData->batch,
            "renewal_expiry_date" => $quoteData->endDate,
            "quote_status_id" => $transApprovedId,
            "other_email_addresses" => $quoteData->other_email_ids,
            "renewal_import_code" => $renewalImportCode,
            "premium" => $quoteData->gross_premium,
            "business_type_of_insurance_id" => $businessSublineInsuranceId
        ]);
        $newBusinessQuote->save();
        $createRenewalQuote = $this->createNewRenewalBusinessQuote($newBusinessQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode, $businessSublineInsuranceId, $quoteData->endDate, $quoteData->gross_premium);
        $this->createRenewalDumpRecord('Business', $createRenewalQuote, $quoteData);
    }

    function createNewCarQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode)
    {
        $carTypeOfInsurance = null;
        $carMake = $this->renewalsAddonService->getCarMake($quoteData->make);
        $carModel = $this->renewalsAddonService->getCarModel($quoteData->model);
        $vehicleType = null;
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Car);

        if ($carModel) {
            $vehicleType = $this->renewalsAddonService->getVehicleType($carModel->vehicle_type_id);
        }

        if ($quoteData->product_type != null) {
            $carTypeOfInsuranceInstance = $this->renewalsAddonService->getCarTypeOfInsurance($quoteData->product_type);	
            if($carTypeOfInsuranceInstance){
                $carTypeOfInsurance = $carTypeOfInsuranceInstance->id;
            }
        }

        $newCarQuote = new CarQuote([
            "first_name" => $quoteData->first_name,
            "last_name" => $quoteData->last_name,
            "customer_id" => $quoteData->customer_id,
            "email" => $quoteData->email,
            "mobile_no" => $quoteData->phoneNumber,
            "uuid" => $quoteUuid,
            "code" => 'CAR-'.$quoteUuid,
            "source" => 'Renewal_upload',
            "currently_insured_with" => $quoteData->insurer,
            "car_type_insurance_id" => $carTypeOfInsurance,
            "year_of_manufacture" => $quoteData->year ?? null,
            "car_make_id" => $carMake->id ?? null,
            "car_model_id" => $carModel->id ?? null,
            "policy_number" => $quoteData->policy,
            "cylinder" => $carModel->cylinder ?? null,
            "vehicle_category" => $vehicleType->category ?? null,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes,
            "renewal_batch" => $quoteData->batch,
            "quote_status_id" => $transApprovedId,
            "renewal_expiry_date" => $quoteData->endDate,
            "other_email_addresses" => $quoteData->other_email_ids,
            "renewal_import_code" => $renewalImportCode,
            "premium" => $quoteData->gross_premium
        ]);
        $newCarQuote->save();
        $createRenewalQuote = $this->createNewRenewalCarQuote($newCarQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode, $quoteData->endDate, $quoteData->gross_premium);
        $this->createRenewalDumpRecord('Car', $createRenewalQuote, $quoteData);
    }

    function createNewHealthQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Health);

        $newHealthQuote = new HealthQuote([
            "first_name" => $quoteData->first_name,
            "last_name" => $quoteData->last_name,
            "customer_id" => $quoteData->customer_id,
            "email" => $quoteData->email,
            "mobile_no" => $quoteData->phoneNumber,
            "uuid" => $quoteUuid,
            "code" => 'HEA-'.$quoteUuid,
            "source" => 'Renewal_upload',
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes,
            "renewal_batch" => $quoteData->batch,
            "renewal_expiry_date" => $quoteData->endDate,
            "quote_status_id" => $transApprovedId,
            "other_email_addresses" => $quoteData->other_email_ids,
            "renewal_import_code" => $renewalImportCode,
            "premium" => $quoteData->gross_premium
        ]);
        $newHealthQuote->save();
        $createRenewalQuote = $this->createNewRenewalHealthQuote($newHealthQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode, $quoteData->endDate, $quoteData->gross_premium);
        $this->createRenewalDumpRecord('Health', $createRenewalQuote, $quoteData);
    }

    function createNewHomeQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Home);
        $newHomeQuote = new HomeQuote([
            "first_name" => $quoteData->first_name,
            "last_name" => $quoteData->last_name,
            "customer_id" => $quoteData->customer_id,
            "email" => $quoteData->email,
            "mobile_no" => $quoteData->phoneNumber,
            "uuid" => $quoteUuid,
            "code" => 'HOM-'.$quoteUuid,
            "source" => 'Renewal_upload',
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes,
            "renewal_batch" => $quoteData->batch,
            "renewal_expiry_date" => $quoteData->endDate,
            "quote_status_id" => $transApprovedId,
            "other_email_addresses" => $quoteData->other_email_ids,
            "renewal_import_code" => $renewalImportCode,
            "premium" => $quoteData->gross_premium
        ]);
        $newHomeQuote->save();
        $createRenewalQuote = $this->createNewRenewalHomeQuote($newHomeQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode, $quoteData->endDate, $quoteData->gross_premium);
        $this->createRenewalDumpRecord('Home', $createRenewalQuote, $quoteData);
    }

    function createNewLifeQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Life);
        $newLifeQuote = new LifeQuote([
            "first_name" => $quoteData->first_name,
            "last_name" => $quoteData->last_name,
            "customer_id" => $quoteData->customer_id,
            "email" => $quoteData->email,
            "mobile_no" => $quoteData->phoneNumber,
            "uuid" => $quoteUuid,
            "code" => 'LIF-'.$quoteUuid,
            "source" => 'Renewal_upload',
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes,
            "renewal_batch" => $quoteData->batch,
            "renewal_expiry_date" => $quoteData->endDate,
            "quote_status_id" => $transApprovedId,
            "other_email_addresses" => $quoteData->other_email_ids,
            "renewal_import_code" => $renewalImportCode,
            "premium" => $quoteData->gross_premium
        ]);
        $newLifeQuote->save();
        $createRenewalQuote = $this->createNewRenewalLifeQuote($newLifeQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode, $quoteData->endDate, $quoteData->gross_premium);
        $this->createRenewalDumpRecord('Life', $createRenewalQuote, $quoteData);
    }

    function createNewTravelQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Travel);

        $newTravelQuote = new TravelQuote([
            "first_name" => $quoteData->first_name,
            "last_name" => $quoteData->last_name,
            "customer_id" => $quoteData->customer_id,
            "email" => $quoteData->email,
            "mobile_no" => $quoteData->phoneNumber,
            "uuid" => $quoteUuid,
            "code" => 'TRA-'.$quoteUuid,
            "source" => 'Renewal_upload',
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes,
            "renewal_batch" => $quoteData->batch,
            "renewal_expiry_date" => $quoteData->endDate,
            "quote_status_id" => $transApprovedId,
            "other_email_addresses" => $quoteData->other_email_ids,
            "renewal_import_code" => $renewalImportCode,
            "premium" => $quoteData->gross_premium
        ]);
        $newTravelQuote->save();
        $createRenewalQuote = $this->createNewRenewalTravelQuote($newTravelQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode, $quoteData->endDate, $quoteData->gross_premium);
        $this->createRenewalDumpRecord('Travel', $createRenewalQuote, $quoteData);
    }

    function createNewYachtQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Yacht);
        $newYachtQuote = new YachtQuote([
            "first_name" => $quoteData->first_name,
            "last_name" => $quoteData->last_name,
            "customer_id" => $quoteData->customer_id,
            "email" => $quoteData->email,
            "mobile_no" => $quoteData->phoneNumber,
            "uuid" => $quoteUuid,
            "code" => 'YAC-'.$quoteUuid,
            "source" => 'Renewal_upload',
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes,
            "renewal_batch" => $quoteData->batch,
            "renewal_expiry_date" => $quoteData->endDate,
            "quote_status_id" => $transApprovedId,
            "other_email_addresses" => $quoteData->other_email_ids,
            "renewal_import_code" => $renewalImportCode,
            "premium" => $quoteData->gross_premium
        ]);
        $newYachtQuote->save();
        $createRenewalQuote = $this->createNewRenewalYachtQuote($newYachtQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode, $quoteData->endDate, $quoteData->gross_premium);
        $this->createRenewalDumpRecord('Yacht', $createRenewalQuote, $quoteData);
    }

    function createNewRenewalBikeQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode, $endDate, $grossPremium)
    {
        $getBikeQuoteData = BikeQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Bike);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Bike);
        $createRenewalQuote = new BikeQuote([
            "first_name" => $getBikeQuoteData->first_name,
            "last_name" => $getBikeQuoteData->last_name,
            "customer_id" => $getBikeQuoteData->customer_id,
            "email" => $getBikeQuoteData->email,
            "mobile_no" => $getBikeQuoteData->mobile_no,
            "uuid" => $quoteUuid,
            "code" => 'BIK-'.$quoteUuid,
            "source" => 'Renewal_upload',
            "currently_insured_with" => $getBikeQuoteData->currently_insured_with,
            "year_of_manufacture" => $getBikeQuoteData->year_of_manufacture,
            "additional_notes" => $getBikeQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId,
            "renewal_batch" => $batchNumber,
            "previous_quote_policy_number" => $policy,
            "quote_status_id" => $newLeadId,
            "other_email_addresses" => $otherEmailIds,
            "renewal_import_code" => $renewalImportCode,
            "previous_policy_expiry_date" => $endDate,
            "previous_quote_policy_premium" => $grossPremium
        ]);
        $createRenewalQuote->save();
        $this->checkAMLService->checkAML($getBikeQuoteData->first_name, $getBikeQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalBusinessQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode, $businessSublineInsuranceId, $endDate, $grossPremium)
    {
        $getBusinessQuoteData = BusinessQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Business);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Business);

        $createRenewalQuote = new BusinessQuote([
            "first_name" => $getBusinessQuoteData->first_name,
            "last_name" => $getBusinessQuoteData->last_name,
            "customer_id" => $getBusinessQuoteData->customer_id,
            "email" => $getBusinessQuoteData->email,
            "mobile_no" => $getBusinessQuoteData->mobile_no,
            "uuid" => $quoteUuid,
            "code" => 'BUS-'.$quoteUuid,
            "source" => 'Renewal_upload',
            "additional_notes" => $getBusinessQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId,
            "renewal_batch" => $batchNumber,
            "previous_quote_policy_number" => $policy,
            "quote_status_id" => $newLeadId,
            "other_email_addresses" => $otherEmailIds,
            "renewal_import_code" => $renewalImportCode,
            "business_type_of_insurance_id" => $businessSublineInsuranceId,
            "previous_policy_expiry_date" => $endDate,
            "previous_quote_policy_premium" => $grossPremium
        ]);
        $createRenewalQuote->save();
        $this->checkAMLService->checkAML($getBusinessQuoteData->first_name, $getBusinessQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalCarQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode, $endDate, $grossPremium)
    {
        $getCarQuoteData = CarQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Car);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Car);

        $createRenewalQuote = new CarQuote([
            "first_name" => $getCarQuoteData->first_name,
            "last_name" => $getCarQuoteData->last_name,
            "customer_id" => $getCarQuoteData->customer_id,
            "email" => $getCarQuoteData->email,
            "mobile_no" => $getCarQuoteData->mobile_no,
            "uuid" => $quoteUuid,
            "code" => 'CAR-'.$quoteUuid,
            "source" => 'Renewal_upload',
            "currently_insured_with" => $getCarQuoteData->currently_insured_with,
            "car_type_insurance_id" => $getCarQuoteData->car_type_insurance_id,
            "year_of_manufacture" => $getCarQuoteData->year_of_manufacture,
            "car_make_id" => $getCarQuoteData->car_make_id,
            "car_model_id" => $getCarQuoteData->car_model_id,
            "cylinder" => $getCarQuoteData->cylinder,
            "vehicle_category" => $getCarQuoteData->vehicle_category,
            "additional_notes" => $getCarQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId,
            "renewal_batch" => $batchNumber,
            "previous_quote_policy_number" => $policy,
            "quote_status_id" => $newLeadId,
            "is_quote_locked" => true,
            "other_email_addresses" => $otherEmailIds,
            "renewal_import_code" => $renewalImportCode,
            "previous_policy_expiry_date" => $endDate,
            "previous_quote_policy_premium" => $grossPremium
        ]);
        $createRenewalQuote->save();
        $this->checkAMLService->checkAML($getCarQuoteData->first_name, $getCarQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalHealthQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode, $endDate, $grossPremium)
    {
        $getHealthQuoteData = HealthQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Health);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Health);

        $createRenewalQuote = new HealthQuote([
            "first_name" => $getHealthQuoteData->first_name,
            "last_name" => $getHealthQuoteData->last_name,
            "customer_id" => $getHealthQuoteData->customer_id,
            "email" => $getHealthQuoteData->email,
            "mobile_no" => $getHealthQuoteData->mobile_no,
            "uuid" => $quoteUuid,
            "code" => 'HEA-'.$quoteUuid,
            "source" => 'Renewal_upload',
            "additional_notes" => $getHealthQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId,
            "renewal_batch" => $batchNumber,
            "previous_quote_policy_number" => $policy,
            "quote_status_id" => $newLeadId,
            "other_email_addresses" => $otherEmailIds,
            "renewal_import_code" => $renewalImportCode,
            "previous_policy_expiry_date" => $endDate,
            "previous_quote_policy_premium" => $grossPremium
        ]);
        $createRenewalQuote->save();
        $this->checkAMLService->checkAML($getHealthQuoteData->first_name, $getHealthQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalHomeQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode, $endDate, $grossPremium)
    {
        $getHomeQuoteData = HomeQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Home);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Home);

        $createRenewalQuote = new HomeQuote([
            "first_name" => $getHomeQuoteData->first_name,
            "last_name" => $getHomeQuoteData->last_name,
            "customer_id" => $getHomeQuoteData->customer_id,
            "email" => $getHomeQuoteData->email,
            "mobile_no" => $getHomeQuoteData->mobile_no,
            "uuid" => $quoteUuid,
            "code" => 'HOM-'.$quoteUuid,
            "source" => 'Renewal_upload',
            "additional_notes" => $getHomeQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId,
            "renewal_batch" => $batchNumber,
            "previous_quote_policy_number" => $policy,
            "quote_status_id" => $newLeadId,
            "other_email_addresses" => $otherEmailIds,
            "renewal_import_code" => $renewalImportCode,
            "previous_policy_expiry_date" => $endDate,
            "previous_quote_policy_premium" => $grossPremium
        ]);
        $createRenewalQuote->save();
        $this->checkAMLService->checkAML($getHomeQuoteData->first_name, $getHomeQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalLifeQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode, $endDate, $grossPremium)
    {
        $getLifeQuoteData = LifeQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Life);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Life);
        $createRenewalQuote = new LifeQuote([
            "first_name" => $getLifeQuoteData->first_name,
            "last_name" => $getLifeQuoteData->last_name,
            "customer_id" => $getLifeQuoteData->customer_id,
            "email" => $getLifeQuoteData->email,
            "mobile_no" => $getLifeQuoteData->mobile_no,
            "uuid" => $quoteUuid,
            "code" => 'LIF-'.$quoteUuid,
            "source" => 'Renewal_upload',
            "additional_notes" => $getLifeQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId,
            "renewal_batch" => $batchNumber,
            "previous_quote_policy_number" => $policy,
            "quote_status_id" => $newLeadId,
            "other_email_addresses" => $otherEmailIds,
            "renewal_import_code" => $renewalImportCode,
            "previous_policy_expiry_date" => $endDate,
            "previous_quote_policy_premium" => $grossPremium
        ]);
        $createRenewalQuote->save();
        $this->checkAMLService->checkAML($getLifeQuoteData->first_name, $getLifeQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalTravelQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode, $endDate, $grossPremium)
    {
        $getTravelQuoteData = TravelQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Travel);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Travel);
        $createRenewalQuote = new TravelQuote([
            "first_name" => $getTravelQuoteData->first_name,
            "last_name" => $getTravelQuoteData->last_name,
            "customer_id" => $getTravelQuoteData->customer_id,
            "email" => $getTravelQuoteData->email,
            "mobile_no" => $getTravelQuoteData->mobile_no,
            "uuid" => $quoteUuid,
            "code" => 'TRA-'.$quoteUuid,
            "source" => 'Renewal_upload',
            "additional_notes" => $getTravelQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId,
            "renewal_batch" => $batchNumber,
            "previous_quote_policy_number" => $policy,
            "quote_status_id" => $newLeadId,
            "other_email_addresses" => $otherEmailIds,
            "renewal_import_code" => $renewalImportCode,
            "previous_policy_expiry_date" => $endDate,
            "previous_quote_policy_premium" => $grossPremium
        ]);
        $createRenewalQuote->save();
        $this->checkAMLService->checkAML($getTravelQuoteData->first_name, $getTravelQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalYachtQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode, $endDate, $grossPremium)
    {
        $getYachtQuoteData = YachtQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Yacht);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Yacht);

        $createRenewalQuote = new YachtQuote([
            "first_name" => $getYachtQuoteData->first_name,
            "last_name" => $getYachtQuoteData->last_name,
            "customer_id" => $getYachtQuoteData->customer_id,
            "email" => $getYachtQuoteData->email,
            "mobile_no" => $getYachtQuoteData->mobile_no,
            "uuid" => $quoteUuid,
            "code" => 'YAC-'.$quoteUuid,
            "source" => 'Renewal_upload',
            "additional_notes" => $getYachtQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId,
            "renewal_batch" => $batchNumber,
            "previous_quote_policy_number" => $policy,
            "quote_status_id" => $newLeadId,
            "other_email_addresses" => $otherEmailIds,
            "renewal_import_code" => $renewalImportCode,
            "previous_policy_expiry_date" => $endDate,
            "previous_quote_policy_premium" => $grossPremium
        ]);
        $createRenewalQuote->save();
        $this->checkAMLService->checkAML($getYachtQuoteData->first_name, $getYachtQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createRenewalDumpRecord($type, $id, $data)
    {
        $newRecord = new RenewalsDump([
            'quote_type' => $type,
            'cdb_id' => $id,
            'data' => json_encode($data)
        ]);
        $newRecord->save();
    }

    function getquoteStatusIdbyCode($quoteStatus)
    {
        return QuoteStatus::where('code', '=', $quoteStatus)->value('id');
    }

    function generateRandomString() {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 8; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
     }

     function updateExistingCarQuote($quoteData, $renewalImportCode)
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
            if($carTypeOfInsuranceInstance){
                $carTypeOfInsurance = $carTypeOfInsuranceInstance->id;
            }
        }

        // Previous Car Lead
        $updateCarQuote = CarQuote::where('renewal_import_code', $renewalImportCode)
        ->where('policy_number', $quoteData->policy)->first();

        $previousAdvisorEmail = 'Previous Advisor Email Id : '. $quoteData->pAdvisor;
        $carMakeModel = 'Car Make/Model/Year : '. $quoteData->make.' '.$quoteData->year;
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

        // Renewal Car Lead
        $updateCarQuoteRenewal = CarQuote::where('renewal_import_code', $renewalImportCode)
        ->where('previous_quote_policy_number', $quoteData->policy)->first();

        $notes = $updateCarQuoteRenewal->additional_notes.' - '.$carMakeModel.' - '.$quoteData->notes;

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
    }

    public function renewalBatchEmailProcess($batchLeadId, $batchEmailId)
    {
        $carQuote = CarQuote::find($batchLeadId);
        $ecomUrl = Config::get('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$carQuote->uuid;

        if($carQuote->previous_quote_id != null) {

            $primaryEmail = $carQuote->email;
            $otherEmails = $carQuote->other_email_addresses;

            if($otherEmails) {
                $allEmails = $primaryEmail.",".$otherEmails;
            } else {
                $allEmails = $primaryEmail;
            }

            $finalEmails = explode(",",$allEmails);

            foreach($finalEmails as $finalEmail) {

                $previousCarQuote = CarQuote::find($carQuote->previous_quote_id);
                Log::channel('daily')->info("CDBID: ".$carQuote->code." RenewalBatch: ".$carQuote->renewal_batch." PreviousQuoteId: ".$carQuote->previous_quote_id." Email: ".$carQuote->email." finalEmail: ".$finalEmail." ecomUrl: ".$ecomUrl." renewal_expiry_date: ".$previousCarQuote->renewal_expiry_date);

                if(isset($previousCarQuote->renewal_expiry_date)) {
                    $renewalExpiryDate = date('d/m/Y', strtotime($previousCarQuote->renewal_expiry_date));
                } else {
                    $renewalExpiryDate = '';
                }

                if(isset($carQuote->car_type_insurance_id)) {
                    $carTypeInsurance = CarTypeInsurance::where('id', '=', $carQuote->car_type_insurance_id)->value('text');
                } else {
                    $carTypeInsurance = '';
                }

                if(isset($carQuote->car_make_id)) {
                    $carMake = CarMake::where('id', '=', $carQuote->car_make_id)->value('text');
                } else {
                    $carMake = '';
                }

                if(isset($carQuote->car_model_id)) {
                    $carModel = CarModel::where('id', '=', $carQuote->car_model_id)->value('text');
                } else {
                    $carModel = '';
                }

                if(isset($carQuote->advisor_id)) {
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
                $emailData = array(
                    'customerName' => $carQuote->first_name . ' ' . $carQuote->last_name,
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
                    'ecomUrl' => $ecomUrl
                );

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
            $emailTemplateId = (int)Config::get('constants.SIB_CAR_RENEWALS_TEMPLATE_ID');
            $tag = 'renewal';

            if($appEnv == 'production') {
                $tag = $tag;
            } else {
                $tag = $appEnv.'-'.$tag;
            }

            $headers = [
                'Accept' => 'application/json',
                'api-key' => $apiKey,
                'Content-Type' => 'application/json'
            ];

            $body = json_encode([
                "to" => array([
                    "email" => $emailData['customerEmail'],
                    "name" => $emailData['customerName'],
                ]),
                "bcc" => array([
                    "email" => $emailData['advisorEmail'],
                    "name" => $emailData['advisorName'],
                ]),
                "templateId" => $emailTemplateId,
                "params" => [
                    "customerName" => $emailData['customerName'],
                    "customerEmail" => $emailData['customerEmail'],
                    "cdbId" => $emailData['cdbId'],
                    "policyNumber" => $emailData['policyNumber'],
                    "expiryDate" => $emailData['expiryDate'],
                    "insurerName" => $emailData['insurerName'],
                    "planType" => $emailData['planType'],
                    "carMake" => $emailData['carMake'],
                    "carModel" => $emailData['carModel'],
                    "advisorName" => $emailData['advisorName'],
                    "advisorEmail" => $emailData['advisorEmail'],
                    "advisorMobile" => $emailData['advisorMobile'],
                    "advisorLandline" => $emailData['advisorLandline'],
                    "ecomUrl" => $emailData['ecomUrl'],
                ],
                "replyTo" => [
                    "email" => $emailData['advisorEmail'],
                ],
                "tags" => [
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
            $getResponse = json_encode($clientRequest->getStatusCode()." ".$clientRequest->getBody()->getContents());

            if($getStatusCode == 201) {
                $isEmailSent = 1;
            } else {
                $errorMessage = "SIB Error:  ".$getStatusCode." ".$emailData['customerEmail']." ".get_class();
                Log::channel('daily')->error("message: ".$errorMessage);
                $isEmailSent = 0;
            }
        }
        catch(Exception $ex) {
            $errorMessage = "SIB Failed Error: ".$ex->getCode()." ".$ex->getMessage()." ".get_class();
            Log::channel('daily')->info("message: ".$errorMessage);
            $getStatusCode = $ex->getCode();
            $getResponse = json_encode($ex->getCode()." ".$ex->getMessage());
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
        if($renewalsBatchStatus) { // if record exists, update the number of rows uploaded
            $renewalsBatchStatus->total_sent = $renewalsBatchStatus->total_sent + 1;
            $renewalsBatchStatus->save();
        }
        if(($renewalsBatchStatus->total_sent + $renewalsBatchStatus->total_bounced) == $renewalsBatchStatus->total_leads) { // if all records are uploaded, update the status to completed
            $renewalsBatchStatus->status = ProcessStatusCode::COMPLETED;
            $renewalsBatchStatus->save();
        }
    }
}
