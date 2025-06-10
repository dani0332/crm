<?php

declare(strict_types=1);

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Services\CarQuoteService;
use App\Traits\ModernCsvExportable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Example implementation showing how to migrate existing export classes
 * to use the new cleaner structure
 */
class ModernCarQuoteExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    public function __construct(
        private CarQuoteService $carQuoteService
    ) {}

    /**
     * Get the data collection for CSV export
     */
    public function collection(array $requestParams = []): Collection
    {
        return $this->carQuoteService->getGridData(requestParams: $requestParams)->get();
    }

    /**
     * Get the query builder instance for chunked processing
     * This enables memory-efficient CSV exports for large datasets
     */
    public function getQuery(array $requestParams = []): ?Builder
    {
        return $this->carQuoteService->getGridData(requestParams: $requestParams);
    }

    /**
     * Define the CSV column headings
     */
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
            'ADVISOR ASSIGNED DATE AND TIME',
            'API ISSUANCE STATUS',
            'INSURER API STATUS',
            'CREATED DATE',
            'LAST MODIFIED DATE',
            'DOB',
            'AGE',
            'GENDER',
            'NATIONALITY',
            'YEARS OF DRIVING',
            'EMIRATES OF REGISTRATION',
            'TRANSAPP CODE',
            'LOST REASON',
            'SOURCE',
            'PROVIDER NAME',
            'PLAN NAME',
            'PREMIUM',
            'POLICY NUMBER',
            'CAR MAKE',
            'CAR MODEL',
            'YEAR OF MANUFACTURE',
            'CAR VALUE',
            'VEHICLE TYPE',
            'REPAIR TYPE',
            'IS ECOMMERCE',
            'PAYMENT STATUS',
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
        ];
    }

    /**
     * Map a single record to CSV row data
     */
    public function map($quote): array
    {
        return [
            $quote->code,
            $quote->first_name,
            $quote->last_name,
            $quote->lead_status,
            $quote->aml_status_code == 1 ? 'Approved' : ($quote->aml_status_code == 2 ? 'Declined' : 'N/A'),
            $quote->insurer_aml_status ?? 'N/A',
            $quote->advisor_requested ?? 'N/A',
            $quote->advisor,
            $quote->advisor_assigned_date,
            $quote->api_issuance_status ?? 'N/A',
            $quote->insurer_api_status ?? 'N/A',
            $quote->created_at ? $quote->created_at->format('d-m-Y H:i:s') : 'N/A',
            $quote->updated_at ? $quote->updated_at->format('d-m-Y H:i:s') : 'N/A',
            $quote->dob ? date('d-m-Y', strtotime($quote->dob)) : 'N/A',
            $quote->age ?? 'N/A',
            $quote->gender ?? 'N/A',
            $quote->nationality ?? 'N/A',
            $quote->years_of_driving ?? 'N/A',
            $quote->emirates_of_registration ?? 'N/A',
            $quote->transapp_code ?? 'N/A',
            $quote->lost_reason ?? 'N/A',
            $quote->source ?? 'N/A',
            $quote->provider_name ?? 'N/A',
            $quote->plan_name ?? 'N/A',
            $quote->premium ?? 'N/A',
            $quote->policy_number ?? 'N/A',
            $quote->car_make ?? 'N/A',
            $quote->car_model ?? 'N/A',
            $quote->year_of_manufacture ?? 'N/A',
            $quote->car_value ?? 'N/A',
            $quote->vehicle_type ?? 'N/A',
            $quote->repair_type ?? 'N/A',
            $quote->is_ecommerce ? 'Yes' : 'No',
            $quote->payment_status ?? 'N/A',
            $quote->renewal_batch ?? 'N/A',
            $quote->previous_policy_expiry_date ?? 'N/A',
            $quote->previous_policy_premium ?? 'N/A',
            $quote->previous_policy_number ?? 'N/A',
            $quote->transaction_approved_date ?? 'N/A',
            $quote->booking_date ?? 'N/A',
            $quote->assignment_type_id == 1 ? 'Manual' : ($quote->assignment_type_id == 2 ? 'Auto' : 'N/A'),
            $quote->advisor_requested ?? 'N/A',
            $quote->segment ?? 'N/A',
            $quote->lead_assignment_trigger_id == 1 ? 'Created' : ($quote->lead_assignment_trigger_id == 2 ? 'Reassigned' : 'N/A'),
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
            'exportType' => 'car_quotes',
            'quoteTypeId' => 1, // Car quote type ID
            'includesCRM' => true,
            'includesAML' => true,
        ];
    }
}
