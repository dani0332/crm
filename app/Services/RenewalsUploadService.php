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
use App\Services\RenewalsAddonServices;
use App\Services\CheckAmlService;
use App\Services\CapiRequestService;
use App\Enums\quoteTypeCode;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteTypeId;
use App\Models\QuoteStatus;
use App\Models\RenewalsUploadLeads;

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

        if ($quoteType == 'BIK') {
            $this->createNewBikeQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode);
            $this->updateRenewalUploadLeadRecord($fileName);
        }
        if ($quoteType == 'BUS') {
            $this->createNewBusinessQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode);
            $this->updateRenewalUploadLeadRecord($fileName);
        }
        if ($quoteType == 'CAR') {
            if ($uploadType == 'Create') {
                $this->createNewCarQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode);
                $this->updateRenewalUploadLeadRecord($fileName);
            }
            if ($uploadType == 'Update') {
                $this->updateExistingCarQuote($quoteData, $renewalImportCode);
                $this->updateRenewalUploadLeadRecord($fileName);
            }
        }
        
        if ($quoteType == 'HEA') {
            $this->createNewHealthQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode);
            $this->updateRenewalUploadLeadRecord($fileName);
        }
        if ($quoteType == 'HOM') {
            $this->createNewHomeQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode);
            $this->updateRenewalUploadLeadRecord($fileName);
        }
        if ($quoteType == 'LIF') {
            $this->createNewLifeQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode);
            $this->updateRenewalUploadLeadRecord($fileName);
        }
        if ($quoteType == 'TRA') {
            $this->createNewTravelQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode);
            $this->updateRenewalUploadLeadRecord($fileName);
        }
        if ($quoteType == 'YAC') {
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
                $record->status = 'Completed';
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
            "source" => $quoteData->source,
            "currently_insured_with" => $quoteData->insurer,
            "year_of_manufacture" => $quoteData->year,
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes,
            "renewal_batch" => $quoteData->batch,
            "renewal_expiry_date" => $quoteData->endDate,
            "quote_status_id" => $transApprovedId,
            "other_email_addresses" => $quoteData->other_email_ids,
            "renewal_import_code" => $renewalImportCode
        ]);
        $newBikeQuote->save();

        $createRenewalQuote = $this->createNewRenewalBikeQuote($newBikeQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode);
        $this->createRenewalDumpRecord('Bike', $createRenewalQuote, $quoteData);
    }

    function createNewBusinessQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Business);

        $newBusinessQuote = new BusinessQuote([
            "first_name" => $quoteData->first_name,
            "last_name" => $quoteData->last_name,
            "customer_id" => $quoteData->customer_id,
            "email" => $quoteData->email,
            "mobile_no" => $quoteData->phoneNumber,
            "uuid" => $quoteUuid,
            "code" => 'BUS-'.$quoteUuid,
            "source" => $quoteData->source,
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes,
            "renewal_batch" => $quoteData->batch,
            "renewal_expiry_date" => $quoteData->endDate,
            "quote_status_id" => $transApprovedId,
            "other_email_addresses" => $quoteData->other_email_ids,
            "renewal_import_code" => $renewalImportCode
        ]);
        $newBusinessQuote->save();

        $createRenewalQuote = $this->createNewRenewalBusinessQuote($newBusinessQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode);
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
            "source" => $quoteData->source,
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
            "renewal_import_code" => $renewalImportCode
        ]);
        $newCarQuote->save();

        $createRenewalQuote = $this->createNewRenewalCarQuote($newCarQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode);
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
            "source" => $quoteData->source,
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes,
            "renewal_batch" => $quoteData->batch,
            "renewal_expiry_date" => $quoteData->endDate,
            "quote_status_id" => $transApprovedId,
            "other_email_addresses" => $quoteData->other_email_ids,
            "renewal_import_code" => $renewalImportCode
        ]);
        $newHealthQuote->save();

        $createRenewalQuote = $this->createNewRenewalHealthQuote($newHealthQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode);
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
            "source" => $quoteData->source,
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes,
            "renewal_batch" => $quoteData->batch,
            "renewal_expiry_date" => $quoteData->endDate,
            "quote_status_id" => $transApprovedId,
            "other_email_addresses" => $quoteData->other_email_ids,
            "renewal_import_code" => $renewalImportCode
        ]);
        $newHomeQuote->save();

        $createRenewalQuote = $this->createNewRenewalHomeQuote($newHomeQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode);
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
            "source" => $quoteData->source,
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes,
            "renewal_batch" => $quoteData->batch,
            "renewal_expiry_date" => $quoteData->endDate,
            "quote_status_id" => $transApprovedId,
            "other_email_addresses" => $quoteData->other_email_ids,
            "renewal_import_code" => $renewalImportCode
        ]);
        $newLifeQuote->save();

        $createRenewalQuote = $this->createNewRenewalLifeQuote($newLifeQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode);
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
            "source" => $quoteData->source,
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes,
            "renewal_batch" => $quoteData->batch,
            "renewal_expiry_date" => $quoteData->endDate,
            "quote_status_id" => $transApprovedId,
            "other_email_addresses" => $quoteData->other_email_ids,
            "renewal_import_code" => $renewalImportCode
        ]);
        $newTravelQuote->save();

        $createRenewalQuote = $this->createNewRenewalTravelQuote($newTravelQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode);
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
            "source" => $quoteData->source,
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes,
            "renewal_batch" => $quoteData->batch,
            "renewal_expiry_date" => $quoteData->endDate,
            "quote_status_id" => $transApprovedId,
            "other_email_addresses" => $quoteData->other_email_ids,
            "renewal_import_code" => $renewalImportCode
        ]);
        $newYachtQuote->save();

        $createRenewalQuote = $this->createNewRenewalYachtQuote($newYachtQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode);
        $this->createRenewalDumpRecord('Yacht', $createRenewalQuote, $quoteData);
    }

    function createNewRenewalBikeQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode)
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
            "source" => $getBikeQuoteData->source,
            "currently_insured_with" => $getBikeQuoteData->currently_insured_with,
            "year_of_manufacture" => $getBikeQuoteData->year_of_manufacture,
            "additional_notes" => $getBikeQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId,
            "renewal_batch" => $batchNumber,
            "previous_quote_policy_number" => $policy,
            "quote_status_id" => $newLeadId,
            "other_email_addresses" => $otherEmailIds,
            "renewal_import_code" => $renewalImportCode
        ]);
        $createRenewalQuote->save();

        $this->checkAMLService->checkAML($getBikeQuoteData->first_name, $getBikeQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalBusinessQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode)
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
            "source" => $getBusinessQuoteData->source,
            "additional_notes" => $getBusinessQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId,
            "renewal_batch" => $batchNumber,
            "previous_quote_policy_number" => $policy,
            "quote_status_id" => $newLeadId,
            "other_email_addresses" => $otherEmailIds,
            "renewal_import_code" => $renewalImportCode
        ]);
        $createRenewalQuote->save();

        $this->checkAMLService->checkAML($getBusinessQuoteData->first_name, $getBusinessQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalCarQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode)
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
            "source" => $getCarQuoteData->source,
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
            "renewal_import_code" => $renewalImportCode
        ]);
        $createRenewalQuote->save();

        $this->checkAMLService->checkAML($getCarQuoteData->first_name, $getCarQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalHealthQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode)
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
            "source" => $getHealthQuoteData->source,
            "additional_notes" => $getHealthQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId,
            "renewal_batch" => $batchNumber,
            "previous_quote_policy_number" => $policy,
            "quote_status_id" => $newLeadId,
            "other_email_addresses" => $otherEmailIds,
            "renewal_import_code" => $renewalImportCode
        ]);
        $createRenewalQuote->save();

        $this->checkAMLService->checkAML($getHealthQuoteData->first_name, $getHealthQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalHomeQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode)
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
            "source" => $getHomeQuoteData->source,
            "additional_notes" => $getHomeQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId,
            "renewal_batch" => $batchNumber,
            "previous_quote_policy_number" => $policy,
            "quote_status_id" => $newLeadId,
            "other_email_addresses" => $otherEmailIds,
            "renewal_import_code" => $renewalImportCode
        ]);
        $createRenewalQuote->save();

        $this->checkAMLService->checkAML($getHomeQuoteData->first_name, $getHomeQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalLifeQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode)
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
            "source" => $getLifeQuoteData->source,
            "additional_notes" => $getLifeQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId,
            "renewal_batch" => $batchNumber,
            "previous_quote_policy_number" => $policy,
            "quote_status_id" => $newLeadId,
            "other_email_addresses" => $otherEmailIds,
            "renewal_import_code" => $renewalImportCode
        ]);
        $createRenewalQuote->save();

        $this->checkAMLService->checkAML($getLifeQuoteData->first_name, $getLifeQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalTravelQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode)
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
            "source" => $getTravelQuoteData->source,
            "additional_notes" => $getTravelQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId,
            "renewal_batch" => $batchNumber,
            "previous_quote_policy_number" => $policy,
            "quote_status_id" => $newLeadId,
            "other_email_addresses" => $otherEmailIds,
            "renewal_import_code" => $renewalImportCode
        ]);
        $createRenewalQuote->save();

        $this->checkAMLService->checkAML($getTravelQuoteData->first_name, $getTravelQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalYachtQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode)
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
            "source" => $getYachtQuoteData->source,
            "additional_notes" => $getYachtQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId,
            "renewal_batch" => $batchNumber,
            "previous_quote_policy_number" => $policy,
            "quote_status_id" => $newLeadId,
            "other_email_addresses" => $otherEmailIds,
            "renewal_import_code" => $renewalImportCode
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
}
