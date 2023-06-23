<?php

namespace App\Repositories;

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Models\PersonalQuote;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class CycleQuoteRepository extends BaseRepository
{
    public function model()
    {
        return PersonalQuote::class;
    }

    /**
     * create new personal quote
     *
     * @param $quoteTypeCode
     * @return mixed
     */
    public function fetchCreate($data)
    {
        $quoteData = [
            'quoteTypeId' => intval(QuoteTypes::CYCLE->id()),
            'mobileNo' => $data['mobile_no'],
            'email' => $data['email'],
            'firstName' => $data['first_name'],
            'lastName' => $data['last_name'],
            'cycleMake' => $data['cycle_make'],
            'cycleModel' => $data['cycle_model'],
            'accessories' => $data['accessories'],
            'hasAccident' => boolval($data['has_accident']),
            'hasGoodCondition' => boolval($data['has_good_condition']),
            'assetValue' => $data['asset_value'],
            'yearOfManufactureId' => strval($data['year_of_manufacture_id']),
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'source' => config('constants.SOURCE_NAME'),
            'referenceUrl' => URL::current(),
            'createdById' => Auth::user()->id,
        ];

        info('cycleQuote:'.json_encode($quoteData));

        return Capi::request('/api/v1-save-personal-quote', 'post', $quoteData);
    }

    /**
     * @return mixed
     */
    public function fetchGetData()
    {
        return $this->byQuoteTypeCode(QuoteTypes::CYCLE)->with(['quoteStatus', 'currentlyInsuredWith', 'advisor'])
            ->when(\auth()->user()->hasRole(RolesEnum::CycleAdvisor), function ($query) {
                $query->where(function ($query) {
                    $query->where('advisor_id', \auth()->user()->id);
                });
            })
            ->filter()
            ->withFakeLeadCriteria()
            ->orderBy('created_at', 'desc')
            ->simplePaginate();
    }

    /**
     * @return mixed
     */
    public function fetchUpdate($uuid, $data)
    {
        return DB::transaction(function () use ($uuid, $data) {
            $quote = $this->byQuoteTypeId(QuoteTypes::CYCLE->id())->where('uuid', $uuid)->firstOrFail();

            $quoteData = Arr::only($data, ['first_name', 'last_name', 'email', 'mobile_no', 'dob',  'asset_value']);
            $quoteData['updated_by_id'] = Auth::user()->id;

            $quote->update($quoteData);

            $quote->cycleQuote->update(Arr::only($data, ['cycle_make', 'cycle_model', 'year_of_manufacture_id', 'accessories', 'has_accident', 'has_good_condition']));

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
            'nationalities' => NationalityRepository::withActive()->get(),
            'uaeLicenses' => UaeLicenseHeldRepository::withActive()->get(),
            'yearOfManufacture' => YearOfManufactureRepository::get(),
            'insuranceProviders' => InsuranceProviderRepository::select('id', 'text')->orderBy('text', 'asc')->get(),
        ];
    }

    /**
     * @return mixed
     */
    public function fetchGetBy($column, $value)
    {
        $quote = $this->byQuoteTypeId(QuoteTypes::CYCLE->id())
            ->where($column, $value)
            ->with(['cycleQuote', 'advisor', 'nationality', 'quoteDetail.lostReason', 'payments' => function ($q) {
                $q->with(['paymentStatus', 'personalPlan', 'paymentMethod', 'paymentStatusLogs']);
            }, 'createdBy', 'updatedBy', 'documents' => function ($q) {
                $q->with('createdBy')->orderBy('created_at', 'desc');
            }])->firstOrFail();
        $quote->payments->each->setAppends(['allow', 'copy_link_button', 'edit_button', 'approve_button', 'approved_button']);

        return $quote;
    }
}
