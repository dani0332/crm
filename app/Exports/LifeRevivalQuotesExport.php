<?php

declare(strict_types=1);

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Services\LifeRevivalService;
use App\Traits\ModernCsvExportable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LifeRevivalQuotesExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    public function __construct(
        protected LifeRevivalService $lifeRevivalService,
    ) {}

    public function collection(array $requestParams = []): Collection
    {
        return $this->lifeRevivalService->getExportQuery($requestParams)->get();
    }

    public function getQuery(array $requestParams = []): ?Builder
    {
        return $this->lifeRevivalService->getExportQuery($requestParams);
    }

    public function headings(): array
    {
        return [
            'Ref-ID',
            'FIRST NAME',
            'LAST NAME',
            'LEAD STATUS',
            'ADVISOR',
            'CREATED DATE',
            'ADVISOR ASSIGNED DATE',
            'LAST MODIFIED DATE',
            'TRANSAPP CODE',
            'PREMIUM',
            'POLICY NUMBER',
            'SOURCE',
            'LOST REASON',
            'IS ECOMMERCE',
            'RENEWAL BATCH',
            'PREVIOUS POLICY EXPIRY DATE',
            'PREVIOUS POLICY PREMIUM',
            'PREVIOUS POLICY NUMBER',
            'TRANSACTION APPROVED DATE',
            'BOOKING DATE',
            'PRIVATE CLIENT',
            'CURRENCY',
            'SUM ASSURED',
            'SUM ASSURED CURRENCY',
            'POLICY SUM ASSURED',
        ];
    }

    public function map($quote): array
    {
        $quoteDetail = $quote->quoteDetail;

        return [
            $quote->code,
            $quote->first_name,
            $quote->last_name,
            optional($quote->quoteStatus)->text ?? '',
            optional($quote->advisor)->name,
            date(config('constants.datetime_format'), strtotime($quote->created_at)),
            isset($quoteDetail?->advisor_assigned_date) ? date(config('constants.datetime_format'), strtotime($quoteDetail->advisor_assigned_date)) : '',
            date(config('constants.datetime_format'), strtotime($quote->updated_at)),
            $quoteDetail?->transapp_code ?? $quote->transapp_code ?? '',
            $quote->premium ? $quote->premium : $quote->price_with_vat,
            $quote->policy_number,
            $quote->source,
            optional($quoteDetail)?->lostReason?->text ?? '',
            $quote->is_ecommerce ? 'Yes' : 'No',
            $quote->renewal_batch,
            $quote->previous_policy_expiry_date ? date('d-M-Y', strtotime($quote->previous_policy_expiry_date)) : '',
            $quote->previous_quote_policy_premium ? $quote->previous_quote_policy_premium : '',
            $quote->previous_quote_policy_number ? $quote->previous_quote_policy_number : '',
            $quote->transaction_approved_at ? date(config('constants.datetime_format'), strtotime($quote->transaction_approved_at)) : '',
            $quote->policy_booking_date ? date(config('constants.datetime_format'), strtotime($quote->policy_booking_date)) : '',
            $quote->customer?->pcp_tag_formatted ?? '',
            optional($quote->lifeQuote)?->sumInsuredCurrency?->text ?? '',
            optional($quote->lifeQuote)?->sum_insured_value ?? '',
            optional($quote->lifeQuote)?->policySumAssuredCurrency?->text ?? '',
            optional($quote->lifeQuote)?->policy_sum_assured ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getExportMetadata(array $requestParams = []): array
    {
        return [
            'exportClass' => static::class,
            'timestamp' => now()->toISOString(),
            'parameters' => $requestParams,
            'sourceTable' => 'personal_quotes',
            'quoteTypeId' => 4,
            'exportType' => 'life_revival_quotes',
        ];
    }
}
