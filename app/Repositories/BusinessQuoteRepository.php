<?php

namespace App\Repositories;

use App\Enums\CustomerTypeEnum;
use App\Enums\quoteStatusCode;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\BusinessQuote;
use App\Traits\CentralTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            ['advisor', 'nationality', 'insuranceProvider', 'businessTypeOfInsurance', 'subSource']
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

        $query = $this->with([
            'businessQuoteRequestDetail.lostReason',
            'quoteStatus',
            'advisor',
            'supportUser',
            'businessTypeOfInsurance',
            'subSource',
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
        $quoteTypeId = QuoteTypes::BUSINESS->id();
        $quote = $this->where($queryWhere)
            ->with([
                'advisor',
                'supportUser',
                'previousAdvisor',
                'businessQuoteRequestDetail.lostReason',
                'customer',
                'transactionType',
                'insuranceProviderDetails',
                'latestInsured' => function ($q) use ($quoteTypeId) {
                    $q->where('customer_insured.quote_type_id', $quoteTypeId);
                },
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
            ])
            ->select([
                $this->getTable().'.*',
            ])
            ->firstOrFail();

        $quote->customer_type = $quote->latestInsured?->customer_type ?? CustomerTypeEnum::Entity;

        return $quote;
    }

    public function fetchCreateDuplicate(array $dataArr): object
    {
        return Capi::request('/api/v1-save-'.strtolower(QuoteTypes::BUSINESS->value).'-quote', 'post', $dataArr);
    }
    public function fetchGetDataOfBusiness()
    {
        return $this->filter()->with(
            ['advisor', 'nationality', 'insuranceProvider', 'businessTypeOfInsurance'])->orderBy('created_at', 'desc')->Paginate();
    }

}
