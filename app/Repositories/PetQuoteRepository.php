<?php

namespace App\Repositories;

use App\Enums\LookupsEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\HomeAccomodationType;
use App\Models\HomePossessionType;
use App\Models\PersonalQuote;
use App\Models\PetQuote;
use Config;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PetQuoteRepository extends BaseRepository
{
    public function model()
    {
        return (in_array(quoteTypeCode::Pet, newUi())) ? PersonalQuote::class : PetQuote::class;
    }

    public function fetchCreate($request)
    {
        $sourceName = Config::get('constants.SOURCE_NAME');
        $appUrl = Config::get('constants.APP_URL');
        $dataArr = [
            'firstName' => $request['first_name'],
            'lastName' => $request['last_name'],
            'email' => $request['email'],
            'mobileNo' => $request['mobile_no'],
            'gender' => $request['gender'],
            'microchipNo' => $request['microchip_no'],
            'petTypeId' => $request['pet_type_id'],
            'petAgeId' => $request['pet_age_id'],
            'breedOfPet1' => $request['breed_of_pet1'],
            'isMicrochipped' => $request['is_microchipped'],
            'isNeutered' => $request['is_neutered'],
            'isMixedBreed' => $request['is_mixed_breed'],
            'hasInjury' => $request['has_injury'],
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'utmSource' => '',
            'utmMedium' => '',
            'utmCampaign' => '',
            'iliveinAccommodationTypeId' => $request['ilivein_accommodation_type_id'],
            'iamPossesionTypeId' => $request['iam_possesion_type_id'],
            'source' => $sourceName,
            'referenceUrl' => $appUrl,
            'quoteTypeId' => intval(QuoteTypes::PET->id()),
        ];

        if (! Auth::user()->hasRole('ADMIN')) {
            $dataArr['advisorId'] = Auth::user()->id;
        }
        $response = Capi::request('/api/v1-save-personal-quote', 'post', $dataArr);

        if (isset($response->quoteUID)) {
            $quote = $this->byQuoteTypeId(QuoteTypes::PET->id())->where('uuid', $response->quoteUID)->firstOrFail();

            $quote->update(['premium' => $request['premium']]);
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

            $quote->petQuote->update(Arr::only($data, ['premium', 'policy_number', 'breed_of_pet1', 'pet_type_id', 'pet_age_id', 'is_neutered', 'is_microchipped', 'microchip_no', 'is_mixed_breed', 'has_injury', 'gender', 'ilivein_accommodation_type_id', 'iam_possesion_type_id']));

            return $quote;
        });
    }
    public function fetchGetData()
    {
        return $this->byQuoteTypeCode(QuoteTypes::PET)->with(['quoteStatus', 'petQuote.accomodationType:id,text', 'petQuote.possessionType:id,text', 'petQuote.petAge:id,text', 'petQuote.petType:id,text', 'currentlyInsuredWith', 'advisor'])
            ->filter()
            ->withFakeLeadCriteria()
            ->orderBy('created_at', 'desc')
            ->simplePaginate()->withQueryString();
    }
    public function fetchGetBy($column, $value)
    {
        return $this->byQuoteTypeId(QuoteTypes::PET->id())
            ->where($column, $value)
            ->with(['petQuote.accomodationType:id,text', 'petQuote.possessionType:id,text', 'petQuote.petAge:id,text', 'petQuote.petType:id,text', 'advisor', 'quoteDetail.lostReason', 'payments' => function ($q) {
                $q->with(['paymentStatus', 'personalPlan', 'paymentMethod']);
            }, 'createdBy', 'updatedBy', 'customer.additionalContactInfo', 'documents' => function ($q) {
                $q->with('createdBy')->orderBy('created_at', 'desc');
            }])->firstOrFail();
    }

    /**
     * get all dropdown options required for form
     *
     * @return array
     */
    public function fetchGetFormOptions()
    {
        return [
            'pet_ages' => LookupRepository::where('key', LookupsEnum::PET_AGES)->get(),
            'pet_types' => LookupRepository::where('key', LookupsEnum::PET_TYPES)->get(),
            'accomodation_types' => HomeAccomodationType::all(),
            'possession_types' => HomePossessionType::all(),
        ];
    }

    public function fetchCreateDuplicate(array $dataArr): object
    {
        $dataArr['quoteTypeId'] = intval(QuoteTypes::PET->id());
        return Capi::request('/api/v1-save-personal-quote', 'post', $dataArr);
    }
}
