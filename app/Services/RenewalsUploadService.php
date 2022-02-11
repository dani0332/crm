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
use Hidehalo\Nanoid\Client;
use App\Services\CheckAmlService;
use App\Enums\quoteTypeCode;
use App\Enums\quoteStatusCode;
use App\Models\QuoteStatus;

class RenewalsUploadService
{
    protected $renewalsAddonService, $checkAMLService;
    function __construct(RenewalsAddonServices $renewalsAddonService, CheckAmlService $checkAMLService)
    {
        $this->renewalsAddonService = $renewalsAddonService;
        $this->checkAMLService = $checkAMLService;
    }
    /*
    * @name generateUUID()
    * @returns a 16 character UUIDv4 string
    */
    public function generateUUID()
    {
        $client = new Client();
        $alphabets = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $nanoId = $client->formattedId($alphabets, 16);
        return $nanoId;
    }

    /*
    * @name createNewQuote()
    * @params $quoteData - extracted data from excel file, $qouteType - type of quote
    * @returns custom function decalred against each quote type else returns false
    */
    public function createNewQuote($quoteData, $quoteType)
    {
        if (!$quoteData) {
            return false;
        }
        if (!$quoteType) {
            return false;
        }

        if ($quoteType == 'BIK') {
            return $this->createNewBikeQuoute($quoteData);
        }

        if ($quoteType == 'BUS') {
            return $this->createNewBusinessQuoute($quoteData);
        }

        if ($quoteType == 'CAR') {
            return $this->createNewCarQuoute($quoteData);
        }

        if ($quoteType == 'HEA') {
            return $this->createNewHealthQuoute($quoteData);
        }

        if ($quoteType == 'HOM') {
            return $this->createNewHomeQuoute($quoteData);
        }

        if ($quoteType == 'LIF') {
            return $this->createNewLifeQuoute($quoteData);
        }

        if ($quoteType == 'TRA') {
            return $this->createNewTravelQuoute($quoteData);
        }

        if ($quoteType == 'YAC') {
            return $this->createNewYachtQuoute($quoteData);
        }
    }

