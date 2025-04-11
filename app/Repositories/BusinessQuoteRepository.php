<?php

namespace App\Repositories;

use App\Enums\CustomerTypeEnum;
use App\Enums\quoteStatusCode;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\BusinessQuote;
use App\Traits\CentralTrait;
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
            ['advisor', 'nationality', 'insuranceProvider', 'businessTypeOfInsurance']
        )->orderBy('created_at', 'desc');
    }

    /**
     * @return mixed
     */
    public function fetchGetData($quoteType, $forExport = false, $forTotalLeadsCount = false,$requestParams = [])
    {
        $user = null;
        if (auth()->check() && empty($requestParams)) {
            $requestParams = collect(request()->all());
            $user = auth()->user();
        } elseif (! empty($requestParams)) {
            $requestParams = collect($requestParams);
            $user = $requestParams['user'];
        }

        $query = $this->with([
            'businessQuoteRequestDetail.lostReason',
            'quoteStatus',
            'advisor',
            'businessTypeOfInsurance',
        ])->whereHas('businessTypeOfInsurance', function ($businessTypeOfInsurance) use ($quoteType) {
            $businessTypeOfInsurance->when($quoteType == quoteTypeCode::GroupMedical, function ($groupMedical) {
                $groupMedical->where('text', quoteStatusCode::GROUP_MEDICAL);
            });
            $businessTypeOfInsurance->when($quoteType == quoteTypeCode::CORPLINE, function ($corpline) {
                $corpline->where('text', '!=', quoteStatusCode::GROUP_MEDICAL);
            });
        })->when(($quoteType == quoteTypeCode::GroupMedical && (
            $user->isSpecificTeamAdvisor(quoteTypeCode::Business) ||
            $user->isSpecificTeamAdvisor(quoteTypeCode::Amt) ||
            $user->isSpecificTeamAdvisor(quoteTypeCode::GM)
        )), function ($query) use ($user) {
            $query->where('advisor_id', $user->id);
        })->when(($quoteType == quoteTypeCode::CORPLINE && (
            $user->isSpecificTeamAdvisor(quoteTypeCode::CORPLINE) ||
            $user->isSpecificTeamAdvisor(quoteTypeCode::Business) ||
            $user->isSpecificTeamAdvisor(quoteTypeCode::Amt) ||
            $user->isSpecificTeamAdvisor(quoteTypeCode::GM)
        )), function ($query) use ($user) {
            $query->where('advisor_id', $user->id);
        })
            ->filter(! $forExport, $forTotalLeadsCount,requestParams: $requestParams)
            ->withFakeLeadCriteria($forTotalLeadsCount,requestParams: $requestParams);
        $this->adjustQueryByDateFilters($query, 'business_quote_request',requestParams: $requestParams);
        $query->orderBy('business_quote_request.created_at', 'desc');

        if ($forTotalLeadsCount) {
            return $query->count();
        }

        return ($forExport) ? $query->get() : $query->simplePaginate();
    }

    /**
     * @return mixed
     */
    public function fetchGetBy($queryWhere)
    {
        $quote = $this->where($queryWhere)
            ->with([
                'advisor',
                'previousAdvisor',
                'businessQuoteRequestDetail.lostReason',
                'customer',
                'transactionType',
                'insuranceProviderDetails',
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
                DB::raw('("'.CustomerTypeEnum::Entity.'") as customer_type'),
            ])
            ->firstOrFail();

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
