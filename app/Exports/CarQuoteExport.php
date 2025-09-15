<?php

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Enums\AMLStatusCode;
use App\Enums\AssignmentTypeEnum;
use App\Enums\LeadAssignmentTriggerEnum;
use App\Enums\QuoteTypeId;
use App\Services\CarQuoteService;
use App\Traits\ModernCsvExportable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CarQuoteExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    public function __construct(
        private CarQuoteService $carQuoteService
    ) {}

    /**
     * Get the data collection - this is used by the original implementation
     * and falls back when getQuery is not available
     */
    public function collection(array $requestParams = []): Collection
    {
        return $this->carQuoteService->getGridData(requestParams: $requestParams)->get();
    }

    /**
     * Get the query builder instance to use for chunking
     * This is the key to memory-efficient CSV exports
     */
    public function getQuery(array $requestParams = []): ?Builder
    {
        return $this->carQuoteService->getGridData(requestParams: $requestParams);
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
            'LAST MODIFIED DATE',
            'UPDATED BY',
            'ADDITIONAL NOTES',
            'ADVISOR',
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
        ];
    }

    /**
     * Map a database record to CSV row
     */
    public function map($quote): array
    {
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
            date(config('constants.datetime_format'), strtotime($quote->updated_at)),
            $quote->updated_by,
            $quote->additional_notes,
            $quote->advisor?->name,
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
            $quote->plan?->insuranceProvider?->text ?? '',
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
            'quoteTypeId' => 1, // QuoteTypeId::Car
            'exportType' => 'car_quotes',
        ];
    }
}
