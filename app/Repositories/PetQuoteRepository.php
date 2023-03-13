<?php

namespace App\Repositories;

use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Services\CapiRequestService;
use App\Traits\AddPremiumAllLobs;
use Config;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PetQuoteRepository extends BaseRepository
{
    use AddPremiumAllLobs;
    public function model()
    {
        return PersonalQuote::class;
    }

    public function fetchCreate($request)
    {
        $sourceName = Config::get('constants.SOURCE_NAME');
        $appUrl = Config::get('constants.APP_URL');
        $dataArr = [
            'firstName' => $request->first_name,
            'lastName' => $request->last_name,
            'email' => $request->email,
            'mobileNo' => $request->mobile_no,
            'gender' => $request->gender,
            'microchipNo' => $request->microchip_no,
            'typeOfPet1' => $request->type_of_pet1,
            'breedOfPet1' => $request->breed_of_pet1,
            'isMicrochipped' => $request->is_microchipped == 'Yes' ? true : false,
            'isNeutered' => $request->is_neutered == 'Yes' ? true : false,
            'isMixedBreed' => $request->is_mixed_breed == 'Yes' ? true : false,
            'anyInjury' => $request->any_injury == 'Yes' ? true : false,
            'ageOfPet1' => $request->age_of_pet1,
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'utmSource' => '',
            'utmMedium' => '',
            'utmCampaign' => '',
            'iliveinAccommodationTypeId' => $request->ilivein_accommodation_type_id,
            'iamPossesionTypeId' => $request->iam_possesion_type_id,
            'source' => $sourceName,
            'referenceUrl' => $appUrl,
        ];

        if (! Auth::user()->hasRole('ADMIN')) {
            $dataArr['advisorId'] = Auth::user()->id;
        }

        $response = CapiRequestService::sendCAPIRequest('/api/v1-save-pet-quote', $dataArr);

        if (isset($response->quoteUID)) {
            $this->savePremium(quoteTypeCode::PetQuote, $request, $response);
        }

        return $response;
    }

    public function fetchUpdate($uuid, $data)
    {
        return DB::transaction(function () use ($uuid, $data) {
            $quote = $this->byQuoteTypeId(QuoteTypes::PET->id())->where('uuid', $uuid)->firstOrFail();

            $quoteData = Arr::only($data, [
                'first_name', 'last_name', 'email', 'mobile_no',
            ]);

            $quoteData['updated_by_id'] = Auth::user()->id;
            $quote->update($quoteData);

            $quote->petQuote->update(Arr::only($data, ['premium', 'policy_number', 'type_of_pet1', 'breed_of_pet1', 'age_of_pet1', 'is_neutered', 'is_microchipped', 'microchip_no', 'is_mixed_breed', 'has_injury', 'gender', 'ilivein_accommodation_type_id', 'iam_possesion_type_id']));

            return $quote;
        });
    }
    public function fetchGetData()
    {
        return $this->byQuoteTypeCode(QuoteTypes::PET)->with(['quoteStatus', 'petQuote.accomodationType:id,text', 'petQuote.possessionType:id,text', 'currentlyInsuredWith', 'advisor'])
            ->filter()
            ->orderBy('created_at', 'desc')
            ->simplePaginate();
    }
//possessionType
    public function fetchGetBy($column, $value)
    {
        return $this->byQuoteTypeId(QuoteTypes::PET->id())
            ->where($column, $value)
            ->with(['petQuote.accomodationType:id,text', 'petQuote.possessionType:id,text', 'advisor', 'quoteDetail.lostReason', 'payments' => function ($q) {
                $q->with(['paymentStatus', 'personalPlan', 'paymentMethod']);
            }, 'createdBy', 'updatedBy','customer.additionalContactInfo'])->firstOrFail();
    }
}
