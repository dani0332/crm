<?php

namespace App\Exports;

use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Imports\UploadAndCreateImport;
use App\Models\RenewalQuoteProcess;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class RenewalFailedValidationExport implements FromCollection, WithStrictNullComparison
{
    private $renewaUploadLead;

    public function __construct($renewalUploadLead)
    {
        $this->renewaUploadLead = $renewalUploadLead;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $failedLeads = RenewalQuoteProcess::where('renewals_upload_lead_id', $this->renewaUploadLead->id)->whereIn('status', [RenewalProcessStatuses::BAD_DATA, RenewalProcessStatuses::VALIDATION_FAILED])->get();
        $exportLeads = collect();
        if ($this->renewaUploadLead->renewal_import_type == RenewalsUploadType::CREATE_LEADS) {
            $columns = (new UploadAndCreateImport($this->renewaUploadLead))->getColumns();
            $firstRow = (object) [];
            foreach ($columns as $key => $column) {
                $firstRow->{$key} = $column['title'];
            }
            $firstRow->errors = 'Error Message(s)';
            $exportLeads->push($firstRow);

            foreach ($failedLeads as $lead) {
                if (! $lead->data) {
                    continue;
                }
                $leadData = $lead->data;
                $row = $this->mapFailedLeadToCreateQuoteFormat($columns, $leadData);
                $row['errors'] = is_string($lead->validation_errors) ? $lead->validation_errors : (is_array($lead->validation_errors) ? implode('; ', $lead->validation_errors) : 'No errors');
                $exportLeads->push((object) $row);
            }

            return $exportLeads;
        }
        if ($this->renewaUploadLead->renewal_import_type == RenewalsUploadType::UPDATE_LEADS) {
            $firstRow = (object) [];
            $firstRow->customer_name = 'Customer Name';
            $firstRow->email = 'Customer e-mail';
            $firstRow->mobile_no = 'Customer Mobile';
            $firstRow->quote_type = 'Insurance Type';
            $firstRow->insurer = 'Insurance Provider';
            $firstRow->registration_type = 'Registration Type';
            $firstRow->vehicle_usage = 'Vehicle Use';
            $firstRow->business_activity = 'Business Activity';
            $firstRow->driver_name = 'Driver Name';
            $firstRow->product_type = 'Product Type';
            $firstRow->advisor = 'Advisor Email';
            $firstRow->policy_number = 'Policy Number';
            $firstRow->end_date = 'Policy End date';
            $firstRow->batch = 'Batch';
            $firstRow->make = 'Car Make';
            $firstRow->model = 'Car Model';
            $firstRow->year = 'Model Year';
            $firstRow->dob = 'Date of Birth';
            $firstRow->driving_experience = 'Driving Experience';
            $firstRow->nationality = 'Nationality';
            $firstRow->provider_name = 'Provider Name';
            $firstRow->plan_name = 'Plan Name';
            $firstRow->plan_type = 'Repair Type';
            $firstRow->claim_history = 'Claims History';
            $firstRow->nc_letter = 'NC Letter';
            $firstRow->insurer_quote_no = 'Insurer Quote No.';
            $firstRow->car_value = 'Car Value (From Insurer)';
            $firstRow->premium = 'Renewal Premium';
            $firstRow->excess = 'Excess';
            $firstRow->ancillary_excess = 'Ancillary Excess';
            $firstRow->driver_cover = 'PAB Driver';
            $firstRow->driver_cover_amount = 'Amount - PAB Driver';
            $firstRow->passenger_cover = 'PAB Passenger';
            $firstRow->passenger_cover_amount = 'Amount - PAB Passenger';
            $firstRow->car_hire = 'Rent a car';
            $firstRow->car_hire_amount = 'Amount- Rent a Car';
            $firstRow->oman_cover = 'Oman cover';
            $firstRow->oman_cover_amount = 'Amount - Oman cover';
            $firstRow->road_side_assistance = 'Road Side Assistance';
            $firstRow->road_side_assistance_amount = 'Amount - Road Side Assistenace';
            $firstRow->year_of_first_registration = 'First Year of Registration';
            $firstRow->trim = 'Trim';
            $firstRow->registration_location = 'Registration Location';
            $firstRow->previous_advisor = 'Previous Advisor Email';
            $firstRow->notes = 'Notes';
            $firstRow->is_gcc = 'Is GCC';
            $firstRow->errors = 'Error Message(s)';
            $exportLeads->push($firstRow);

            foreach ($failedLeads as $lead) {
                if ($lead->data) {
                    $leadData = $lead->data;
                    if (isset($leadData['renewal_batch_id'])) {
                        unset($leadData['renewal_batch_id']);
                    }
                    $leadData['errors'] = $lead->validation_errors ?? 'No errors';
                    $exportLeads->push($leadData);
                }
            }
        }

        return $exportLeads;
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
            } else {
                $row[$key] = $leadData[$key] ?? null;
            }
        }

        return $row;
    }
}
