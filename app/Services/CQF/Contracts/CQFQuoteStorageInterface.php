<?php

declare(strict_types=1);

namespace App\Services\CQF\Contracts;

use App\Models\RenewalsUploadLeads;
use Illuminate\Database\Eloquent\Model;

interface CQFQuoteStorageInterface
{
    /**
     * Store CQF renewal quote (new lead) from existing quote.
     *
     * @param  array<int, string>  $epCodes  Optional reference to collect embedded product codes
     * @return Model|null Created quote model or null on failure
     */
    public function storeRenewalQuote(
        Model $quote,
        RenewalsUploadLeads $renewalsUploadLeads,
        array &$epCodes = []
    ): ?Model;
}
