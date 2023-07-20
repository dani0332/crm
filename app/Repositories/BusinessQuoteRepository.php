<?php

namespace App\Repositories;

use App\Enums\quoteStatusCode;
use App\Enums\quoteTypeCode;
use App\Models\BusinessQuote;
use App\Traits\CentralTrait;

class BusinessQuoteRepository extends BaseRepository
{
    use CentralTrait;
    public function model()
    {
        return BusinessQuote::class;
    }

    /**
     * @return mixed
     */
    public function fetchGetData($quoteType, $isForExport = false)
    {
        $query = $this->with([
            'businessQuoteRequestDetail.lostReason',
            'quoteStatus',
            'advisor',
            'typeOfInsurance'
            ])->whereHas('typeOfInsurance', function($typeOfInsurance) use($quoteType){
                $typeOfInsurance->when($quoteType == quoteTypeCode::GroupMedical, function($groupMedical){
                    $groupMedical->where('text', quoteStatusCode::GROUP_MEDICAL);
                });
                $typeOfInsurance->when($quoteType == quoteTypeCode::CORPLINE, function($corpline){
                    $corpline->where('text', '!=', quoteStatusCode::GROUP_MEDICAL);
                });
            })->when(($quoteType == quoteTypeCode::GroupMedical && (
                auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::Business) ||
                auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::Amt) ||
                auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::GM)
            )), function($query){
                $query->where('advisor_id', \auth()->user()->id);
            })->when(($quoteType == quoteTypeCode::CORPLINE && (
                auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::CORPLINE) ||
                auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::Business) ||
                auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::Amt) ||
                auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::GM)
            )), function($query){
                $query->where('advisor_id', auth()->user()->id);
            })
            ->filter()
            ->withFakeLeadCriteria()
            ->orderBy('created_at', 'desc');

        return ($isForExport) ? $query->get() : $query->simplePaginate();
    }
}
