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
use Illuminate\Support\Facades\DB;

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
        // dd($request->toArray());
        $query = HealthQuote::with([
            'paymentStatus',
            'quoteStatus',
            'advisor',
            'salaryBand',
            'memberCategory',
            'currentProvider',
            'healthLeadType',
            'healthQuoteRequestDetail.lostReason',
        ])->where('source', LeadSourceEnum::REVIVAL)->filter();

        if (! empty($request->assignment_type)) {
            $query->where('assignment_type', $request->assignment_type);
        }
        if (! empty($request->advisors)) {
            $query->whereIn('advisor_id', $request->advisors);
        }
        if (! empty($request->sub_team)) {
            $query->where('health_team_type', $request->sub_team);
        }
        if (! empty($request->quote_status)) {
            $query->whereIn('quote_status_id', $request->quote_status);
        }
        if (! empty($request->is_ecommerce)) {
            $isEcommerce = $request->is_ecommerce == 'Yes' ? 1 : 0;
            $query->where('is_ecommerce', $isEcommerce);
        }

        if (! empty($request->previous_quote_policy_number)) {
            $query->where('previous_quote_policy_number', $request->previous_quote_policy_number);
        }
        if (! empty($request->renewal_batch)) {
            $query->where('renewal_batch', $request->renewal_batch);
        }

        if (! empty($request->is_renewal)) {
            if ($request->is_renewal == quoteTypeCode::yesText) {
                $query->whereNotNull('previous_quote_policy_number');
            }
            if ($request->is_renewal == quoteTypeCode::noText) {
                $query->whereNull('previous_quote_policy_number');
            }
        }
        $query->when(request()->get('advisor_date_start'), function ($query) {
            $query->whereHas('healthQuoteRequestDetail', function ($advisorAssignDate) {
                if (isset(request()->advisor_date_start) && isset(request()->advisor_date_end)) {
                    $startDate = date('Y-m-d 00:00:00', strtotime(request()->advisor_date_start));
                    $endDate = date('Y-m-d 23:59:59', strtotime(request()->advisor_date_end));
                    $advisorAssignDate->whereBetween(DB::raw('date(advisor_assigned_date)'), [$startDate, $endDate]);
                }
            });
        });

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
