<?php

namespace App\Exports\BranchOverrides;

use App\Enums\QuoteTypes;
use App\Repositories\BusinessQuoteRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use App\Services\BranchAssignmentService;
use App\Enums\QuoteTypeId;
use App\Traits\ExcelExportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use App\Models\User;
use App\Enums\UserNameEnum;

class BusinessQuoteExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStrictNullComparison
{
    use ExcelExportable;

    private function getQuoteQuery($requestParams = [])
    {
        $user = User::where('name', UserNameEnum::System)->first();
        $requestParams = [
            'user' => $user,
            'booking_date' => [
                now()->subDays(7)->startOfDay()->format(config('constants.DB_DATE_FORMAT_MATCH')),
                now()->subDays(1)->endOfDay()->format(config('constants.DB_DATE_FORMAT_MATCH')),
            ]
        ];

        $query = BusinessQuoteRepository::getData(QuoteTypes::CORPLINE->value, true, requestParams: $requestParams)
            ->with(
                'branchOverride',
                'branchOverride.branchOverrideConfig',
                'branchOverride.branchOverrideConfig.sourceBranch',
                'branchOverride.branchOverrideConfig.targetBranch',
                'branchOverride.branchOverrideConfig.quoteType',
                'payments'
            )
            ->whereHas('branchOverride');

        return $query;
    }

    public function collection($requestParams = []): Collection
    {
        return $this->getQuoteQuery($requestParams)->get();
    }

    /**
     * Get the query builder instance to use for chunking
     * This is the key to memory-efficient CSV exports
     */
    public function getQuery($requestParams = []): ?Builder
    {
        return $this->getQuoteQuery($requestParams);
    }

    public function headings(): array
    {
        return [
            'REF-ID',
            'FIRST NAME',
            'LAST NAME',
            'COMPANY NAME',
            'TRANSAPP CODE',
            'SOURCE',
            'POLICY NUMBER',
            'LOST REASON',
            'ADVISOR',
            'BRANCH',
            'LEAD STATUS',
            'CREATED DATE',
            'ADVISOR ASSIGNED DATE',
            'LAST MODIFIED DATE',
            'PREMIUM',
            'NUMBER OF EMPLOYEES',
            'BUSINESS INSURANCE TYPE',
            'GENDER',
            'RENEWAL BATCH',
            'PREVIOUS POLICY EXPIRY DATE',
            'PREVIOUS POLICY PREMIUM',
            'PREVIOUS POLICY NUMBER',
            'TRANSACTION APPROVED DATE',
            'BOOKING DATE',
            'IMCRM SUB-SOURCE',
            'Override Flag',
            'Override Reason',
            'Original Branch',
            'Target Branch',
            'Override Applied Date',
            'Total Commission',
            'Commission %',            
        ];
    }

    public function map($quote): array
    {
        $branch = ! $quote->is_branch_applicable ? 'N/A' : ($quote?->branch?->name ?? app(BranchAssignmentService::class)->getBranchName($quote?->advisor?->primaryBranch?->branch_id, QuoteTypeId::Business));
        return [
            $quote->code,
            $quote->first_name,
            $quote->last_name,
            $quote->company_name,
            optional($quote->businessQuoteRequestDetail)->transapp_code,
            $quote->source,
            $quote->policy_number,
            optional($quote->businessQuoteRequestDetail)->lostReason?->text,
            optional($quote->advisor)->name,
            $branch,
            optional($quote->quoteStatus)->text,
            date(config('constants.datetime_format'), strtotime($quote->created_at)),
            isset($quote->businessQuoteRequestDetail->advisor_assigned_date) ? date(config('constants.datetime_format'), strtotime($quote->businessQuoteRequestDetail->advisor_assigned_date)) : '',
            date(config('constants.datetime_format'), strtotime($quote->updated_at)),
            $quote->premium ? $quote->premium : $quote->price_with_vat,
            $quote->number_of_employees,
            optional($quote->businessTypeOfInsurance)->text,
            $quote->gender,
            $quote->renewal_batch,
            $quote->previous_policy_expiry_date ? date('d-M-Y', strtotime($quote->previous_policy_expiry_date)) : '',
            $quote->previous_quote_policy_premium ? $quote->previous_quote_policy_premium : '',
            $quote->previous_quote_policy_number ? $quote->previous_quote_policy_number : '',
            $quote->transaction_approved_at ? date(config('constants.datetime_format'), strtotime($quote->transaction_approved_at)) : '',
            $quote->policy_booking_date ? date(config('constants.datetime_format'), strtotime($quote->policy_booking_date)) : '',
            optional($quote->subSource)->text,
            'TRUE',
            $quote->branchOverride?->branchOverrideConfig?->override_text ?? '',
            $quote->branchOverride?->branchOverrideConfig?->sourceBranch?->name ?? '',
            $quote->branchOverride?->branchOverrideConfig?->targetBranch?->name ?? '',
            date(config('constants.DATE_FORMAT'), strtotime($quote->branchOverride?->created_at)),
            $quote->payments?->first()?->commission ?? '0',
            $quote->payments?->first()?->commmission_percentage . '%' ?? '0%',
        ];
    }

    /**
     * Get export metadata with business-specific information
     */
    public function getExportMetadata(array $requestParams = []): array
    {
        return [
            'exportClass' => static::class,
            'timestamp' => now()->toISOString(),
            'parameters' => $requestParams,
            'sourceTable' => 'personal_quotes',
            'quoteTypeId' => QuoteTypeId::Business,
            'exportType' => 'business_quotes',
        ];
    }
}
