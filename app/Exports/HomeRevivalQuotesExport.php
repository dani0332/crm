<?php

declare(strict_types=1);

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Enums\QuoteTypes;
use App\Services\BranchAssignmentService;
use App\Services\HomeRevivalService;
use App\Traits\ModernCsvExportable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class HomeRevivalQuotesExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    public function __construct(
        protected HomeRevivalService $homeRevivalService,
    ) {}

    public function collection(array $requestParams = []): Collection
    {
        return $this->homeRevivalService->getExportQuery($requestParams)->get();
    }

    public function getQuery(array $requestParams = []): ?Builder
    {
        return $this->homeRevivalService->getExportQuery($requestParams);
    }

    public function headings(): array
    {
        return [
            'REF-ID',
            'FIRST NAME',
            'LAST NAME',
            'LEAD STATUS',
            'ADVISOR',
            'BRANCH',
            'CREATED DATE',
            'ADVISOR ASSIGNED DATE',
            'LAST MODIFIED DATE',
            'TRANSAPP CODE',
            'SOURCE',
            'LOST REASON',
            'PREMIUM',
            'POLICY NUMBER',
            'RENEWAL BATCH',
            'PREVIOUS POLICY EXPIRY DATE',
            'PREVIOUS POLICY PREMIUM',
            'PREVIOUS POLICY NUMBER',
            'TRANSACTION APPROVED DATE',
            'BOOKING DATE',
            'PRIVATE CLIENT',
            'IMCRM SUB-SOURCE',
        ];
    }

    public function map($quote): array
    {
        $branchName = ! $quote->is_branch_applicable
            ? 'N/A'
            : ($quote?->branch?->name ?? app(BranchAssignmentService::class)->getBranchName(
                $quote?->advisor?->primaryBranch?->branch_id,
                QuoteTypes::getIdFromValue(QuoteTypes::HOME->value)
            ));

        return [
            $quote->code,
            $quote->first_name,
            $quote->last_name,
            optional($quote->quoteStatus)->text ?? '',
            optional($quote->advisor)->name,
            $branchName,
            date(config('constants.datetime_format'), strtotime($quote->created_at)),
            isset($quote->quoteDetail?->advisor_assigned_date) ? date(config('constants.datetime_format'), strtotime($quote->quoteDetail->advisor_assigned_date)) : '',
            date(config('constants.datetime_format'), strtotime($quote->updated_at)),
            $quote?->homeQuote?->homeQuoteRequestDetail?->transapp_code ?? '',
            $quote->source,
            $quote?->homeQuote?->homeQuoteRequestDetail?->lostReason?->text ?? '',
            $quote->premium ?: $quote->price_with_vat,
            $quote->policy_number,
            $quote->renewal_batch,
            $quote->previous_policy_expiry_date ? date('d-M-Y', strtotime($quote->previous_policy_expiry_date)) : '',
            $quote->previous_quote_policy_premium ?? '',
            $quote->previous_quote_policy_number ?? '',
            $quote->transaction_approved_at ? date(config('constants.datetime_format'), strtotime($quote->transaction_approved_at)) : '',
            $quote->policy_booking_date ? date(config('constants.datetime_format'), strtotime($quote->policy_booking_date)) : '',
            $quote->customer?->pcp_tag_formatted ?? '',
            optional($quote->subSource)->text,
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
            'quoteTypeId' => 2,
            'exportType' => 'home_revival_quotes',
        ];
    }
}
