<?php

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Services\CRUDService;
use App\Services\HealthQuoteService;
use App\Traits\ModernCsvExportable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class HealthQuotesExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    private $genderOptions;

    public function __construct(
        private HealthQuoteService $healthQuoteService,
        private CRUDService $crudService
    ) {
        $this->genderOptions = $this->crudService->getGenderOptions();
    }

    public function collection(array $requestParams = []): Collection
    {
        return $this->healthQuoteService->getGridData(requestParams: $requestParams)->get();
    }

    /**
     * Get the query builder instance to use for chunking
     * This is the key to memory-efficient CSV exports
     */
    public function getQuery(array $requestParams = []): ?Builder
    {
        return $this->healthQuoteService->getGridData(requestParams: $requestParams);
    }

    public function headings(): array
    {
        return [
            'Ref-ID',
            'FIRST NAME',
            'LAST NAME',
            'EMIRATE OF VISA',
            'LEAD STATUS',
            'ADVISOR',
            'BRANCH',
            'ADVISOR EMAIL',
            'WC ADVISOR',
            'CREATED DATE',
            'ADVISOR ASSIGNED DATE',
            'LAST MODIFIED DATE',
            'HEALTH TEAM TYPE',
            'TRANSAPP CODE',
            'LOST REASON',
            'STARTING FROM',
            'PREMIUM',
            'POLICY NUMBER',
            'SOURCE',
            'LEAD TYPE',
            'SALARY BAND',
            'MEMBER CATEGORY',
            'CURRENTLY INSURED WITH',
            'IS ECOMMERCE',
            'Device',
            'Gender',
            'Nationality',
            'Age Bands',
            'FOR WHOM DO YOU REQUIRE HEALTH INSURANCE?',
            'TYPE OF PLAN',
            'Provider Name',
            'RENEWAL BATCH',
            'PREVIOUS POLICY EXPIRY DATE',
            'PREVIOUS POLICY PREMIUM',
            'PREVIOUS POLICY NUMBER',
            'TRANSACTION APPROVED DATE',
            'BOOKING DATE',
            'PAYMENT STATUS',
            'ADVISOR CAR TEAM(s)',
            'PRIVATE CLIENT',
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->code,
            $quote->first_name,
            $quote->last_name,
            $quote->emirate?->text,
            $quote->quoteStatus?->text,
            $quote->advisor?->name,
            app(HealthQuoteService::class)->getBranchName($quote->emirate->id, $quote->advisor?->primaryBranch?->branch?->name),
            $quote->advisor?->email,
            $quote->wcAdvisor?->name,
            date(config('constants.datetime_format'), strtotime($quote->created_at)),
            isset($quote->healthQuoteRequestDetail->advisor_assigned_date) ? date(config('constants.datetime_format'), strtotime($quote->healthQuoteRequestDetail->advisor_assigned_date)) : '',
            date(config('constants.datetime_format'), strtotime($quote->updated_at)),
            $quote->health_team_type,
            $quote->healthQuoteRequestDetail?->transapp_code,
            $quote->healthQuoteRequestDetail?->lostReason?->text,
            $quote->price_starting_from,
            $quote->premium,
            $quote->policy_number,
            $quote->source,
            $quote->healthLeadType?->text,
            $quote->salaryBand?->text,
            $quote->memberCategory?->text,
            $quote->currentProvider?->text,
            $quote->is_ecommerce ? 'Yes' : 'No',
            $quote->device,
            $this->genderOptions[$quote->gender] ?? '',
            $quote->nationality?->text,
            Carbon::parse($quote->dob)->age,
            $quote->customer_type,
            $quote->plan?->text,
            $quote->insuranceProvider?->text,
            $quote->renewalBatchModel?->name,
            $quote->previous_policy_expiry_date_formatted,
            $quote->previous_quote_policy_premium ? $quote->previous_quote_policy_premium : '',
            $quote->previous_quote_policy_number ? $quote->previous_quote_policy_number : '',
            $quote->transaction_approved_at ? date(config('constants.datetime_format'), strtotime($quote->transaction_approved_at)) : '',
            $quote->policy_booking_date ? date(config('constants.datetime_format'), strtotime($quote->policy_booking_date)) : '',
            $quote->payment_status?->payment_status_text ?? 'N/A',
            $quote->car_teams ?? 'N/A',
            $quote->customer->pcp_tag_formatted ?? '',
        ];
    }

    /**
     * Get export metadata with health-specific information
     */
    public function getExportMetadata(array $requestParams = []): array
    {
        return [
            'exportClass' => static::class,
            'timestamp' => now()->toISOString(),
            'parameters' => $requestParams,
            'sourceTable' => 'personal_quotes',
            'quoteTypeId' => 3, // QuoteTypeId::Health
            'exportType' => 'health_quotes',
        ];
    }
}
