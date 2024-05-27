<?php

namespace App\Repositories;

use App\Enums\LeadSourceEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\Emirate;
use App\Models\HealthCoverFor;
use App\Models\HealthLeadType;
use App\Models\HealthQuote;
use App\Services\CRUDService;
use App\Services\DropdownSourceService;
use App\Services\LookupService;
use Illuminate\Support\Facades\Auth;

class HealthRevivalQuoteRepository extends BaseRepository
{
    public function model()
    {
        return HealthQuote::class;
    }

    /**
     * @return mixed
     */
    public function fetchGetData()
    {
        $request = request();
        $query = HealthQuote::with([
            'paymentStatus',
            'quoteStatus',
            'advisor',
            'salaryBand',
            'memberCategory',
            'currentProvider',
            'healthLeadType',
            'healthQuoteRequestDetail.lostReason',
        ])->where('source', LeadSourceEnum::REVIVAL)

            ->filter();
        if (!empty($request->assignment_type)) {
            $query->where('assignment_type', $request->assignment_type);
        }
        if (!empty($request->advisors)) {
            $query->whereIn('advisor_id', $request->advisors);
        }
        $query->orderBy('created_at', 'desc');

        return $query->simplePaginate();
    }

    /**
     * get all dropdown options required for form.
     *
     * @return array
     */
    public function fetchGetFormOptions()
    {

        $result = [
            'advisors' => app(CRUDService::class)->getAdvisorsByModelType(strtolower(quoteTypeCode::Health)),
            'teams' => app(CRUDService::class)->getUserTeams(Auth::user()->id),
            'leadStatuses' => app(DropdownSourceService::class)->getDropdownSource('quote_status_id', QuoteTypeId::Health),
            'coverFor' => HealthCoverFor::select('id', 'text')->where('is_active', true)->get(),
            'nationalities' => NationalityRepository::withActive()->get(),
            'emirateOfVisa' => Emirate::withActive()->get(),
            'healthLeadType' => HealthLeadType::get(),
            'memberCategories' => app(LookupService::class)->getMemberCategories(),
            'salaryBand' => app(LookupService::class)->getSalaryBands(),
            'gender' => app(CRUDService::class)->getGenderOptions(),
        ];

        return $result;
    }

    public function fetchGetBy($column, $value)
    {
        $quote = HealthQuote::with([
            'paymentStatus',
            'quoteStatus',
            'advisor',
            'salaryBand',
            'memberCategory',
            'currentProvider',
            'healthLeadType',
            'healthCoverFor',
            'healthQuoteRequestDetail.lostReason',
        ])
            ->where([
                $column => $value,
                // 'source' => LeadSourceEnum::REVIVAL,
            ])->firstOrFail();

        return $quote;
    }
}
