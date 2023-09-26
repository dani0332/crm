<?php

namespace App\Repositories;

use App\Enums\quoteStatusCode;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\BusinessQuote;
use App\Traits\CentralTrait;

class BusinessQuoteRepository extends BaseRepository
{
    use CentralTrait;

    public function model()
    {
        return BusinessQuote::class;
    }

    public function fetchExport()
    {
        return $this->filter()->with(
            ['advisor', 'nationality', 'insuranceProvider','businessTypeOfInsurance']
        )->orderBy('created_at', 'desc');
    }

    /**
     * @return mixed
     */
    public function fetchGetData($quoteType, $forExport = false)
    {
        $query = $this->with([
            'businessQuoteRequestDetail.lostReason',
            'quoteStatus',
            'advisor',
            'businessTypeOfInsurance',
            'nationality',
            'insuranceProvider',
            'typeOfInsurance',
        ])->whereHas('businessTypeOfInsurance', function ($businessTypeOfInsurance) use ($quoteType) {
            $businessTypeOfInsurance->when($quoteType == quoteTypeCode::GroupMedical, function ($groupMedical) {
                $groupMedical->where('text', quoteStatusCode::GROUP_MEDICAL);
            });
            $businessTypeOfInsurance->when($quoteType == quoteTypeCode::CORPLINE, function ($corpline) {
                $corpline->where('text', '!=', quoteStatusCode::GROUP_MEDICAL);
            });
        })->when(($quoteType == quoteTypeCode::GroupMedical && (
            auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::Business) ||
            auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::Amt) ||
            auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::GM)
        )), function ($query) {
            $query->where('advisor_id', \auth()->user()->id);
        })->when(($quoteType == quoteTypeCode::CORPLINE && (
            auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::CORPLINE) ||
            auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::Business) ||
            auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::Amt) ||
            auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::GM)
        )), function ($query) {
            $query->where('advisor_id', auth()->user()->id);
        })
            ->filter(! $forExport)
            ->withFakeLeadCriteria()
            ->orderBy('created_at', 'desc');

        return ($forExport) ? $query->get() : $query->simplePaginate();
    }

    public function fetchCreateDuplicate(array $dataArr): object
    {
        return Capi::request('/api/v1-save-'.strtolower(QuoteTypes::BUSINESS->value).'-quote', 'post', $dataArr);
    }
    public function fetchGetDataOfBusiness()
    {
        return $this->filter()->with(
            ['advisor', 'nationality', 'insuranceProvider','businessTypeOfInsurance'])->orderBy('created_at', 'desc')->Paginate();
    }


}
