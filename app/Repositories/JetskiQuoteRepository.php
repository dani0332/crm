<?php

namespace App\Repositories;

use App\Enums\LookupsEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Models\PersonalQuote;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class JetskiQuoteRepository extends BaseRepository
{
    use GenericQueriesAllLobs;

    public function model()
    {
        return PersonalQuote::class;
    }

    /**
     * create new personal quote
     *
     * @param  $quoteTypeCode
     * @return mixed
     */
    public function fetchCreate($data)
    {
        $quoteData = [
            'quoteTypeId' => intval(QuoteTypes::JETSKI->id()),
            'firstName' => $data['first_name'],
            'lastName' => $data['last_name'],
            'email' => $data['email'],
            'mobileNo' => $data['mobile_no'],
            'jetskiMake' => $data['jetski_make'],
            'jetskiModel' => $data['jetski_model'],
            'maxSpeed' => $data['max_speed'],
            'seatCapacity' => $data['seat_capacity'],
            'enginePower' => $data['engine_power'],
            'yearOfManufactureId' => strval($data['year_of_manufacture_id']),
            'jetskiMaterialId' => strval($data['jetski_material_id']),
            'jetskiUseId' => $data['jetski_use_id'],
            'claimHistory' => $data['claim_history'],
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'source' => config('constants.SOURCE_NAME'),
            'referenceUrl' => URL::current(),
            'createdById' => auth()->user()->id,
            'advisorId' => (! auth()->user()->hasRole(RolesEnum::Admin)) ? auth()->user()->id : null,
        ];

        info('JetSki Quote Create :'.json_encode($quoteData));

        return Capi::request('/api/v1-save-personal-quote', 'post', $quoteData);
    }

    /**
     * @return mixed
     */
    public function fetchUpdate($uuid, $data)
    {
        return DB::transaction(function () use ($uuid, $data) {
            $quote = $this->byQuoteTypeId(QuoteTypes::JETSKI->id())->where('uuid', $uuid)->firstOrFail();

            $quoteData = Arr::only($data, [
                'first_name', 'last_name', 'email', 'mobile_no',
            ]);

            $quoteData['updated_by_id'] = Auth::user()->id;
            $quote->update($quoteData);

            $quote->jetskiQuote->update(Arr::only($data, ['jetski_make', 'jetski_model', 'year_of_manufacture_id', 'max_speed', 'seat_capacity',
                'engine_power', 'jetski_material_id', 'jetski_use_id', 'claim_history']));

            return $quote;
        });
    }

    /**
     * get all dropdown options required for form
     *
     * @return array
     */
    public function fetchGetFormOptions()
    {
        return [
            'jetski_materials' => LookupRepository::where('key', LookupsEnum::JETSKI_MATERIALS)->get(),
            'jetski_uses' => LookupRepository::where('key', LookupsEnum::JETSKI_USES)->get(),
            'yearOfManufacture' => YearOfManufactureRepository::get(),
        ];
    }

    /**
     * @return mixed
     */
    public function fetchGetBy($column, $value)
    {
        return $this->byQuoteTypeId(QuoteTypes::JETSKI->id())
            ->where($column, $value)
            ->with(['jetskiQuote', 'nationality', 'advisor', 'quoteDetail.lostReason', 'payments' => function ($q) {
                $q->with(['paymentStatus', 'personalPlan', 'paymentMethod', 'paymentable']);
            }, 'createdBy', 'updatedBy', 'customer.additionalContactInfo', 'documents' => function ($q) {
                $q->with('createdBy')->orderBy('created_at', 'desc');
            }])->firstOrFail();
    }

    /**
     * @return mixed
     */
    public function fetchGetData($forExport = false)
    {

        $query = $this->byQuoteTypeCode(QuoteTypes::JETSKI)->with([
            'quoteStatus',
            'currentlyInsuredWith',
            'advisor',
            'paymentStatus',
            'payments',
            'renewalBatchModel',
        ])->when(auth()->user()->hasRole(RolesEnum::JetskiAdvisor), function ($query) {
            $query->where('advisor_id', auth()->user()->id);
        })->filter(! $forExport)
            ->withFakeLeadCriteria();

        $query->orderBy('personal_quotes.'.(request()->sortBy ?? 'created_at'), request()->sortType ?? 'desc');

        $this->adjustQueryByInsurerInvoiceFilters($query);

        return ($forExport) ? $query->get() : $query->simplePaginate();
    }

}
