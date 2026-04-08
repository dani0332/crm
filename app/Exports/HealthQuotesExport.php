<?php

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Enums\HealthPlanTypeEnum;
use App\Enums\QuoteTypeId;
use App\Services\BranchAssignmentService;
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
            'POLICY PEC FLAG',
            'LEAD STATUS',
            'ADVISOR',
            'OE/AE',
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
            'PLAN NAME',
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
            'IMCRM SUB-SOURCE',
        ];
    }

    public function map($quote): array
    {
        $branchName = ! $quote->is_branch_applicable ? 'N/A' : ($quote?->branch?->name ?? app(BranchAssignmentService::class)->getBranchName($quote?->advisor?->primaryBranch?->branch_id, QuoteTypeId::Health, $quote->emirate_of_your_visa_id));

        return [
            $quote->code,
            $quote->first_name,
            $quote->last_name,
            $quote->emirate?->text,
            $quote->has_pec_tag ? 'Yes' : 'No',
            $quote->quoteStatus?->text,
            $quote->advisor?->name,
            $quote->supportUser?->name ?? '',
            $branchName,
            $quote->advisor?->email,
            $quote->wcAdvisor?->name,
            date(config('constants.datetime_format'), strtotime($quote->created_at)),
            isset($quote->healthQuoteRequestDetail->advisor_assigned_date) ? date(config('constants.datetime_format'), strtotime($quote->healthQuoteRequestDetail->advisor_assigned_date)) : '',
            date(config('constants.datetime_format'), strtotime($quote->updated_at)),
            $quote->health_team_type ?? $quote->notional_team,
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
            HealthPlanTypeEnum::typeText($quote->health_plan_type_id),
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
            $quote->subSource?->text,
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