    function createNewBikeQuoute($quoteData)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);

        $newBikeQuote = new BikeQuote([
            "first_name" => $quoteData->first_name,
            "last_name" => $quoteData->last_name,
            "customer_id" => $quoteData->customer_id,
            "email" => $quoteData->email,
            "mobile_no" => $quoteData->phoneNumber,
            "uuid" => $this->generateUUID(),
            "source" => $quoteData->source,
            "currently_insured_with" => $quoteData->insurer,
            "year_of_manufacture" => $quoteData->year,
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes
        ]);
        $newBikeQuote->save();

        $this->renewalsAddonService->updateBikeQuoteRequestCode($newBikeQuote->id);
        $createRenewalQuote = $this->createNewRenewalBikeQuote($newBikeQuote->id, $quoteData->advisor);
        $this->createRenewalDumpRecord('Bike', $createRenewalQuote, $quoteData);
    }

    function createNewBusinessQuoute($quoteData)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);

        $newBusinessQuote = new BusinessQuote([
            "first_name" => $quoteData->first_name,
            "last_name" => $quoteData->last_name,
            "customer_id" => $quoteData->customer_id,
            "email" => $quoteData->email,
            "mobile_no" => $quoteData->phoneNumber,
            "uuid" => $this->generateUUID(),
            "source" => $quoteData->source,
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes
        ]);
        $newBusinessQuote->save();

        $this->renewalsAddonService->updateBusinessQuoteRequestCode($newBusinessQuote->id);
        $createRenewalQuote = $this->createNewRenewalBusinessQuote($newBusinessQuote->id, $quoteData->advisor);
        $this->createRenewalDumpRecord('Business', $createRenewalQuote, $quoteData);
    }

    function createNewCarQuoute($quoteData)
    {
        $carTypeOfInsurance = null;
        $carMake = $this->renewalsAddonService->getCarMake($quoteData->make);
        $carModel = $this->renewalsAddonService->getCarModel($quoteData->model);
        $vehicleType = null;
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);

        if ($carModel) {
            $vehicleType = $this->renewalsAddonService->getVehicleType($carModel->vehicle_type_id);
        }

        if ($quoteData->product_type != null) {
            $carTypeOfInsurance = $this->renewalsAddonService->getCarTypeOfInsurance($quoteData->product_type)->id;
        }

        $quoteStatusId = QuoteStatus::where('code', '=', quoteStatusCode::TRANSACTION_APPROVED)->value('id');

        $newCarQuote = new CarQuote([
            "first_name" => $quoteData->first_name,
            "last_name" => $quoteData->last_name,
            "customer_id" => $quoteData->customer_id,
            "email" => $quoteData->email,
            "mobile_no" => $quoteData->phoneNumber,
            "uuid" => $this->generateUUID(),
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
            "quote_status_id" => $quoteStatusId
        ]);
        $newCarQuote->save();

        $this->renewalsAddonService->updateCarQuoteRequestCode($newCarQuote->id);
        $createRenewalQuote = $this->createNewRenewalCarQuote($newCarQuote->id, $quoteData->advisor, $quoteData->batch, $quoteData->endDate, $quoteData->policy);
        $this->createRenewalDumpRecord('Car', $createRenewalQuote, $quoteData);
    }

    function createNewHealthQuoute($quoteData)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);

        $newHealthQuote = new HealthQuote([
            "first_name" => $quoteData->first_name,
            "last_name" => $quoteData->last_name,
            "customer_id" => $quoteData->customer_id,
            "email" => $quoteData->email,
            "mobile_no" => $quoteData->phoneNumber,
            "uuid" => $this->generateUUID(),
            "source" => $quoteData->source,
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes
        ]);
        $newHealthQuote->save();

        $this->renewalsAddonService->updateHealthQuoteRequestCode($newHealthQuote->id);
        $createRenewalQuote = $this->createNewRenewalHealthQuote($newHealthQuote->id, $quoteData->advisor);
        $this->createRenewalDumpRecord('Health', $createRenewalQuote, $quoteData);
    }

    function createNewHomeQuoute($quoteData)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);

        $newHomeQuote = new HomeQuote([
            "first_name" => $quoteData->first_name,
            "last_name" => $quoteData->last_name,
            "customer_id" => $quoteData->customer_id,
            "email" => $quoteData->email,
            "mobile_no" => $quoteData->phoneNumber,
            "uuid" => $this->generateUUID(),
            "source" => $quoteData->source,
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes
        ]);
        $newHomeQuote->save();

        $this->renewalsAddonService->updateHomeQuoteRequestCode($newHomeQuote->id);
        $createRenewalQuote = $this->createNewRenewalHomeQuote($newHomeQuote->id, $quoteData->advisor);
        $this->createRenewalDumpRecord('Home', $createRenewalQuote, $quoteData);
    }

    function createNewLifeQuoute($quoteData)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);

        $newLifeQuote = new LifeQuote([
            "first_name" => $quoteData->first_name,
            "last_name" => $quoteData->last_name,
            "customer_id" => $quoteData->customer_id,
            "email" => $quoteData->email,
            "mobile_no" => $quoteData->phoneNumber,
            "uuid" => $this->generateUUID(),
            "source" => $quoteData->source,
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes
        ]);
        $newLifeQuote->save();

        $this->renewalsAddonService->updateLifeQuoteRequestCode($newLifeQuote->id);
        $createRenewalQuote = $this->createNewRenewalLifeQuote($newLifeQuote->id, $quoteData->advisor);
        $this->createRenewalDumpRecord('Life', $createRenewalQuote, $quoteData);
    }

    function createNewTravelQuoute($quoteData)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);

        $newTravelQuote = new TravelQuote([
            "first_name" => $quoteData->first_name,
            "last_name" => $quoteData->last_name,
            "customer_id" => $quoteData->customer_id,
            "email" => $quoteData->email,
            "mobile_no" => $quoteData->phoneNumber,
            "uuid" => $this->generateUUID(),
            "source" => $quoteData->source,
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes
        ]);
        $newTravelQuote->save();

        $this->renewalsAddonService->updateTravelQuoteRequestCode($newTravelQuote->id);
        $createRenewalQuote = $this->createNewRenewalTravelQuote($newTravelQuote->id, $quoteData->advisor);
        $this->createRenewalDumpRecord('Travel', $createRenewalQuote, $quoteData);
    }

    function createNewYachtQuoute($quoteData)
    {
        $advisorId = $this->renewalsAddonService->getUserInfo($quoteData->pAdvisor);

        $newYachtQuote = new YachtQuote([
            "first_name" => $quoteData->first_name,
            "last_name" => $quoteData->last_name,
            "customer_id" => $quoteData->customer_id,
            "email" => $quoteData->email,
            "mobile_no" => $quoteData->phoneNumber,
            "uuid" => $this->generateUUID(),
            "source" => $quoteData->source,
            "policy_number" => $quoteData->policy,
            "advisor_id" => $advisorId,
            "additional_notes" => $quoteData->notes
        ]);
        $newYachtQuote->save();

        $this->renewalsAddonService->updateYachtQuoteRequestCode($newYachtQuote->id);
        $createRenewalQuote = $this->createNewRenewalYachtQuote($newYachtQuote->id, $quoteData->advisor);
        $this->createRenewalDumpRecord('Yacht', $createRenewalQuote, $quoteData);
    }

    function createNewRenewalBikeQuote($quoteId, $newAdvisor)
    {
        $getBikeQuoteData = BikeQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Bike);

        $createRenewalQuote = new BikeQuote([
            "first_name" => $getBikeQuoteData->first_name,
            "last_name" => $getBikeQuoteData->last_name,
            "customer_id" => $getBikeQuoteData->customer_id,
            "email" => $getBikeQuoteData->email,
            "mobile_no" => $getBikeQuoteData->mobile_no,
            "uuid" => $this->generateUUID(),
            "source" => $getBikeQuoteData->source,
            "currently_insured_with" => $getBikeQuoteData->currently_insured_with,
            "year_of_manufacture" => $getBikeQuoteData->year_of_manufacture,
            "additional_notes" => $getBikeQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId
        ]);

        $createRenewalQuote->save();
        $this->renewalsAddonService->updateBikeQuoteRequestCode($createRenewalQuote->id);

        $this->checkAMLService->checkAml($getBikeQuoteData->first_name, $getBikeQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalBusinessQuote($quoteId, $newAdvisor)
    {
        $getBusinessQuoteData = BusinessQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Business);

        $createRenewalQuote = new BusinessQuote([
            "first_name" => $getBusinessQuoteData->first_name,
            "last_name" => $getBusinessQuoteData->last_name,
            "customer_id" => $getBusinessQuoteData->customer_id,
            "email" => $getBusinessQuoteData->email,
            "mobile_no" => $getBusinessQuoteData->mobile_no,
            "uuid" => $this->generateUUID(),
            "source" => $getBusinessQuoteData->source,
            "additional_notes" => $getBusinessQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId
        ]);

        $createRenewalQuote->save();
        $this->renewalsAddonService->updateBusinessQuoteRequestCode($createRenewalQuote->id);

        $this->checkAMLService->checkAml($getBusinessQuoteData->first_name, $getBusinessQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalCarQuote($quoteId, $newAdvisor, $batchNumber, $endDate, $policy)
    {
        $getCarQuoteData = CarQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Car);


        $quoteStatusId = QuoteStatus::where('code', '=', quoteStatusCode::NEW_LEAD)->value('id');

        $createRenewalQuote = new CarQuote([
            "first_name" => $getCarQuoteData->first_name,
            "last_name" => $getCarQuoteData->last_name,
            "customer_id" => $getCarQuoteData->customer_id,
            "email" => $getCarQuoteData->email,
            "mobile_no" => $getCarQuoteData->mobile_no,
            "uuid" => $this->generateUUID(),
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
            "quote_status_id" => $quoteStatusId,
            "renewal_expiry_date" => $endDate,
            "previous_quote_policy_number" => $policy
        ]);

        $createRenewalQuote->save();
        $this->renewalsAddonService->updateCarQuoteRequestCode($createRenewalQuote->id);

        $this->checkAMLService->checkAml($getCarQuoteData->first_name, $getCarQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalHealthQuote($quoteId, $newAdvisor)
    {
        $getHealthQuoteData = HealthQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Health);

        $createRenewalQuote = new HealthQuote([
            "first_name" => $getHealthQuoteData->first_name,
            "last_name" => $getHealthQuoteData->last_name,
            "customer_id" => $getHealthQuoteData->customer_id,
            "email" => $getHealthQuoteData->email,
            "mobile_no" => $getHealthQuoteData->mobile_no,
            "uuid" => $this->generateUUID(),
            "source" => $getHealthQuoteData->source,
            "additional_notes" => $getHealthQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId
        ]);

        $createRenewalQuote->save();
        $this->renewalsAddonService->updateHealthQuoteRequestCode($createRenewalQuote->id);

        $this->checkAMLService->checkAml($getHealthQuoteData->first_name, $getHealthQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalHomeQuote($quoteId, $newAdvisor)
    {
        $getHomeQuoteData = HomeQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Home);

        $createRenewalQuote = new HomeQuote([
            "first_name" => $getHomeQuoteData->first_name,
            "last_name" => $getHomeQuoteData->last_name,
            "customer_id" => $getHomeQuoteData->customer_id,
            "email" => $getHomeQuoteData->email,
            "mobile_no" => $getHomeQuoteData->mobile_no,
            "uuid" => $this->generateUUID(),
            "source" => $getHomeQuoteData->source,
            "additional_notes" => $getHomeQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId
        ]);

        $createRenewalQuote->save();
        $this->renewalsAddonService->updateHomeQuoteRequestCode($createRenewalQuote->id);

        $this->checkAMLService->checkAml($getHomeQuoteData->first_name, $getHomeQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalLifeQuote($quoteId, $newAdvisor)
    {
        $getLifeQuoteData = LifeQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Life);

        $createRenewalQuote = new LifeQuote([
            "first_name" => $getLifeQuoteData->first_name,
            "last_name" => $getLifeQuoteData->last_name,
            "customer_id" => $getLifeQuoteData->customer_id,
            "email" => $getLifeQuoteData->email,
            "mobile_no" => $getLifeQuoteData->mobile_no,
            "uuid" => $this->generateUUID(),
            "source" => $getLifeQuoteData->source,
            "additional_notes" => $getLifeQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId
        ]);

        $createRenewalQuote->save();
        $this->renewalsAddonService->updateLifeQuoteRequestCode($createRenewalQuote->id);

        $this->checkAMLService->checkAml($getLifeQuoteData->first_name, $getLifeQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalTravelQuote($quoteId, $newAdvisor)
    {
        $getTravelQuoteData = TravelQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Travel);

        $createRenewalQuote = new TravelQuote([
            "first_name" => $getTravelQuoteData->first_name,
            "last_name" => $getTravelQuoteData->last_name,
            "customer_id" => $getTravelQuoteData->customer_id,
            "email" => $getTravelQuoteData->email,
            "mobile_no" => $getTravelQuoteData->mobile_no,
            "uuid" => $this->generateUUID(),
            "source" => $getTravelQuoteData->source,
            "additional_notes" => $getTravelQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId
        ]);

        $createRenewalQuote->save();
        $this->renewalsAddonService->updateTravelQuoteRequestCode($createRenewalQuote->id);

        $this->checkAMLService->checkAml($getTravelQuoteData->first_name, $getTravelQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
        return $createRenewalQuote->id;
    }

    function createNewRenewalYachtQuote($quoteId, $newAdvisor)
    {
        $getYachtQuoteData = YachtQuote::where('id', '=', $quoteId)->get()->first();
        $advisorId = $this->renewalsAddonService->getUserInfo($newAdvisor);
        $quoteType = $this->renewalsAddonService->getQuoteType(quoteTypeCode::Yacht);

        $createRenewalQuote = new YachtQuote([
            "first_name" => $getYachtQuoteData->first_name,
            "last_name" => $getYachtQuoteData->last_name,
            "customer_id" => $getYachtQuoteData->customer_id,
            "email" => $getYachtQuoteData->email,
            "mobile_no" => $getYachtQuoteData->mobile_no,
            "uuid" => $this->generateUUID(),
            "source" => $getYachtQuoteData->source,
            "additional_notes" => $getYachtQuoteData->additional_notes,
            "previous_quote_id" => $quoteId,
            "advisor_id" => $advisorId
        ]);

        $createRenewalQuote->save();
        $this->renewalsAddonService->updateYachtQuoteRequestCode($createRenewalQuote->id);

        $this->checkAMLService->checkAml($getYachtQuoteData->first_name, $getYachtQuoteData->last_name, $createRenewalQuote->id, $quoteType->id, false, null, null);
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
}
