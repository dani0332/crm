<?php

namespace App\Repositories;

use App\Enums\CustomerTypeEnum;
use App\Enums\quoteStatusCode;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
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
            ['advisor', 'nationality', 'insuranceProvider', 'businessTypeOfInsurance']
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
            'businessTypeOfInsurance',
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
            ->filter(! $forExport, $forTotalLeadsCount)
            ->withFakeLeadCriteria($forTotalLeadsCount);
        $this->adjustQueryByDateFilters($query, 'business_quote_request');
        $query->orderBy('business_quote_request.created_at', 'desc');

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
                'insured' => function ($q) {
                    $q->where('customer_insured.quote_type_id', QuoteTypeId::Business);
                    $q->whereRaw('customer_insured.id = (SELECT MAX(id) FROM customer_insured WHERE quote_type_id = ? AND quote_request_id = '.$this->getTable().'.id)', [QuoteTypeId::Business]);
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
                // DB::raw('("'.CustomerTypeEnum::Entity.'") as customer_type'),
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
