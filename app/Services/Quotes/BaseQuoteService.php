<?php

namespace App\Services\Quotes;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypes;
use App\Models\ApplicationStorage;
use App\Models\RenewalBatch;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\UserRepository;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

abstract class BaseQuoteService
{
    use GenericQueriesAllLobs;

    public function __construct(public QuoteTypes $quoteType) {}

    protected function baseQuery(): Builder
    {
        return $this->quoteType->model()
            ->when($this->quoteType->isPersonalQuote(), fn ($query) => $query->where('quote_type_id', $this->quoteType->id()))
            ->when($this->isAdvisor(), fn ($query) => $query->where('advisor_id', Auth::id()))
            ->filterByAdvisors(request('advisors'))
            ->orderBy((request()->sortBy ?? 'created_at'), request()->sortType ?? 'desc');
    }

    protected function isAdvisor()
    {
        return Auth::user()->hasAnyRole($this->quoteType->advisorRoles());
    }

    public function getAdvisors()
    {
        return UserRepository::getPersonalQuoteAdvisors($this->quoteType->value);
    }

    public function getQuoteStatuses($ignoreList = [])
    {
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId($this->quoteType->id())->get();

        return collect($quoteStatuses)->filter(fn ($value) => ! in_array($value['id'], $ignoreList))->values();
    }

    public function getRenewalBatches()
    {
        return RenewalBatch::getAllBatches($this->quoteType !== QuoteTypes::CAR);
    }

    public function getPaymentAuthorizedDays()
    {
        return ApplicationStorage::where('key_name', ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS)->first();
    }

    public function hasOtherFilters()
    {
        return count(array_diff_key(request()->all(), ['page' => ''])) > 0;
    }
}
