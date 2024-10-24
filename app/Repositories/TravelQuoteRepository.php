<?php

namespace App\Repositories;

use App\Enums\PolicyIssuanceEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\PolicyIssuance;
use App\Models\TravelQuote;
use App\Traits\CentralTrait;

class TravelQuoteRepository extends BaseRepository
{
    use CentralTrait;

    public const TYPE = quoteTypeCode::Travel;
    public const TYPE_ID = QuoteTypeId::Travel;

    public function model()
    {
        return TravelQuote::class;
    }

    public function fetchGetData($forExport = false)
    {
        $query = $this->with([
            'travelQuoteRequestDetail.lostReason',
            'quoteStatus',
            'travelCoverFor',
            'regionCoverFor',
            'advisor',
            'plan',
            'payments',
            'currentlyLocatedIn',
            'nationality',
            'destination',
            'paymentStatus',
            'insuranceProvider',
        ])
            ->filter(! $forExport)
            ->withFakeLeadCriteria();

        $this->adjustQueryByDateFilters($query, 'travel_quote_request');

        $query->orderBy('travel_quote_request.created_at', 'desc');

        return ($forExport) ? $query->get() : $query->simplePaginate();
    }
    public function fetchExport()
    {
        return $this->filter()->with(
            ['advisor', 'nationality', 'insuranceProvider'])->orderBy('created_at', 'desc');
    }

    public function fetchCreateDuplicate(array $dataArr): object
    {
        return Capi::request('/api/v1-save-'.strtolower(QuoteTypes::TRAVEL->value).'-quote', 'post', $dataArr);
    }

    public function createPolicyIssuanceSchedule($quote, $insurer)
    {
        return PolicyIssuance::create([
            'insurance_provider_id' => $insurer->id,
            'model_type' => $quote->getMorphClass(),
            'model_id' => $quote->id,
            'quote_type' => self::TYPE,
            'status' => PolicyIssuanceEnum::PENDING_STATUS,
        ]);
    }
}
