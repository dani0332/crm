<?php

namespace App\Services;

use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\BikeQuote;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\TravelQuote;
use App\Models\YachtQuote;

class RenewalsUploadServiceBk
{
    public function createNewBikeQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode, $currentUserId)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Bike);

        $newBikeQuote = new BikeQuote([
            'first_name' => $quoteData->first_name,
            'last_name' => $quoteData->last_name,
            'customer_id' => $quoteData->customer_id,
            'email' => $quoteData->email,
            'mobile_no' => $quoteData->phoneNumber,
            'uuid' => $quoteUuid,
            'code' => 'BIK-'.$quoteUuid,
            'source' => 'Renewal_upload',
            'currently_insured_with' => $quoteData->insurer,
            'year_of_manufacture' => $quoteData->year,
            'policy_number' => $quoteData->policy,
            'advisor_id' => $advisorId,
            'additional_notes' => $quoteData->notes,
            'renewal_batch' => $quoteData->batch,
            'renewal_expiry_date' => $quoteData->endDate,
            'quote_status_id' => $transApprovedId,
            'other_email_addresses' => $quoteData->other_email_ids,
            'renewal_import_code' => $renewalImportCode,
            'premium' => $quoteData->gross_premium,
        ]);
        $newBikeQuote->save();
        $createRenewalQuote = $this->createNewRenewalBikeQuote($newBikeQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode, $quoteData->endDate, $quoteData->gross_premium, $currentUserId);
        $this->createRenewalDumpRecord('Bike', $createRenewalQuote, $quoteData);
        $this->updateAdvisorAssignedDateTime('BikeQuoteRequestDetail', $newBikeQuote->id, 'bike_quote_request_id', $currentUserId, $advisorId);
    }

    public function createNewBusinessQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode, $currentUserId)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Business);

        if (isset($quoteData->product_type)) {
            $businessSubline = $this->renewalsAddonService->getBusinessSublineInsurance($quoteData->product_type);
            $businessSublineInsuranceId = isset($businessSubline->id) ? $businessSubline->id : null;
        } else {
            $businessSublineInsuranceId = null;
        }

        $newBusinessQuote = new BusinessQuote([
            'first_name' => $quoteData->first_name,
            'last_name' => $quoteData->last_name,
            'customer_id' => $quoteData->customer_id,
            'email' => $quoteData->email,
            'mobile_no' => $quoteData->phoneNumber,
            'uuid' => $quoteUuid,
            'code' => 'BUS-'.$quoteUuid,
            'source' => 'Renewal_upload',
            'policy_number' => $quoteData->policy,
            'advisor_id' => $advisorId,
            'additional_notes' => $quoteData->notes,
            'renewal_batch' => $quoteData->batch,
            'renewal_expiry_date' => $quoteData->endDate,
            'quote_status_id' => $transApprovedId,
            'other_email_addresses' => $quoteData->other_email_ids,
            'renewal_import_code' => $renewalImportCode,
            'premium' => $quoteData->gross_premium,
            'business_type_of_insurance_id' => $businessSublineInsuranceId,
        ]);
        $newBusinessQuote->save();
        $createRenewalQuote = $this->createNewRenewalBusinessQuote($newBusinessQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode, $businessSublineInsuranceId, $quoteData->endDate, $quoteData->gross_premium, $currentUserId);
        $this->createRenewalDumpRecord('Business', $createRenewalQuote, $quoteData);
        $this->updateAdvisorAssignedDateTime('BusinessQuoteRequestDetail', $newBusinessQuote->id, 'business_quote_request_id', $currentUserId, $advisorId);
    }

    public function createNewCarQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode, $currentUserId)
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
            if ($carTypeOfInsuranceInstance) {
                $carTypeOfInsurance = $carTypeOfInsuranceInstance->id;
            }
        }

        $newCarQuote = new CarQuote([
            'first_name' => $quoteData->first_name,
            'last_name' => $quoteData->last_name,
            'customer_id' => $quoteData->customer_id,
            'email' => $quoteData->email,
            'mobile_no' => $quoteData->phoneNumber,
            'uuid' => $quoteUuid,
            'code' => 'CAR-'.$quoteUuid,
            'source' => 'Renewal_upload',
            'currently_insured_with' => $quoteData->insurer,
            'car_type_insurance_id' => $carTypeOfInsurance,
            'year_of_manufacture' => $quoteData->year ?? null,
            'car_make_id' => $carMake->id ?? null,
            'car_model_id' => $carModel->id ?? null,
            'policy_number' => $quoteData->policy,
            'cylinder' => $carModel->cylinder ?? null,
            'vehicle_category' => $vehicleType->category ?? null,
            'advisor_id' => $advisorId,
            'additional_notes' => $quoteData->notes,
            'renewal_batch' => $quoteData->batch,
            'quote_status_id' => $transApprovedId,
            'renewal_expiry_date' => $quoteData->endDate,
            'other_email_addresses' => $quoteData->other_email_ids,
            'renewal_import_code' => $renewalImportCode,
            'premium' => $quoteData->gross_premium,
        ]);
        $newCarQuote->save();
        $createRenewalQuote = $this->createNewRenewalCarQuote($newCarQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode, $quoteData->endDate, $quoteData->gross_premium, $currentUserId);
        $this->createRenewalDumpRecord('Car', $createRenewalQuote, $quoteData);
        $this->updateAdvisorAssignedDateTime('CarQuoteRequestDetail', $newCarQuote->id, 'car_quote_request_id', $currentUserId, $advisorId);
    }

    public function createNewHealthQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode, $currentUserId)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Health);

        $newHealthQuote = new HealthQuote([
            'first_name' => $quoteData->first_name,
            'last_name' => $quoteData->last_name,
            'customer_id' => $quoteData->customer_id,
            'email' => $quoteData->email,
            'mobile_no' => $quoteData->phoneNumber,
            'uuid' => $quoteUuid,
            'code' => 'HEA-'.$quoteUuid,
            'source' => 'Renewal_upload',
            'policy_number' => $quoteData->policy,
            'advisor_id' => $advisorId,
            'additional_notes' => $quoteData->notes,
            'renewal_batch' => $quoteData->batch,
            'renewal_expiry_date' => $quoteData->endDate,
            'quote_status_id' => $transApprovedId,
            'other_email_addresses' => $quoteData->other_email_ids,
            'renewal_import_code' => $renewalImportCode,
            'premium' => $quoteData->gross_premium,
        ]);
        $newHealthQuote->save();
        $createRenewalQuote = $this->createNewRenewalHealthQuote($newHealthQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode, $quoteData->endDate, $quoteData->gross_premium, $currentUserId);
        $this->createRenewalDumpRecord('Health', $createRenewalQuote, $quoteData);
        $this->updateAdvisorAssignedDateTime('HealthQuoteRequestDetail', $newHealthQuote->id, 'health_quote_request_id', $currentUserId, $advisorId);
    }

    public function createNewHomeQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode, $currentUserId)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Home);
        $newHomeQuote = new HomeQuote([
            'first_name' => $quoteData->first_name,
            'last_name' => $quoteData->last_name,
            'customer_id' => $quoteData->customer_id,
            'email' => $quoteData->email,
            'mobile_no' => $quoteData->phoneNumber,
            'uuid' => $quoteUuid,
            'code' => 'HOM-'.$quoteUuid,
            'source' => 'Renewal_upload',
            'policy_number' => $quoteData->policy,
            'advisor_id' => $advisorId,
            'additional_notes' => $quoteData->notes,
            'renewal_batch' => $quoteData->batch,
            'renewal_expiry_date' => $quoteData->endDate,
            'quote_status_id' => $transApprovedId,
            'other_email_addresses' => $quoteData->other_email_ids,
            'renewal_import_code' => $renewalImportCode,
            'premium' => $quoteData->gross_premium,
        ]);
        $newHomeQuote->save();
        $createRenewalQuote = $this->createNewRenewalHomeQuote($newHomeQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode, $quoteData->endDate, $quoteData->gross_premium, $currentUserId);
        $this->createRenewalDumpRecord('Home', $createRenewalQuote, $quoteData);
        $this->updateAdvisorAssignedDateTime('HomeQuoteRequestDetail', $newHomeQuote->id, 'home_quote_request_id', $currentUserId, $advisorId);
    }

    public function createNewLifeQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode, $currentUserId)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Life);
        $newLifeQuote = new LifeQuote([
            'first_name' => $quoteData->first_name,
            'last_name' => $quoteData->last_name,
            'customer_id' => $quoteData->customer_id,
            'email' => $quoteData->email,
            'mobile_no' => $quoteData->phoneNumber,
            'uuid' => $quoteUuid,
            'code' => 'LIF-'.$quoteUuid,
            'source' => 'Renewal_upload',
            'policy_number' => $quoteData->policy,
            'advisor_id' => $advisorId,
            'additional_notes' => $quoteData->notes,
            'renewal_batch' => $quoteData->batch,
            'renewal_expiry_date' => $quoteData->endDate,
            'quote_status_id' => $transApprovedId,
            'other_email_addresses' => $quoteData->other_email_ids,
            'renewal_import_code' => $renewalImportCode,
            'premium' => $quoteData->gross_premium,
        ]);
        $newLifeQuote->save();
        $createRenewalQuote = $this->createNewRenewalLifeQuote($newLifeQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode, $quoteData->endDate, $quoteData->gross_premium, $currentUserId);
        $this->createRenewalDumpRecord('Life', $createRenewalQuote, $quoteData);
        $this->updateAdvisorAssignedDateTime('LifeQuoteRequestDetail', $newLifeQuote->id, 'life_quote_request_id', $currentUserId, $advisorId);
    }

    public function createNewTravelQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode, $currentUserId)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Travel);

        $newTravelQuote = new TravelQuote([
            'first_name' => $quoteData->first_name,
            'last_name' => $quoteData->last_name,
            'customer_id' => $quoteData->customer_id,
            'email' => $quoteData->email,
            'mobile_no' => $quoteData->phoneNumber,
            'uuid' => $quoteUuid,
            'code' => 'TRA-'.$quoteUuid,
            'source' => 'Renewal_upload',
            'policy_number' => $quoteData->policy,
            'advisor_id' => $advisorId,
            'additional_notes' => $quoteData->notes,
            'renewal_batch' => $quoteData->batch,
            'renewal_expiry_date' => $quoteData->endDate,
            'quote_status_id' => $transApprovedId,
            'other_email_addresses' => $quoteData->other_email_ids,
            'renewal_import_code' => $renewalImportCode,
            'premium' => $quoteData->gross_premium,
        ]);
        $newTravelQuote->save();
        $createRenewalQuote = $this->createNewRenewalTravelQuote($newTravelQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode, $quoteData->endDate, $quoteData->gross_premium, $currentUserId);
        $this->createRenewalDumpRecord('Travel', $createRenewalQuote, $quoteData);
        $this->updateAdvisorAssignedDateTime('TravelQuoteRequestDetail', $newTravelQuote->id, 'travel_quote_request_id', $currentUserId, $advisorId);
    }

    public function createNewYachtQuoute($quoteData, $transApprovedId, $newLeadId, $renewalImportCode, $currentUserId)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Yacht);
        $newYachtQuote = new YachtQuote([
            'first_name' => $quoteData->first_name,
            'last_name' => $quoteData->last_name,
            'customer_id' => $quoteData->customer_id,
            'email' => $quoteData->email,
            'mobile_no' => $quoteData->phoneNumber,
            'uuid' => $quoteUuid,
            'code' => 'YAC-'.$quoteUuid,
            'source' => 'Renewal_upload',
            'policy_number' => $quoteData->policy,
            'advisor_id' => $advisorId,
            'additional_notes' => $quoteData->notes,
            'renewal_batch' => $quoteData->batch,
            'renewal_expiry_date' => $quoteData->endDate,
            'quote_status_id' => $transApprovedId,
            'other_email_addresses' => $quoteData->other_email_ids,
            'renewal_import_code' => $renewalImportCode,
            'premium' => $quoteData->gross_premium,
        ]);
        $newYachtQuote->save();
        $createRenewalQuote = $this->createNewRenewalYachtQuote($newYachtQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->policy, $newLeadId, $quoteData->other_email_ids, $renewalImportCode, $quoteData->endDate, $quoteData->gross_premium, $currentUserId);
        $this->createRenewalDumpRecord('Yacht', $createRenewalQuote, $quoteData);
        $this->updateAdvisorAssignedDateTime('YachtQuoteRequestDetail', $newYachtQuote->id, 'yacht_quote_request_id', $currentUserId, $advisorId);
    }

    public function createNewRenewalBikeQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode, $endDate, $grossPremium, $currentUserId)
    {
        $getBikeQuoteData = BikeQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Bike);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Bike);
        $createRenewalQuote = new BikeQuote([
            'first_name' => $getBikeQuoteData->first_name,
            'last_name' => $getBikeQuoteData->last_name,
            'customer_id' => $getBikeQuoteData->customer_id,
            'email' => $getBikeQuoteData->email,
            'mobile_no' => $getBikeQuoteData->mobile_no,
            'uuid' => $quoteUuid,
            'code' => 'BIK-'.$quoteUuid,
            'source' => 'Renewal_upload',
            'currently_insured_with' => $getBikeQuoteData->currently_insured_with,
            'year_of_manufacture' => $getBikeQuoteData->year_of_manufacture,
            'additional_notes' => $getBikeQuoteData->additional_notes,
            'previous_quote_id' => $quoteId,
            'advisor_id' => $advisorId,
            'renewal_batch' => $batchNumber,
            'previous_quote_policy_number' => $policy,
            'quote_status_id' => $newLeadId,
            'other_email_addresses' => $otherEmailIds,
            'renewal_import_code' => $renewalImportCode,
            'previous_policy_expiry_date' => $endDate,
            'previous_quote_policy_premium' => $grossPremium,
            'policy_number' => null,
        ]);
        $createRenewalQuote->save();
        $this->checkAMLService->checkAML($getBikeQuoteData->first_name, $getBikeQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        $this->updateAdvisorAssignedDateTime('BikeQuoteRequestDetail', $createRenewalQuote->id, 'bike_quote_request_id', $currentUserId, $advisorId);

        return $createRenewalQuote->id;
    }

    public function createNewRenewalBusinessQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode, $businessSublineInsuranceId, $endDate, $grossPremium, $currentUserId)
    {
        $getBusinessQuoteData = BusinessQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Business);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Business);

        $createRenewalQuote = new BusinessQuote([
            'first_name' => $getBusinessQuoteData->first_name,
            'last_name' => $getBusinessQuoteData->last_name,
            'customer_id' => $getBusinessQuoteData->customer_id,
            'email' => $getBusinessQuoteData->email,
            'mobile_no' => $getBusinessQuoteData->mobile_no,
            'uuid' => $quoteUuid,
            'code' => 'BUS-'.$quoteUuid,
            'source' => 'Renewal_upload',
            'additional_notes' => $getBusinessQuoteData->additional_notes,
            'previous_quote_id' => $quoteId,
            'advisor_id' => $advisorId,
            'renewal_batch' => $batchNumber,
            'previous_quote_policy_number' => $policy,
            'quote_status_id' => $newLeadId,
            'other_email_addresses' => $otherEmailIds,
            'renewal_import_code' => $renewalImportCode,
            'business_type_of_insurance_id' => $businessSublineInsuranceId,
            'previous_policy_expiry_date' => $endDate,
            'previous_quote_policy_premium' => $grossPremium,
            'policy_number' => null,
        ]);
        $createRenewalQuote->save();
        $this->checkAMLService->checkAML($getBusinessQuoteData->first_name, $getBusinessQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        $this->updateAdvisorAssignedDateTime('BusinessQuoteRequestDetail', $createRenewalQuote->id, 'business_quote_request_id', $currentUserId, $advisorId);

        return $createRenewalQuote->id;
    }

    public function createNewRenewalCarQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode, $endDate, $grossPremium, $currentUserId)
    {
        $getCarQuoteData = CarQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Car);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Car);

        $createRenewalQuote = new CarQuote([
            'first_name' => $getCarQuoteData->first_name,
            'last_name' => $getCarQuoteData->last_name,
            'customer_id' => $getCarQuoteData->customer_id,
            'email' => $getCarQuoteData->email,
            'mobile_no' => $getCarQuoteData->mobile_no,
            'uuid' => $quoteUuid,
            'code' => 'CAR-'.$quoteUuid,
            'source' => 'Renewal_upload',
            'currently_insured_with' => $getCarQuoteData->currently_insured_with,
            'car_type_insurance_id' => $getCarQuoteData->car_type_insurance_id,
            'year_of_manufacture' => $getCarQuoteData->year_of_manufacture,
            'car_make_id' => $getCarQuoteData->car_make_id,
            'car_model_id' => $getCarQuoteData->car_model_id,
            'cylinder' => $getCarQuoteData->cylinder,
            'vehicle_category' => $getCarQuoteData->vehicle_category,
            'additional_notes' => $getCarQuoteData->additional_notes,
            'previous_quote_id' => $quoteId,
            'advisor_id' => $advisorId,
            'renewal_batch' => $batchNumber,
            'previous_quote_policy_number' => $policy,
            'quote_status_id' => $newLeadId,
            'is_quote_locked' => true,
            'other_email_addresses' => $otherEmailIds,
            'renewal_import_code' => $renewalImportCode,
            'previous_policy_expiry_date' => $endDate,
            'previous_quote_policy_premium' => $grossPremium,
            'policy_number' => null,
        ]);
        $createRenewalQuote->save();
        $this->checkAMLService->checkAML($getCarQuoteData->first_name, $getCarQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        $this->updateAdvisorAssignedDateTime('CarQuoteRequestDetail', $createRenewalQuote->id, 'car_quote_request_id', $currentUserId, $advisorId);

        return $createRenewalQuote->id;
    }

    public function createNewRenewalHealthQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode, $endDate, $grossPremium, $currentUserId)
    {
        $getHealthQuoteData = HealthQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Health);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Health);

        $createRenewalQuote = new HealthQuote([
            'first_name' => $getHealthQuoteData->first_name,
            'last_name' => $getHealthQuoteData->last_name,
            'customer_id' => $getHealthQuoteData->customer_id,
            'email' => $getHealthQuoteData->email,
            'mobile_no' => $getHealthQuoteData->mobile_no,
            'uuid' => $quoteUuid,
            'code' => 'HEA-'.$quoteUuid,
            'source' => 'Renewal_upload',
            'additional_notes' => $getHealthQuoteData->additional_notes,
            'previous_quote_id' => $quoteId,
            'advisor_id' => $advisorId,
            'renewal_batch' => $batchNumber,
            'previous_quote_policy_number' => $policy,
            'quote_status_id' => $newLeadId,
            'other_email_addresses' => $otherEmailIds,
            'renewal_import_code' => $renewalImportCode,
            'previous_policy_expiry_date' => $endDate,
            'previous_quote_policy_premium' => $grossPremium,
            'policy_number' => null,
        ]);
        $createRenewalQuote->save();
        $this->checkAMLService->checkAML($getHealthQuoteData->first_name, $getHealthQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        $this->updateAdvisorAssignedDateTime('HealthQuoteRequestDetail', $createRenewalQuote->id, 'health_quote_request_id', $currentUserId, $advisorId);

        return $createRenewalQuote->id;
    }

    public function createNewRenewalHomeQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode, $endDate, $grossPremium, $currentUserId)
    {
        $getHomeQuoteData = HomeQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Home);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Home);

        $createRenewalQuote = new HomeQuote([
            'first_name' => $getHomeQuoteData->first_name,
            'last_name' => $getHomeQuoteData->last_name,
            'customer_id' => $getHomeQuoteData->customer_id,
            'email' => $getHomeQuoteData->email,
            'mobile_no' => $getHomeQuoteData->mobile_no,
            'uuid' => $quoteUuid,
            'code' => 'HOM-'.$quoteUuid,
            'source' => 'Renewal_upload',
            'additional_notes' => $getHomeQuoteData->additional_notes,
            'previous_quote_id' => $quoteId,
            'advisor_id' => $advisorId,
            'renewal_batch' => $batchNumber,
            'previous_quote_policy_number' => $policy,
            'quote_status_id' => $newLeadId,
            'other_email_addresses' => $otherEmailIds,
            'renewal_import_code' => $renewalImportCode,
            'previous_policy_expiry_date' => $endDate,
            'previous_quote_policy_premium' => $grossPremium,
            'policy_number' => null,
        ]);
        $createRenewalQuote->save();
        $this->checkAMLService->checkAML($getHomeQuoteData->first_name, $getHomeQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        $this->updateAdvisorAssignedDateTime('HomeQuoteRequestDetail', $createRenewalQuote->id, 'home_quote_request_id', $currentUserId, $advisorId);

        return $createRenewalQuote->id;
    }

    public function createNewRenewalLifeQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode, $endDate, $grossPremium, $currentUserId)
    {
        $getLifeQuoteData = LifeQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Life);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Life);
        $createRenewalQuote = new LifeQuote([
            'first_name' => $getLifeQuoteData->first_name,
            'last_name' => $getLifeQuoteData->last_name,
            'customer_id' => $getLifeQuoteData->customer_id,
            'email' => $getLifeQuoteData->email,
            'mobile_no' => $getLifeQuoteData->mobile_no,
            'uuid' => $quoteUuid,
            'code' => 'LIF-'.$quoteUuid,
            'source' => 'Renewal_upload',
            'additional_notes' => $getLifeQuoteData->additional_notes,
            'previous_quote_id' => $quoteId,
            'advisor_id' => $advisorId,
            'renewal_batch' => $batchNumber,
            'previous_quote_policy_number' => $policy,
            'quote_status_id' => $newLeadId,
            'other_email_addresses' => $otherEmailIds,
            'renewal_import_code' => $renewalImportCode,
            'previous_policy_expiry_date' => $endDate,
            'previous_quote_policy_premium' => $grossPremium,
            'policy_number' => null,
        ]);
        $createRenewalQuote->save();
        $this->checkAMLService->checkAML($getLifeQuoteData->first_name, $getLifeQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        $this->updateAdvisorAssignedDateTime('LifeQuoteRequestDetail', $createRenewalQuote->id, 'life_quote_request_id', $currentUserId, $advisorId);

        return $createRenewalQuote->id;
    }

    public function createNewRenewalTravelQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode, $endDate, $grossPremium, $currentUserId)
    {
        $getTravelQuoteData = TravelQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Travel);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Travel);
        $createRenewalQuote = new TravelQuote([
            'first_name' => $getTravelQuoteData->first_name,
            'last_name' => $getTravelQuoteData->last_name,
            'customer_id' => $getTravelQuoteData->customer_id,
            'email' => $getTravelQuoteData->email,
            'mobile_no' => $getTravelQuoteData->mobile_no,
            'uuid' => $quoteUuid,
            'code' => 'TRA-'.$quoteUuid,
            'source' => 'Renewal_upload',
            'additional_notes' => $getTravelQuoteData->additional_notes,
            'previous_quote_id' => $quoteId,
            'advisor_id' => $advisorId,
            'renewal_batch' => $batchNumber,
            'previous_quote_policy_number' => $policy,
            'quote_status_id' => $newLeadId,
            'other_email_addresses' => $otherEmailIds,
            'renewal_import_code' => $renewalImportCode,
            'previous_policy_expiry_date' => $endDate,
            'previous_quote_policy_premium' => $grossPremium,
            'policy_number' => null,
        ]);
        $createRenewalQuote->save();
        $this->checkAMLService->checkAML($getTravelQuoteData->first_name, $getTravelQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        $this->updateAdvisorAssignedDateTime('TravelQuoteRequestDetail', $createRenewalQuote->id, 'travel_quote_request_id', $currentUserId, $advisorId);

        return $createRenewalQuote->id;
    }

    public function createNewRenewalYachtQuote($quoteId, $newAdvisor, $batchNumber, $policy, $newLeadId, $otherEmailIds, $renewalImportCode, $endDate, $grossPremium, $currentUserId)
    {
        $getYachtQuoteData = YachtQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Yacht);
        $quoteUuid = $this->generateUUID(QuoteTypeId::Yacht);

        $createRenewalQuote = new YachtQuote([
            'first_name' => $getYachtQuoteData->first_name,
            'last_name' => $getYachtQuoteData->last_name,
            'customer_id' => $getYachtQuoteData->customer_id,
            'email' => $getYachtQuoteData->email,
            'mobile_no' => $getYachtQuoteData->mobile_no,
            'uuid' => $quoteUuid,
            'code' => 'YAC-'.$quoteUuid,
            'source' => 'Renewal_upload',
            'additional_notes' => $getYachtQuoteData->additional_notes,
            'previous_quote_id' => $quoteId,
            'advisor_id' => $advisorId,
            'renewal_batch' => $batchNumber,
            'previous_quote_policy_number' => $policy,
            'quote_status_id' => $newLeadId,
            'other_email_addresses' => $otherEmailIds,
            'renewal_import_code' => $renewalImportCode,
            'previous_policy_expiry_date' => $endDate,
            'previous_quote_policy_premium' => $grossPremium,
            'policy_number' => null,
        ]);
        $createRenewalQuote->save();
        $this->checkAMLService->checkAML($getYachtQuoteData->first_name, $getYachtQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        $this->updateAdvisorAssignedDateTime('YachtQuoteRequestDetail', $createRenewalQuote->id, 'yacht_quote_request_id', $currentUserId, $advisorId);

        return $createRenewalQuote->id;
    }
}
