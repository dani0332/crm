<?php

namespace App\Services\Quotes;

use App\Enums\GenderEnum;
use App\Enums\QuoteTypes;
use App\Models\Nationality;
use App\Models\CurrencyType;
use App\Models\MartialStatus;
use App\Enums\SavingsPurposeEnum;

class SavingsQuoteService extends BaseQuoteService
{
    public function __construct()
    {
        parent::__construct(QuoteTypes::SAVINGS);
    }

    public function getData(bool $paginted = false, bool $forExport = false, bool $getTotalCount = false)
    {
        $query = $this->baseQuery()->with([
            'quoteStatus',
            'currentlyInsuredWith',
            'advisor',
            'paymentStatus',
            'payments',
            'quoteDetail',
            'renewalBatchModel',
        ])
            ->filter(! $forExport, $getTotalCount)
            ->withFakeLeadCriteria($getTotalCount);

        $this->adjustQueryByInsurerInvoiceFilters($query);
        $this->adjustQueryByDateFilters($query, 'personal_quotes');

        return $query->resolveData($paginted, $forExport, $getTotalCount);
    }

    public function getFormOptions()
    {
        return [
            'nationalities' => Nationality::withActive()->options(),
            'genders' => GenderEnum::withLabels(),
            'maritalStatuses' => MartialStatus::withActive()->options(),
            'purposes' => SavingsPurposeEnum::withLabels(),
            'currencies' => CurrencyType::withActive()->options(),
        ];
    }
}
