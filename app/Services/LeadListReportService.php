<?php

namespace App\Services;

use App\Models\LeadSource;
use App\Models\PaymentStatus;
use App\Enums\PaymentStatusEnum;

use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Models\Team;
use App\Models\Tier;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeadListReportService extends BaseService
{
    use GetUserTreeTrait;
    use TeamHierarchyTrait;

    public function getReportData($request)
    {

        $query = CarQuote::query()
        ->select(
            'users.id as advisor_id',
            'users.name as advisor',
            'car_quote_request.uuid as uuid',
            'tiers.name as tier',
            'car_quote_request.source as source',
            'car_quote_request.first_name as first_name',
            'car_quote_request.device as device',
            'car_quote_request.created_at as created_at',
            'car_quote_request.updated_at as updated_at',
            'car_quote_request.is_ecommerce as is_ecommerce',
            'quote_status.text as quoteStatus',
            'payment_status.text as payment_status_id',
        )
        ->join('users', 'users.id', 'car_quote_request.advisor_id')
        ->join('tiers', 'tiers.id', 'car_quote_request.tier_id')
        ->join('payment_status', 'payment_status.id', 'car_quote_request.payment_status_id')
        ->join('quote_status', 'quote_status.id', 'car_quote_request.quote_status_id')
        ->orderBy('car_quote_request.created_at', 'desc');

        $filters = [
            'uuid' => $request->uuid,
            'advisorAssignedDates' => $request->advisorAssignedDates,
            'tiersFilter' => $request->tiers,
            'leadSourceFilter' => $request->leadSources,
            'teamsFilter' => $request->teams,
            'ecommerceFilter' => $request->is_ecommerce,
            'paymentStatus' => $request->payment_status,
            'page' => $request->page,
        ];
        
        $query = $this->applyFilters($query, $filters); 
        
        return $query->simplePaginate(15)->withQueryString();
        // return $extendedQuery;

    }

    public function getFilterOptions()
    {
        $loginUserId = auth()->user()->id;
        $teamIds = $this->getUserTeams($loginUserId);
        $teams = Team::whereIn('id', $teamIds->pluck('id'))
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();
        $tiers = Tier::query()
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();
        $leadSource = LeadSource::query()
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($source) => $source->name)
            ->toArray();
         $paymentStatus = PaymentStatus::query()
            ->orderBy('text')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($paymentStatus) => $paymentStatus->text)
            ->toArray();
        
        return [
            'tiers' => $tiers,
            'teams' => $teams,
            'leadSource' => $leadSource,
            'paymentStatus' => $paymentStatus,
        ];
    }

    public function getDefaultFilters()
    {
        $dateFormat = config('constants.DATE_FORMAT_ONLY');
        $advisorAssignedDates = [
            Carbon::parse(now())->startOfDay()->format($dateFormat),
            Carbon::parse(now())->endOfDay()->format($dateFormat),
        ];

        return [
            'advisorAssignedDates' => $advisorAssignedDates,
        ];
    }

    public function applyFilters($query, $filters)
    {
        $filters = (object) $filters;
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $freshLoad = ! isset($filters->page);

        $startDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[0])->startOfDay()->format($dateFormat) :
                ($freshLoad ? Carbon::parse(now())->startOfDay()->format($dateFormat) : Carbon::parse(now()->subDays($maxDays))->startOfDay()->format($dateFormat));

        $endDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[1])->endOfDay()->format($dateFormat) : Carbon::parse(now())->endOfDay()->format($dateFormat);
        
        $query->whereDate('car_quote_request.created_at', '>=', $startDate);
        $query->whereDate('car_quote_request.created_at', '<=', $endDate);
        
        if (isset($filters->uuid)  && is_string($filters->uuid)) {
            $query->where('car_quote_request.uuid', $filters->uuid);
        }

        if (isset($filters->tiers) && count($filters->tiers) > 0) {
            info('tiersFilter are : '.json_encode($filters->tiers));
            $query->whereIn('car_quote_request.tier_id', $filters->tiers);
        }

        if (isset($filters->teams) && count($filters->teams) > 0) {
            info('teamsFilter are : '.json_encode($filters->teams));
            $value = $filters->teams;
            $query->whereIn('users.id', function ($query) use ($value) {
                $query->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', 'user_team.team_id')
                    ->whereIn('teams.id', $value);
            });
        }

        if (isset($filters->tiersFilter) && count($filters->tiersFilter) > 0) {
            info('tiersFilter are : '.json_encode($filters->tiersFilter));
            $query->whereIn('car_quote_request.tier_id', $filters->tiersFilter);
        }

        if (isset($filters->leadSourceFilter) && count($filters->leadSourceFilter) > 0) {
            info('leadSourceFilter are : '.json_encode($filters->leadSourceFilter));
            $query->whereIn('car_quote_request.source', $filters->leadSourceFilter);
        }

        if (isset($filters->paymentStatus) && count($filters->paymentStatus) > 0) {
            info('paymentStatus are : '.json_encode($filters->paymentStatus));
            $query->whereIn('car_quote_request.payment_status_id', $filters->paymentStatus);
        }

        if (isset($filters->ecommerceFilter) && $filters->ecommerceFilter != 'All') {
            info('ecommerceFilter are : '.json_encode($filters->ecommerceFilter));
            $query->where('car_quote_request.is_ecommerce', $filters->ecommerceFilter == 'Yes' ? 1 : 0);
        }

        return $query;
    }
}
