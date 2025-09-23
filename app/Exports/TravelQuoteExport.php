<?php

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Enums\AMLStatusCode;
use App\Enums\LeadAssignmentTriggerEnum;
use App\Enums\QuoteTypeId;
use App\Services\TravelQuoteService;
use App\Traits\ModernCsvExportable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TravelQuoteExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    public function __construct(
        private TravelQuoteService $travelQuoteService
    ) {}

    public function collection(array $requestParams = []): Collection
    {
        return $this->travelQuoteService->getGridData(requestParams: $requestParams)->get();
    }

    /**
     * Get the query builder instance to use for chunking
     * This is the key to memory-efficient CSV exports
     */
    public function getQuery(array $requestParams = []): ?Builder
    {
        return $this->travelQuoteService->getGridData(requestParams: $requestParams);
    }

    public function headings(): array
    {
        return [
            'REF-ID',
            'FIRST NAME',
            'LAST NAME',
            'LEAD STATUS',
            'AML STATUS',
            'INSURER AML STATUS',
            'ADVISOR REQUESTED',
            'ADVISOR',
            'BRANCH',
            'ADVISOR ASSIGNED DATE AND TIME',
            'API ISSUANCE STATUS',
            'INSURER API STATUS',
            'CREATED DATE',
            'TRAVEL START DATE',
            'TRAVEL END DATE',
            'TRAVEL DURATION',
            'LAST MODIFIED DATE',
            'DOB',
            'AGE GROUP',
            'TRANSAPP CODE',
            'LOST REASON',
            'SOURCE',
            'PROVIDER NAME',
            'PLAN NAME',
            'PREMIUM',
            'POLICY NUMBER',
            'DESTINATION',
            'CURRENTLY LOCATED IN',
            'EXPIRY DATE',
            'IS ECOMMERCE',
            'PAYMENT STATUS',
            'RENEWAL BATCH',
            'PREVIOUS POLICY EXPIRY DATE',
            'PREVIOUS POLICY PREMIUM',
            'PREVIOUS POLICY NUMBER',
            'TRAVEL TYPE',
            'TRAVEL COVERAGE',
            'TRANSACTION APPROVED DATE',
            'BOOKING DATE',
            'ADVISOR REQUESTED',
            'SEGMENT',
            'LEAD ASSIGNMENT TRIGGER',
            'PRIVATE CLIENT',
        ];
    }

    public function map($quote): array
    {
        $ageGroup = $this->getAgeGroup($quote);

        return [
            $quote->code,
            $quote->first_name,
            $quote->last_name,
            optional($quote->quoteStatus)->text,
            AMLStatusCode::getName($quote->aml_status) ?? '',
            AMLStatusCode::getName($quote->insurer_aml_status, 'N/A') ?? '',
            $quote->sic_advisor_requested == '0' ? 'No' : 'Yes',
            optional($quote->advisor)->name,
            $quote->advisor?->primaryBranch?->branch?->name,
            $quote->travelQuoteRequestDetail->advisor_assigned_date ?? '',
            $quote->api_issuance_status ? $quote->api_issuance_status : '',
            $quote->insurer_api_status ? $quote->insurer_api_status : '',
            date(config('constants.datetime_format'), strtotime($quote->created_at)),
            $quote->start_date ?? '',
            $quote->end_date ?? '',
            $quote->days_cover_for ?? '',
            date(config('constants.datetime_format'), strtotime($quote->updated_at)),
            date(config('constants.datetime_format'), strtotime($quote->dob)),
            $ageGroup,
            optional($quote->travelQuoteRequestDetail)->transapp_code,
            optional($quote->travelQuoteRequestDetail)->lostReason?->text,
            $quote->source,
            $quote->insuranceProvider->text ?? '',
            $quote->plan->text ?? '',
            $quote->premium,
            $quote->policy_number,
            optional($quote->destination)->text,
            optional($quote->currentlyLocatedIn)->text,
            date(config('constants.DATE_FORMAT'), strtotime($quote->expiry_date)),
            $quote->is_ecommerce ? 'Yes' : 'No',
            optional($quote->paymentStatus)->text,
            $quote->renewal_batch,
            $quote->previous_policy_expiry_date ? date('d-M-Y', strtotime($quote->previous_policy_expiry_date)) : '',
            $quote->previous_quote_policy_premium ? $quote->previous_quote_policy_premium : '',
            $quote->previous_quote_policy_number ? $quote->previous_quote_policy_number : '',
            $quote->direction_code,
            $quote->coverage_code,
            $quote->transaction_approved_at ? date(config('constants.datetime_format'), strtotime($quote->transaction_approved_at)) : '',
            $quote->policy_booking_date ? date(config('constants.datetime_format'), strtotime($quote->policy_booking_date)) : '',
            (isset($quote->sic_advisor_requested) && $quote->sic_advisor_requested) ? 'Yes' : 'No',
            $quote->getSegments($quote, QuoteTypeId::Travel) ?? '',
            $quote->lead_assignment_trigger ? LeadAssignmentTriggerEnum::getAssignmentTypeText($quote->lead_assignment_trigger) : '',
            $quote->customer?->pcp_tag_formatted ?? '',
        ];
    }

    /**
     * Get export metadata with travel-specific information
     */
    public function getExportMetadata(array $requestParams = []): array
    {
        return [
            'exportClass' => static::class,
            'timestamp' => now()->toISOString(),
            'parameters' => $requestParams,
            'sourceTable' => 'personal_quotes',
            'quoteTypeId' => 8, // QuoteTypeId::Travel
            'exportType' => 'travel_quotes',
        ];
    }

    private function getAgeGroup($quote)
    {
        if ($quote->child || $quote->parent) {
            return 'Both';
        }

        $age = Carbon::parse($quote->dob)->age;

        return $age < 65 ? '0 - 64' : '65 and above';
    }
}
