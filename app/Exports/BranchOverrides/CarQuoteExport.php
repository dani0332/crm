<?php

namespace App\Exports\BranchOverrides;

use App\Enums\AMLStatusCode;
use App\Enums\AssignmentTypeEnum;
use App\Enums\LeadAssignmentTriggerEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypeId;
use App\Enums\UserNameEnum;
use App\Models\User;
use App\Services\BranchAssignmentService;
use App\Services\CarQuoteService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Traits\ExcelExportable;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class CarQuoteExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStrictNullComparison
{
    use ExcelExportable;

    public function __construct(
        private CarQuoteService $carQuoteService
    ) {}

    private function getQuoteQuery($requestParams = [])
    {
        $user = User::where('name', UserNameEnum::System)->first();
        $requestParams = [
            'user' => $user,
            'booking_date' => [
                now()->subDays(7)->startOfDay()->format(config('constants.DB_DATE_FORMAT_MATCH')),
                now()->subDays(1)->endOfDay()->format(config('constants.DB_DATE_FORMAT_MATCH')),
            ],
        ];

        $query = $this->carQuoteService->getGridData(requestParams: $requestParams)
            ->with('branchOverride',
                'branchOverride.branchOverrideConfig',
                'branchOverride.branchOverrideConfig.sourceBranch',
                'branchOverride.branchOverrideConfig.targetBranch',
                'branchOverride.branchOverrideConfig.quoteType',
                'payments')
            ->whereHas('branchOverride');

        return $query;
    }

    public function collection($requestParams = [])
    {
        return $this->getQuoteQuery($requestParams)->get();
    }

    public function getQuery($requestParams = []): ?Builder
    {
        return $this->getQuoteQuery($requestParams);
    }

    /**
     * Define the CSV headings
     */
    public function headings(): array
    {
        return [
            'CDB ID',
            'BATCH',
            'FIRST NAME',
            'LAST NAME',
            'DATE OF BIRTH',
            'LEAD SOURCE',
            'NATIONALITY',
            'UAE LICENCE HELD FOR',
            'CAR MAKE',
            'CAR MODEL',
            'CAR MODEL YEAR',
            'FIRST REGISTRATION DATE',
            'CAR VALUE',
            'CAR VALUE (AT ENQUIRY)',
            'VEHICLE TYPE',
            'TYPE OF CAR INSURANCE',
            'CURRENTLY INSURED WITH',
            'CLAIM HISTORY',
            'CREATED DATE',
            'ADVISOR ASSIGNED DATE',
            'LEAD COST',
            'LEAD STATUS',
            'AML STATUS',
            'INSURER AML STATUS',
            'PAYMENT STATUS',
            'ECOMMERCE',
            'TIER NAME',
            'VISIT COUNT',
            'FOLLOW UP DATE',
            'API ISSUANCE STATUS',
            'INSURER API STATUS',
            'LAST MODIFIED DATE',
            'UPDATED BY',
            'ADDITIONAL NOTES',
            'ADVISOR',
            'BRANCH',
            'POLICY NUMBER',
            'POLICY EXPIRY DATE',
            'IS GCC STANDARD',
            'IS VEHICLE MODIFIED',
            'PREMIUM',
            'LOST REASON',
            'QUOTE LINK',
            'RENEWAL BATCH',
            'PREVIOUS POLICY EXPIRY DATE',
            'PREVIOUS POLICY PREMIUM',
            'PREVIOUS POLICY NUMBER',
            'TRANSACTION APPROVED DATE',
            'BOOKING DATE',
            'ASSIGNMENT TYPE',
            'ADVISOR REQUESTED',
            'SEGMENT',
            'LEAD ASSIGNMENT TRIGGER',
            'PRIVATE CLIENT',
            'INSURER',
            'IMCRM SUB-SOURCE',
            'REPAIR TYPE',
            'ENGAGEMENT LEVEL',
            'Override Flag',
            'Override Reason',
            'Original Branch',
            'Target Branch',
            'Override Applied Date',
            'Total Commission',
            'Commission %',
        ];
    }

    /**
     * Map a database record to CSV row
     */
    public function map($quote): array
    {
        $branchName = ! $quote->is_branch_applicable ? 'N/A' : ($quote?->branch?->name ?? app(BranchAssignmentService::class)->getBranchName($quote?->advisor?->primaryBranch?->branch_id, QuoteTypeId::Car));

        return [
            $quote->code,
            $quote->batch?->name,
            $quote->first_name,
            $quote->last_name,
            $quote->dob_formatted ?? '',
            $quote->source,
            $quote->nationality?->text,
            $quote->uaeLicenseHeldFor?->text,
            $quote->carMake?->text,
            $quote->carModel?->text,
            $quote->year_of_manufacture,
            $quote->year_of_first_registration,
            $quote->car_value,
            $quote->car_value_tier,
            $quote->vehicleType?->text,
            $quote->carTypeInsurance?->text,
            $quote->currently_insured_with,
            $quote->claimHistory?->text,
            date(config('constants.datetime_format'), strtotime($quote->created_at)),
            $quote->carQuoteRequestDetail?->advisor_assigned_date_formatted ?? '',
            $quote->tier?->cost_per_lead,
            $quote->quoteStatus?->text,
            AMLStatusCode::getName($quote->aml_status) ?? '',
            $quote->insurer_aml_status_text,
            $quote->paymentStatus?->text,
            $quote->is_ecommerce ? 'Yes' : 'No',
            $quote->tier?->name,
            $quote->quoteViewCount?->visit_count,
            $quote->carQuoteRequestDetail?->next_followup_date_formatted ?? '',
            $quote->api_issuance_status_id ? PolicyIssuanceEnum::getAPIIssuanceStatuses($quote->api_issuance_status_id) : 'N/A',
            $quote->insurer_api_status_id ? app(PolicyIssuanceService::class)->getInsurerAPIStatuses($quote->insurer_api_status_id) : 'N/A',
            date(config('constants.datetime_format'), strtotime($quote->updated_at)),
            $quote->updated_by,
            $quote->additional_notes,
            $quote->advisor?->name,
            $branchName,
            $quote->policy_number,
            $quote->policy_expiry_date ? date(config('constants.datetime_format'), strtotime($quote->policy_expiry_date)) : '',
            $quote->is_gcc_standard ? 'Yes' : 'No',
            $quote->is_modified ? 'Yes' : 'No',
            $quote->premium,
            $quote->carQuoteRequestDetail?->lostReason?->text ?? '',
            $quote->quote_link,
            $quote->renewal_batch,
            $quote->previous_policy_expiry_date_formatted ?? '',
            $quote->previous_quote_policy_premium ?? '',
            $quote->previous_quote_policy_number ?? '',
            $quote->transaction_approved_at ? date(config('constants.datetime_format'), strtotime($quote->transaction_approved_at)) : '',
            $quote->policy_booking_date ? date(config('constants.datetime_format'), strtotime($quote->policy_booking_date)) : '',
            $quote->assignment_type ? AssignmentTypeEnum::getAssignmentTypeText($quote->assignment_type) : '',
            (isset($quote->sic_advisor_requested) && $quote->sic_advisor_requested) ? 'Yes' : 'No',
            $quote->getSegments($quote, QuoteTypeId::Car) ?? '',
            $quote->lead_assignment_trigger ? LeadAssignmentTriggerEnum::getAssignmentTypeText($quote->lead_assignment_trigger) : '',
            $quote->customer?->pcp_tag_formatted ?? '',
            $quote->insuranceProvider?->text ?? '',
            $quote->subSource?->text ?? '',
            $quote->plan?->repair_type ?? '',
            $quote->carQuoteRequestDetail?->engagement_level ?? '',
            'TRUE',
            $quote->branchOverride?->branchOverrideConfig?->override_text ?? '',
            $quote->branchOverride?->branchOverrideConfig?->sourceBranch?->name ?? '',
            $quote->branchOverride?->branchOverrideConfig?->targetBranch?->name ?? '',
            date(config('constants.DATE_FORMAT'), strtotime($quote->branchOverride?->created_at)),
            $quote->payments?->first()?->commission ?? '0',
            ($quote->payments?->first()?->commmission_percentage ?? '0').'%',
        ];
    }

    /**
     * Get export metadata with car-specific information
     */
    public function getExportMetadata(array $requestParams = []): array
    {
        return [
            'exportClass' => static::class,
            'timestamp' => now()->toISOString(),
            'parameters' => $requestParams,
            'sourceTable' => 'personal_quotes',
            'quoteTypeId' => QuoteTypeId::Car,
            'exportType' => 'car_quotes',
        ];
    }
}
