<?php

namespace App\Exports;

use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Imports\UploadAndCreateImport;
use App\Models\RenewalQuoteProcess;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class RenewalFailedValidationExport implements FromCollection, WithStrictNullComparison
{
    private $renewalUploadLead;

    public function __construct($renewalUploadLead)
    {
        $this->renewalUploadLead = $renewalUploadLead;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        $failedLeads = RenewalQuoteProcess::where('renewals_upload_lead_id', $this->renewalUploadLead->id)
            ->whereIn('status', [RenewalProcessStatuses::BAD_DATA, RenewalProcessStatuses::VALIDATION_FAILED])
            ->get();

        $exportLeads = collect();

        switch ($this->renewalUploadLead->renewal_import_type) {
            default:
                // Optionally, handle unknown import type. For now, do nothing or log an error.
                break;
            case RenewalsUploadType::CREATE_LEADS:
                $this->handleCreateLeads($exportLeads, $failedLeads);
                break;
            case RenewalsUploadType::UPDATE_LEADS:
                $this->handleUpdateLeads($exportLeads, $failedLeads);
                break;
        }

        return $exportLeads;
    }

    /**
     * Handle export for CREATE_LEADS import type.
     */
    private function handleCreateLeads($exportLeads, $failedLeads)
    {
        $columns = (new UploadAndCreateImport($this->renewalUploadLead))->getColumns();
        $exportLeads->push($this->getCreateLeadsHeader($columns));

        foreach ($failedLeads as $lead) {
            if (! $lead->data) {
                continue;
            }
            $leadData = $lead->data;
            $row = $this->mapFailedLeadToCreateQuoteFormat($columns, $leadData);
            $row['errors'] = $this->formatValidationErrors($lead->validation_errors);
            $exportLeads->push((object) $row);
        }
    }

    /**
     * Handle export for UPDATE_LEADS import type.
     */
    private function handleUpdateLeads($exportLeads, $failedLeads)
    {
        $exportLeads->push($this->getUpdateLeadsHeader());

        foreach ($failedLeads as $lead) {
            if (! $lead->data) {
                continue;
            }
            $leadData = $lead->data;
            unset($leadData['renewal_batch_id']);
            $leadData['errors'] = $lead->validation_errors ?? 'No errors';
            $exportLeads->push($leadData);
        }
    }

    /**
     * Create header row for CREATE_LEADS.
     */
    private function getCreateLeadsHeader($columns)
    {
        $firstRow = (object) [];
        foreach ($columns as $key => $column) {
            $firstRow->{$key} = $column['title'];
        }
        $firstRow->errors = 'Error Message(s)';

        return $firstRow;
    }

    /**
     * Create header row for UPDATE_LEADS.
     */
    private function getUpdateLeadsHeader()
    {
        $headers = [
            'customer_name' => 'Customer Name',
            'email' => 'Customer e-mail',
            'mobile_no' => 'Customer Mobile',
            'quote_type' => 'Insurance Type',
            'insurer' => 'Insurance Provider',
            'registration_type' => 'Registration Type',
            'vehicle_usage' => 'Vehicle Use',
            'business_activity' => 'Business Activity',
            'driver_name' => 'Driver Name',
            'product_type' => 'Product Type',
            'advisor' => 'Advisor Email',
            'policy_number' => 'Policy Number',
            'end_date' => 'Policy End date',
            'batch' => 'Batch',
            'make' => 'Car Make',
            'model' => 'Car Model',
            'year' => 'Model Year',
            'dob' => 'Date of Birth',
            'driving_experience' => 'Driving Experience',
            'nationality' => 'Nationality',
            'provider_name' => 'Provider Name',
            'plan_name' => 'Plan Name',
            'plan_type' => 'Repair Type',
            'claim_history' => 'Claims History',
            'nc_letter' => 'NC Letter',
            'insurer_quote_no' => 'Insurer Quote No.',
            'car_value' => 'Car Value (From Insurer)',
            'premium' => 'Renewal Premium',
            'excess' => 'Excess',
            'ancillary_excess' => 'Ancillary Excess',
            'driver_cover' => 'PAB Driver',
            'driver_cover_amount' => 'Amount - PAB Driver',
            'passenger_cover' => 'PAB Passenger',
            'passenger_cover_amount' => 'Amount - PAB Passenger',
            'car_hire' => 'Rent a car',
            'car_hire_amount' => 'Amount- Rent a Car',
            'oman_cover' => 'Oman cover',
            'oman_cover_amount' => 'Amount - Oman cover',
            'road_side_assistance' => 'Road Side Assistance',
            'road_side_assistance_amount' => 'Amount - Road Side Assistenace',
            'year_of_first_registration' => 'First Year of Registration',
            'trim' => 'Trim',
            'registration_location' => 'Registration Location',
            'previous_advisor' => 'Previous Advisor Email',
            'notes' => 'Notes',
            'is_gcc' => 'Is GCC',
            'errors' => 'Error Message(s)',
        ];

        $firstRow = (object) [];
        foreach ($headers as $key => $title) {
            $firstRow->{$key} = $title;
        }

        return $firstRow;
    }

    /**
     * Format the validation errors for export.
     */
    private function formatValidationErrors($errors)
    {
        if (is_string($errors)) {
            return $errors;
        }
        if (is_array($errors)) {
            return implode('; ', $errors);
        }

        return 'No errors';
    }

    /**
     * Map failed lead data to the exact format expected by UploadAndCreateImport and createQuote().
     * Ensures downloaded file can be re-uploaded to create renewal quotes (all LOBs: Car, Bike, Life, Home, etc.).
     *
     * @param  array<string, array{index: int, title: string, rules: mixed}>  $columns
     * @param  array<string, mixed>  $leadData
     * @return array<string, mixed>
     */
    protected function mapFailedLeadToCreateQuoteFormat(array $columns, array $leadData): array
    {
        $row = [];
        foreach ($columns as $key => $column) {
            if ($key === 'premium') {
                $row[$key] = $leadData['premium'] ?? $leadData['previous_quote_policy_premium'] ?? null;
            } elseif ($key === 'previous_commission') {
                $row[$key] = $leadData['previous_commission'] ?? $leadData['previous_quote_policy_commission'] ?? null;
            } elseif ($key === 'previous_ref_id') {
                $row[$key] = $leadData['previous_ref_id'] ?? null;
            } else {
                $row[$key] = $leadData[$key] ?? null;
            }
        }

        return $row;
    }
}
