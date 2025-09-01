<?php

declare(strict_types=1);

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Services\ClaimsService;
use App\Traits\ModernCsvExportable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ClaimsExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    public function __construct(
        private ClaimsService $claimsService,
        private array $requestParams = []
    ) {
        $this->requestParams = $requestParams;
    }

    /**
     * Get the data collection - this is used by the original implementation
     * and falls back when getQuery is not available
     */
    public function collection(array $requestParams = []): Collection
    {
        // If request params were provided in constructor, use those
        // Otherwise use the params passed to this method
        $params = !empty($this->requestParams) ? $this->requestParams : $requestParams;
        return $this->claimsService->getClaimsDataForExport($params)->get();
    }

    /**
     * Get the query builder instance to use for chunking
     * This is the key to memory-efficient CSV exports
     */
    public function getQuery(array $requestParams = []): ?Builder
    {
        // If request params were provided in constructor, use those
        // Otherwise use the params passed to this method
        $params = !empty($this->requestParams) ? $this->requestParams : $requestParams;
        return $this->claimsService->getClaimsDataForExport($params);
    }

    /**
     * Define the CSV headings
     */
    public function headings(): array
    {
        return [
            'CLAIM CODE',
            'CLAIM NUMBER',
            'FIRST NAME',
            'LAST NAME',
            'EMAIL',
            'MOBILE NO',
            'POLICY NUMBER',
            'LINE OF BUSINESS',
            'CLAIM TYPE',
            'CLAIM STATUS',
            'CLAIM SUB STATUS',
            'COMPLAINT STATUS',
            'ASSIGNED MANAGER',
            'MANAGER ASSIGNED DATE',
            'INSURANCE PROVIDER',
            'INCIDENT DATE',
            'INCIDENT DESCRIPTION',
            'SOURCE',
            'NEXT FOLLOW UP DATE',
            'NEXT FOLLOW UP NOTES',
            'COMPLAINT DATE TIME',
            'COMPLAINT NOTES',
            'APPROVED REPAIR AMOUNT',
            'APPROVED TOTAL LOSS AMOUNT',
            'APPROVED CASH LOSS AMOUNT',
            'CLAIM DECLINE REASON',
            'CREATED DATE',
            'UPDATED DATE',
            // Car-specific fields
            'PLATE NUMBER',
            'CAR MAKE',
            'CAR MODEL',
            'MODEL YEAR',
            // Health-specific fields
            'CLAIM REQUEST TYPE',
            'SERVICE TYPE',
            'REQUEST REFERENCE NUMBER',
        ];
    }

    /**
     * Map a database record to CSV row
     */
    public function map($claim): array
    {
        return [
            $claim->code,
            $claim->claim_number,
            $claim->first_name,
            $claim->last_name,
            $claim->email,
            $claim->mobile_no,
            $claim->policy_number,
            $claim->quoteType?->text ?? '',
            $claim->claimType?->text ?? '',
            $claim->claimStatus?->text ?? '',
            $claim->claimSubStatus?->text ?? '',
            $claim->complaintStatus?->text ?? '',
            $claim->manager?->name ?? '',
            $claim->manager_assigned_date ? Carbon::parse($claim->manager_assigned_date)->format(config('constants.datetime_format')) : '',
            $claim->insuranceProvider?->text ?? '',
            $claim->incident_date ? Carbon::parse($claim->incident_date)->format(config('constants.DATE_FORMAT_ONLY')) : '',
            $claim->incident ?? '',
            $claim->source ?? '',
            $claim->next_followup_datetime ? Carbon::parse($claim->next_followup_datetime)->format(config('constants.datetime_format')) : '',
            $claim->next_followup_notes ?? '',
            $claim->complaint_datetime ? Carbon::parse($claim->complaint_datetime)->format(config('constants.datetime_format')) : '',
            $claim->complaint_notes ?? '',
            $claim->approved_repair_amount ?? '',
            $claim->approved_total_loss_amount ?? '',
            $claim->approved_cash_loss_amount ?? '',
            $claim->claim_decline_reason ?? '',

            $claim->created_at ? Carbon::parse($claim->created_at)->format(config('constants.datetime_format')) : '',
            $claim->updated_at ? Carbon::parse($claim->updated_at)->format(config('constants.datetime_format')) : '',
            // Car-specific fields
            $claim->claimRequestDetails?->plat_number ?? '',
            $claim->claimRequestDetails?->car_make ?? '',
            $claim->claimRequestDetails?->car_model ?? '',
            $claim->claimRequestDetails?->model_year ?? '',
            // Health-specific fields
            $claim->claimRequestType?->text ?? '',
            $claim->claimRequestDetails?->serviceType?->text ?? '',
            $claim->claimRequestDetails?->request_reference_number ?? '',
        ];
    }


    /**
     * Get export metadata with claims-specific information
     */
    public function getExportMetadata(array $requestParams = []): array
    {
        return [
            'exportClass' => static::class,
            'timestamp' => now()->toISOString(),
            'parameters' => $requestParams,
            'sourceTable' => 'claim_requests',
            'exportType' => 'claims',
        ];
    }
}
