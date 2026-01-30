<?php

declare(strict_types=1);

namespace App\Services\CQF\Contracts;

use App\Models\RenewalsUploadLeads;
use Illuminate\Database\Eloquent\Model;

interface CQFQuoteMappingInterface
{
    /**
     * Map quote to renewal lead data for storage.
     *
     * @return array<string, mixed>
     */
    public function mapRenewalQuote(Model $quote, RenewalsUploadLeads $renewalsUploadLeads, string $quoteUuid): array;

    /**
     * Map failed quote data for export (e.g. failed validation export).
     *
     * @return array<string, mixed>
     */
    public function mapFailedQuoteData(Model $quote): array;
}
