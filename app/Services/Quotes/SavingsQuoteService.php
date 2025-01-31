<?php

namespace App\Services\Quotes;

use App\Enums\QuoteTypes;

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
}
