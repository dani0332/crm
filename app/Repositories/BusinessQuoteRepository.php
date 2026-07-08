<?php

namespace App\Repositories;

use App\Enums\CustomerTypeEnum;
use App\Enums\quoteStatusCode;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\BusinessQuote;
use App\Services\BranchAssignmentService;
use App\Traits\CentralTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BusinessQuoteRepository extends BaseRepository
{
    use CentralTrait;

    /**
     * Normalize emirate filter input into positive integer IDs.
     *
     * @return array<int>
     */
    public static function normalizeEmirateOfRegistrationIds(mixed $rawInput): array
    {
        $values = is_array($rawInput) ? $rawInput : [$rawInput];

        return array_values(array_filter(
            array_map('intval', $values),
            static fn (int $id): bool => $id > 0
        ));
    }

    public function model()
    {
        return BusinessQuote::class;
    }

    public function fetchExport()
    {
        return $this->filter(paginate: false)->with(
            ['advisor', 'nationality', 'insuranceProvider', 'businessTypeOfInsurance', 'subSource', 'previousAdvisor', 'personalQuote.currentlyInsuredWith']
        )->orderBy('created_at', 'desc');
    }

    /**
     * @return mixed
     */
    public function fetchGetData($quoteType, $forExport = false, $forTotalLeadsCount = false, $requestParams = [])
    {
        if (! Auth::check()) {
            $user = $requestParams['user'] ?? null;
            unset($requestParams['user']);
            Auth::login($user);
            DB::setDefaultConnection('mysql_read');
            request()->merge($requestParams);
        }

        $with = [
            'businessQuoteRequestDetail.lostReason',
            'quoteStatus',
            'advisor',
            'advisor.primaryBranch',
            'supportUser',
            'businessTypeOfInsurance',
            'subSource',
            'branch:id,name',
            'leadGenerator:id,name',
        ];
        if ($quoteType == quoteTypeCode::GroupMedical) {
            $with[] = 'emirate';
        }
        $query = $this->with($with)->whereHas('businessTypeOfInsurance', function ($businessTypeOfInsurance) use ($quoteType) {
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
            $query->where('advisor_id', auth()->id());
        })->when(($quoteType == quoteTypeCode::CORPLINE && (
            auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::CORPLINE) ||
            auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::Business) ||
            auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::Amt) ||
            auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::GM)
        )), function ($query) {
            $query->where('advisor_id', auth()->id());
        })
            ->filter(! $forExport, $forTotalLeadsCount, $requestParams)
            ->withFakeLeadCriteria($forTotalLeadsCount);
        $this->adjustQueryByDateFilters($query, 'business_quote_request', $requestParams);
        $query->orderBy('business_quote_request.created_at', 'desc');

        // Apply sub_source_id filter when present
        if (! empty($requestParams['sub_source_id'])) {
            $values = (array) $requestParams['sub_source_id'];
            $query->whereIn('business_quote_request.sub_source_id', $values);
        }
        // apply assignment_type filter when present
        if (! empty($requestParams['assignment_type'])) {
            $assignmentTypes = (array) $requestParams['assignment_type'];
            if (! in_array('all', $assignmentTypes)) {
                $query->whereIn('business_quote_request.assignment_type', $assignmentTypes);
            }
        }
        if (! empty($requestParams['pq_advisor_id']) && is_array($requestParams['pq_advisor_id'])) {
            $query->whereIn('business_quote_request.pq_advisor_id', $requestParams['pq_advisor_id']);
        }

        if (! empty($requestParams['lead_type'])) {
            $values = (array) $requestParams['lead_type'];
            $query->whereIn('business_quote_request.lead_type', $values);
        }

        if (! empty($requestParams['emirate_of_registration_id']) && $quoteType == quoteTypeCode::GroupMedical) {
            $ids = self::normalizeEmirateOfRegistrationIds($requestParams['emirate_of_registration_id']);
            if ($ids !== []) {
                $query->whereIn('business_quote_request.emirate_of_registration_id', $ids);
            }
        }

        $filtersForAdvisorDate = ! empty($requestParams) ? $requestParams : request()->all();
        if (! empty($filtersForAdvisorDate['advisor_assigned_date'])) {
            $dateRange = $filtersForAdvisorDate['advisor_assigned_date'];
            while (is_array($dateRange) && isset($dateRange[0]) && is_array($dateRange[0])) {
                $dateRange = $dateRange[0];
            }
            if (is_array($dateRange) && count($dateRange) >= 2) {
                $dateFrom = Carbon::parse($dateRange[0])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::parse($dateRange[1])->endOfDay()->toDateTimeString();
                $query->whereHas('businessQuoteRequestDetail', function ($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('advisor_assigned_date', [$dateFrom, $dateTo]);
                });
            }
        }

        if ($forTotalLeadsCount) {
            return $query->count();
        }

        return ($forExport) ? $query : $query->simplePaginate();
    }

    /**
     * @return mixed
     */
    public function fetchGetBy($queryWhere)
    {
        $quote = $this->where($queryWhere)
            ->with([
                'previousQuote:id,uuid,code',
                'renewalBatchModel',
                'advisor',
                'preQualificationAdvisor',
                'advisor.primaryBranch',
                'supportUser',
                'previousAdvisor',
                'businessQuoteRequestDetail.lostReason',
                'customer',
                'transactionType',
                'insuranceProviderDetails',
                'latestInsured',
                'latestInsured.insuredKyc:id,insured_id',
                'payments' => function ($q) {
                    $q->with(['paymentStatus', 'personalPlan', 'paymentMethod',
                        'paymentSplits.paymentStatus',
                        'paymentSplits.paymentMethod',
                        'paymentSplits.verifiedByUser',
                        'paymentSplits.documents',
                        'paymentSplits.processJob',
                        'paymentSplits.paymentCharges',
                    ]);
                },
                'quoteRequestEntityMapping' => function ($entityMapping) {
                    $entityMapping->with('entity');
                },
                'documents' => function ($q) {
                    $q->with('createdBy')->orderBy('created_at', 'desc');
                },
                'nationality',
                'branch:id,name',
                'emirate:id,text',
                'personalQuote.currentlyInsuredWith:id,text',
            ])
            ->select([
                $this->getTable().'.*',
            ])
            ->firstOrFail();

        $quote->customer_type = $quote->latestInsured?->customer_type ?? CustomerTypeEnum::Entity;

        if (isset($quote->latestInsured)) {
            $quote->emirates_id_number = $quote->latestInsured['id_type'] == 'emiratesId' ? $quote->latestInsured['id_number'] : null;
        }

        $emirateOfRegistrationId = $quote->emirate_of_registration_id ?? null;
        $quote->branch_name = ! $quote->is_branch_applicable ? 'N/A' : ($quote->branch?->name ?? app(BranchAssignmentService::class)->getBranchName($quote->advisor?->primaryBranch?->branch_id, QuoteTypeId::GroupMedical, $emirateOfRegistrationId));

        return $quote;
    }

    public function fetchCreateDuplicate(array $dataArr): object
    {
        return Capi::request('/api/v1-save-'.strtolower(QuoteTypes::BUSINESS->value).'-quote', 'post', $dataArr);
    }
    public function fetchGetDataOfBusiness()
    {
        return $this->filter()->with(
            ['advisor', 'nationality', 'insuranceProvider', 'businessTypeOfInsurance', 'customer', 'personalQuote.currentlyInsuredWith'])->orderBy('created_at', 'desc')->Paginate();
    }

}
