<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Services\CQF\NonMotor\BaseCQFQuoteMappingService;
use Illuminate\Database\Eloquent\Model;

class CycleCQFQuoteMappingService extends BaseCQFQuoteMappingService
{
    protected function getQuoteType(): QuoteTypes
    {
        return QuoteTypes::CYCLE;
    }

    protected function getQuoteTypeId(): int
    {
        return QuoteTypeId::Cycle;
    }

    protected function getProductName(): string
    {
        return 'Cycle insurance';
    }

    public function mapRenewalQuote(Model $quote, RenewalsUploadLeads $renewalsUploadLeads, string $quoteUuid): array
    {
        $data = parent::mapRenewalQuote($quote, $renewalsUploadLeads, $quoteUuid);
        $data['asset_value'] = $quote->asset_value;

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getFailedQuoteDataExtra(PersonalQuote $quote): array
    {
        $cycleQuote = $quote->cycleQuote;

        return [
            'cycle_make' => $cycleQuote?->cycle_make ?? null,
            'cycle_model' => $cycleQuote?->cycle_model ?? null,
            'year_of_manufacture_id' => $cycleQuote?->year_of_manufacture_id ?? null,
        ];
    }
}
