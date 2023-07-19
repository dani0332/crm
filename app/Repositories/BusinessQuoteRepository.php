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
            'advisor'
            ])->whereHas('typeOfInsurance', function($typeOfInsurance) use ($quoteType){
                $typeOfInsurance->when($quoteType == 'Group Medical', function($query){
                    $query->where('text', quoteStatusCode::GROUP_MEDICAL);
                });
                $typeOfInsurance->when($quoteType == 'Amt', function($query){
                    $query->where('text', '!=' ,quoteStatusCode::GROUP_MEDICAL);
                });
            })->when(($quoteType == 'Group Medical' && (
                auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::Business) || 
                auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::Amt) || 
                auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::GM)
            )), function($query){
                $query->where('advisor_id', \auth()->user()->id);
            })->when(($quoteType != 'Group Medical' && (
                auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::CORPLINE) || 
                auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::Business) || 
                auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::Amt) || 
                auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::GM)
            )), function($query){
                $query->where('advisor_id', \auth()->user()->id);
            })
            ->filter()
            ->withFakeLeadCriteria()
            ->orderBy('created_at', 'desc');
        
        return ($isForExport) ? $query->get() : $query->simplePaginate();
    }
}
